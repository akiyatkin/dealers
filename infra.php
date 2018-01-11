<?php
use infrajs\event\Event;
use akiyatkin\prices\Prices;
use infrajs\excel\Xlsx;
use infrajs\path\Path;
use infrajs\each\Each;


Event::handler('Catalog.oninit', function (&$data) {
	$list = Prices::getList();
	$ids = array();
	foreach ($list as $dealer => $info) {
		$ids[$dealer] = array();
		$rule = Prices::getRule($dealer);
		Event::$classes['Prices-'.$dealer] = function (&$obj) {
			return $obj['key'];
		};

		Xlsx::runPoss($info['data'], function &(&$pos, $i, $group) use (&$ids, $rule, $dealer) {
			$key = Prices::getHash($pos, $rule['price'], $dealer);
			//$id = $dealer.'-'.$key;
			//$ids[$dealer][$id] = $pos;
			$pos['path'] = $group['path'];
			$id = $key;
			$ids[$dealer][$id] = $pos;

			$r = null;
			return $r;
		});
		
	};
	Xlsx::runPoss($data, function &(&$pos) use ($ids) {
		$r = null;
		$dealer = $pos['producer'];
		$rule = Prices::getRule($dealer);
		$key = Prices::getHash($pos, $rule['catalog'], $dealer);
		$id = $key;

		if (empty($ids[$dealer][$id])) return $r;

		$price = &$ids[$dealer][$id];

		Prices::checkSynonyms($price, $rule);

		$data = array(
			'key' => $id,
			'price' => $price,
			'pos' => &$pos
		);
		
		Event::fire('Prices-'.$dealer.'.oninit', $data);
		
		if (isset($data['pos']['Цена']) && $data['pos']['Цена'] > Prices::$conf['costlimit']) {
			unset($data['pos']['Цена']);
		}
		$r = null;
		return $r;
	});
}, 'prices');
Path::reqif('~prices.php');
