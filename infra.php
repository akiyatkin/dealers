<?php
use infrajs\event\Event;
use akiyatkin\prices\Prices;
use infrajs\excel\Xlsx;
use infrajs\path\Path;
use infrajs\each\Each;

Path::reqif('~prices.php');
Event::handler('Catalog.oninit', function (&$data) {
	
	$list = Prices::getList();
	$ids = array();
	foreach ($list as $prod => $info) {
		$ids[$prod] = array();
		$rule = Prices::getRule($prod);
		Event::$classes['Prices-'.$prod] = function (&$obj) {
			return $obj['key'];
		};

		Xlsx::runPoss($info['data'], function &(&$pos, $i, $group) use (&$ids, $rule, $prod) {
			Prices::checkSynonyms($pos, $rule);
			$key = Prices::getHash($pos, $rule['price'], $prod);
			//$id = $prod.'-'.$key;
			//$ids[$prod][$id] = $pos;
			$pos['path'] = $group['path'];
			$id = $key;
			$ids[$prod][$id] = $pos;

			$r = null;
			return $r;
		});
		
	};

	Xlsx::runPoss($data, function &(&$pos) use ($ids) {
		$r = null;
		$prod = $pos['producer'];
		$rule = Prices::getRule($prod);
		$key = Prices::getHash($pos, $rule['catalog'], $prod);
		$id = $key;

		if (empty($ids[$prod][$id])) return $r;

		$price = &$ids[$prod][$id];
		$data = array(
			'key' => $id,
			'price' => $price,
			'pos' => &$pos
		);

		

		Event::fire('Prices-'.$prod.'.oninit', $data);
		if (!isset($data['pos']['Цена'])){
			if (isset($data['pos']['Цена оптовая'])) $data['pos']['Цена'] = $data['pos']['Цена оптовая'];
			if (isset($data['pos']['Цена розничная'])) $data['pos']['Цена'] = $data['pos']['Цена розничная'];
		}
		
		$r = null;
		return $r;
	});
	//echo '<pre>';
	//print_r($data);
}, 'prices');

