<?php
namespace akiyatkin\dealers;
use infrajs\excel\Xlsx;
use infrajs\ans\Ans;
use infrajs\path\Path;
use infrajs\load\Load;
use akiyatkin\dealers\Dealers;

class Dealers {
	public static $folder = '~.dealers/';
	public static function getList()
	{
		$list = array();
		array_map(function ($file) use (&$list) {
		
			if ($file[0] == '.') return;
			if ($file[0] == '~') return;

			$folder = Dealers::$folder;

			$src = Path::theme($folder.$file); //Проверка что файл.
			if (!$src) return;
			$file = Path::toutf($file);
			$fd = Load::nameInfo($file);

			if (!in_array($fd['ext'], array('xlsx','xls'))) return;
			
			//Данные из прайса Дилера
			$fd['data'] = Dealers::getData($folder.$file);

			$name = $fd['name'];
			$list[$name] = $fd;

		}, scandir(Path::resolve(Dealers::$folder)));
		return $list;
	}
	public static function getData($src)
	{
		$fd = Load::srcInfo($src);
		$name = $fd['name'];
		$data = Xlsx::parseAll($src);
		Dealers::applyRules($data, $name);
		return Xlsx::get($data, $name);
	}
	public static function applyRules(&$data, $name)
	{
		$rules = Load::loadJSON('~dealers.json');
		$rule = isset($rules[$name])? $rules[$name]:array(
			"start" => 4,
			"ignore" => []
		);
		
		foreach ($data as $sheetname => $sheet) {
			if (in_array($sheetname, $rule['ignore'])) {
				unset($data[$sheetname]);
				continue;
			}
			foreach ($sheet as $i => $row) {
				if ($i >= $rule['start']) break;
				unset($data[$sheetname][$i]);
			}
		}
	}
}