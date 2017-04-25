<?php

use infrajs\excel\Xlsx;
use infrajs\ans\Ans;
use infrajs\path\Path;
use infrajs\access\Access;
use infrajs\template\Template;
use infrajs\load\Load;
use infrajs\config\Config;
use infrajs\catalog\Catalog;
use akiyatkin\dealers\Dealers;

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

$dealer = Ans::GET('dealer');
if (!$dealer) {
	$data = array();
	$list = Dealers::getList();
	foreach ($list as $dealer => $info) {	
		$data[$dealer] = Dealers::init($dealer); 
	}
	echo Template::parse('-dealers/layout.tpl', array('data' => $data), 'ROOT');
} else {
	$rule = Dealers::getRule($dealer);

	$data = Dealers::init($dealer);
	$images = Catalog::getIndex(Catalog::$conf['dir'].$dealer.'/images/');
	foreach ($data['bingo'] as $obj) {
		if (isset($images[$obj['catalog']['dealerkey']])) unset($images[$obj['catalog']['dealerkey']]);
	}
	
	foreach ($data['lose'] as $obj) { //Только в каталоге
		if (isset($images[$obj['catalog']['dealerkey']])) unset($images[$obj['catalog']['dealerkey']]);
	}
	ksort($images);
	echo Template::parse('-dealers/layout.tpl', array(
		'data' => $data, 
		'images' => $images,
		'dealer' => $dealer, 
		'rule' => $rule
	), 'DEALER');
}
