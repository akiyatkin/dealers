<?php

use infrajs\excel\Xlsx;
use infrajs\ans\Ans;
use infrajs\path\Path;
use infrajs\load\Load;
use infrajs\dealers\Dealers;

$ans = array();
$folder = '~.dealers/';
$rules = Load::loadJSON('~dealers.json');
$list = array();
array_map(function ($file) use (&$list, &$folders, $folder, $rules) {
	if ($file[0] == '.') return;
	if ($file[0] == '~') return;
	$src = Path::theme($folder.$file); //Проверка что файл.
	if (!$src) return;
	$file = Path::toutf($file);
	$fd = Load::nameInfo($file);
	if (!in_array($fd['ext'], array('xlsx'))) return;
	$fd['size'] = round(filesize($src) / 1000, 2);
	$data = Xlsx::parseAll($folder.$file);
	$name = $fd['name'];


	Dealers::applyRules($data);


	$fd['data'] = Xlsx::get($data, $fd['name']);
	$list[$fd['name']] = $fd;
}, scandir(Path::resolve($folder)));

$ans['list'] = $list;

return Ans::ret($ans);