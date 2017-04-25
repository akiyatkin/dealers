<?php
use infrajs\event\Event;
use akiyatkin\dealers\Dealers;
use infrajs\excel\Xlsx;




Event::handler('Catalog.oninit', function (&$data) {

	$list = Dealers::getList();
	$ids = array();
	foreach ($list as $dealer => $info) {	
		$rule = Dealers::getRule($dealer);
		Event::$classes['Dealers-'.$dealer] = function (&$obj) {
			return $obj['key'];
		};

		Xlsx::runPoss($info['data'], function &(&$pos) use (&$ids, $rule, $dealer) {
			$key = Dealers::getKey($pos, $rule['price']);
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
		$rule = Dealers::getRule($dealer);
		$key = Dealers::getKey($pos, $rule['catalog']);
		$id = $dealer.' '.$key;
		if (empty($ids[$id])) return $r;	
		$data = array(
			'key' => $key,
			'price' => $ids[$id],
			'pos' => &$pos
		);
		Event::fire('Dealers-'.$dealer.'.oninit', $data);
		return $r;
	});
}, 'dealers');