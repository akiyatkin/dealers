<?php

use infrajs\excel\Xlsx;
use infrajs\ans\Ans;
use infrajs\path\Path;
use infrajs\access\Access;
use infrajs\template\Template;
use infrajs\load\Load;
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
function clearKey(&$value, $key) {
	$value = str_replace(["\n","\r"," ", "\t"], '', $value);
	$value = str_replace(".", ',', $value);
}
Access::debug(true); //Запрещает доступ если нет отладочного режима.

$ans = array();

$list = Dealers::getList();

$data = Catalog::init();


foreach ($list as $dealer => $info) {
	if (isset($_GET['name'])) $dealer = $_GET['name'];
	
	
	$price = array();
	$ans[$dealer] = Array();
	$poss = array();
	Xlsx::runPoss($data, function &($pos) use (&$poss, $dealer) {
		$r = null;
		if ($pos['Производитель'] != $dealer) return $r;
		$poss[] = $pos['Артикул'];
		return $r;
	});
	foreach ($info['data']['childs'] as $sheet) {
		foreach ($sheet['data'] as $pos) {
			if (isset($pos['Модель']))
				$price[] = $pos['Модель'];
		}
		
	}
	array_walk($poss, function (&$value, $key) {
		$value = str_replace(["\n","\r"," ", "\t"], '', $value);
		$value = str_replace(".", ',', $value);
	});
	array_walk($price, function (&$value, $key) {
		$value = str_replace(["\n","\r"," ", "\t"], '', $value);
		$value = str_replace(".", ',', $value);
	});
	sort($poss);
	sort($price);
	$poss_len = count($poss);
	$price_len = count($price);
	$miss = array();
	$bingo = array();
	$lose = array();
	$i = 0;
	$j = 0;
	while ($i < $poss_len && $j < $price_len) {
		$r = strcasecmp($poss[$i], $price[$j]);
		if ($r == 0) {
			$bingo[] = $poss[$i];
			$i++;
			$j++;
		} else if ($r < 0) {
			$lose[] = $poss[$i];
			$i++;
		} else if ($r > 0) { 
			$miss[] = $price[$j];
			$j++;
		}
	}
	while ($i < $poss_len) {
			$lose[] = $poss[$i];
			$i++;
	}
	while ($j < $price_len) {
			$miss[] = $price[$j];
			$j++;
	}
	$ans[$dealer]['bingo'] = $bingo;
	$ans[$dealer]['miss'] = $miss;
	$ans[$dealer]['lose'] = $lose;
	
	if (isset($_GET['name'])) break;
}


echo Template::parse('-dealers/layout.tpl', array('data' => $ans) );