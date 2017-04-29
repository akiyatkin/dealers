<?php

use infrajs\excel\Xlsx;
use infrajs\ans\Ans;
use infrajs\path\Path;
use infrajs\access\Access;
use infrajs\template\Template;
use infrajs\load\Load;
use infrajs\each\Each;
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
$ans = array();
$price = Ans::GET('price');
if (!$price) {
	$data = array();
	$list = Prices::getList();
	foreach ($list as $price => $info) {	
		$data[$price] = Prices::init($price); 
	}
	echo Template::parse('-prices/layout.tpl', array('data' => $data), 'ROOT');
} else {
	$rule = Prices::getRule($price);
	if (isset($_GET['show'])) {
		$list = Prices::getList();
		$info = $list[$price];	
		$data = array();
		Each::exec($info['data']['childs'], function &($group) use (&$data){

			$r = null;
			$data[$group['title']] = $group['head'];
			return $r;
		});
		echo Template::parse('-prices/layout.tpl', array(
			'data' => $data, 
			'price' => $price, 
			'rule' => $rule
		), 'SHOW');
	} else {
		if (!$rule) return Ans::err($ans,'Дилер не зарегистрирован в ~prices.json');
		$data = Prices::init($price);

		$images = Catalog::getIndex(Catalog::$conf['dir'].$price.'/images/');
		foreach ($data['bingo'] as $obj) {
			if (isset($images[$obj['catalog']['article']])) unset($images[$obj['catalog']['article']]);
			if (isset($images[$obj['catalog']['producer'].'-'.$obj['catalog']['article']])) unset($images[$obj['catalog']['producer'].'-'.$obj['catalog']['article']]);
		}
		
		foreach ($data['lose'] as $obj) { //Только в каталоге
			if (isset($images[$obj['catalog']['article']])) unset($images[$obj['catalog']['article']]);
			if (isset($images[$obj['catalog']['producer'].'-'.$obj['catalog']['article']])) unset($images[$obj['catalog']['producer'].'-'.$obj['catalog']['article']]);
		}
		ksort($images);
		echo Template::parse('-prices/layout.tpl', array(
			'data' => $data, 
			'images' => $images,
			'price' => $price, 
			'rule' => $rule
		), 'PRICE');
	}
}
