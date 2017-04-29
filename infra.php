<?php
use infrajs\event\Event;
use akiyatkin\prices\Prices;
use infrajs\excel\Xlsx;
use infrajs\path\Path;


Event::handler('Catalog.oninit', function (&$data) {

	$list = Prices::getList();
	$ids = array();
	foreach ($list as $dealer => $info) {	
		$rule = Prices::getRule($dealer);
		Event::$classes['Prices-'.$dealer] = function (&$obj) {
			return $obj['key'];
		};

		Xlsx::runPoss($info['data'], function &(&$pos) use (&$ids, $rule, $dealer) {
			$key = Prices::getHash($pos, $rule['price']);
			$id = $dealer.' '.$key;
			$ids[$id] = $pos;
			$r = null;
			return $r;
		});		
	}
	;
	Xlsx::runPoss($data, function &(&$pos) use ($ids) {
		$r = null;
		$dealer = $pos['producer'];
		$rule = Prices::getRule($dealer);
		$key = Prices::getHash($pos, $rule['catalog']);
		$id = $dealer.' '.$key;
		if (empty($ids[$id])) return $r;	
		$data = array(
			'key' => $key,
			'price' => $ids[$id],
			'pos' => &$pos
		);
		Event::fire('Prices-'.$dealer.'.oninit', $data);
		return $r;
	});
}, 'prices');
Path::reqif('~prices.php');
