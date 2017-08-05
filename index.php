<?php

use infrajs\excel\Xlsx;
use infrajs\ans\Ans;
use infrajs\path\Path;
use infrajs\access\Access;
use infrajs\template\Template;
use infrajs\load\Load;
use infrajs\each\Each;

use infrajs\rest\Rest;
use infrajs\config\Config;
use infrajs\catalog\Catalog;
use akiyatkin\prices\Prices;

/*
Проверяет соответствие загруженных прайсов с данными в каталоге. 

Amatek
Каталог 100 (30)
Прайс 80	(10)

70 есть Ключ в Прайсе и в Каталоге


Какие позиции исчезли	(Код произвоидетяля в каталоге есть а в Прайсе нет)
Какие позиции добавились (Производитель нет соответствия в Каталоге)

В формает понятном для клиента.

*/
Access::debug(true); //Запрещает доступ если нет отладочного режима.

return Rest::get( function () {
		$prices = array();
		$list = Prices::getList();
		foreach ($list as $price => $info) {
			$prices[$price] = array();
			$prices[$price]['data'] = Prices::init($price); 
		}

		
		echo Rest::parse('-prices/layout.tpl', array(
			'prices' => $prices
		), 'ROOT');
	}, [ function ($price) {
		$rule = Prices::getRule($price);
		$data = Prices::init($price);
		$images = Catalog::getIndex(Catalog::$conf['dir'].$price.'/images/');
		foreach ($data['bingo'] as $obj) {
			if ( isset($images[strtolower($obj['catalog']['article'])]) ) unset($images[strtolower($obj['catalog']['article'])]);
			if ( isset($images[strtolower($obj['catalog']['producer'].'-'.$obj['catalog']['article'])])) unset($images[strtolower($obj['catalog']['producer'].'-'.$obj['catalog']['article'])]);
		}		
		foreach ($data['losepr'] as $obj) { //Только в каталоге
			if ( isset(  $images[strtolower($obj['catalog']['article'])] ) )  {
				unset( $images[ strtolower($obj['catalog']['article'])]);
			}
			
			
			$key = $obj['catalog']['producer'].'-'.$obj['catalog']['article'];
			$key = strtolower($key);
			if (isset($images[$key])) {
				unset($images[$key]);
			}
		}

		ksort($images);
		echo Rest::parse('-prices/layout.tpl', array(
			'data' => $data, 
			'images' => $images,
			'price' => $price, 
			'rule' => $rule
		),'PRICE');
	}, 'show', function ($price) {
		$rule = Prices::getRule($price);
		$list = Prices::getList();
		if(!isset($list[$price])) $list[$price] = array();
		$info = $list[$price];	
		$data = array();

		Each::exec($info['data']['childs'], function &($group) use (&$data){
			$r = null;
			$data[$group['title']] = $group['head'];
			return $r;
		});

		echo Rest::parse('-prices/layout.tpl', array(
			'data' => $data, 
			'price' => $price, 
			'rule' => $rule
		), 'SHOW');
	}, 'doubles', function ($price) {
		$rule = Prices::getRule($price);
		$data = Prices::init($price);

		echo Rest::parse('-prices/layout.tpl', array(
			'data' => $data, 
			'price' => $price, 
			'rule' => $rule
		),'DOUBLES');
	}, function () {
		http_response_code(404);
		echo '404 что вы имеете ввиду?';
	}
]);
