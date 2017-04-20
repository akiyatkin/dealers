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
Access::debug(true); //Запрещает доступ если нет отладочного режима.

$ans = array();

$list = Dealers::getList();

$data = Catalog::init();
$poss = array();

Xlsx::runPoss($data, function &($pos) use (&$poss) {
	$r = null;
	if ($pos['Производитель'] != 'Amatek') return $r;
	$poss[] = $pos;

	
	return $r;
});
echo '<pre>';
print_r($poss);
exit;







$ans['data'] = $data;
$ans['list'] = $list;





//echo Template::parse('-dealers/layout.tpl', $ans);
//exit;

return Ans::ret($ans);