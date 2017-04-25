<?php
namespace akiyatkin\dealers;
use infrajs\excel\Xlsx;
use infrajs\ans\Ans;
use infrajs\path\Path;
use infrajs\once\Once;
use infrajs\load\Load;
use akiyatkin\dealers\Dealers;
use infrajs\catalog\Catalog;

class Dealers {
	public static $folder = '~.dealers/';
	public static function init($dealer) 
	{
		$list = Dealers::getList();
		$data = Catalog::init();

		$info = $list[$dealer];
		$rule = Dealers::getRule($dealer);
		//$images = Catalog::getIndex(Catalog::$conf['dir'].$dealer.'/images/');
		$poss = array();
		Xlsx::runPoss($data, function &($pos) use (&$poss, $dealer, $rule) {
			$r = null;
			if ($pos['Производитель'] != $dealer) return $r;
			$name = $rule['catalog'];
			if (empty($pos[$name])) $pos[$name] = 'Нет ключа синхронизации '.$pos['article'];
			$pos['dealerorig'] = $pos[$name];
			$pos['dealerkey'] = Dealers::getKey($pos, $name);
			$pos = Catalog::getPos($pos);
			$poss[] = $pos;
			return $r;
		});

		$price = array();
		Xlsx::runPoss($info['data'], function &(&$pos) use (&$price, $rule) {
			$r = null;
			$name = $rule['price'];
			if (empty($pos[$name])) return $r;
			$pos['dealerorig'] = $pos[$name];
			$pos['dealerkey'] = Dealers::getKey($pos, $name);
			$price[] = $pos;
			return $r;
		});
		
		
		//array_walk($price, array("akiyatkin\dealers\Dealers","clearKey"), $rule['price']);
		//array_walk($poss, array("akiyatkin\dealers\Dealers","clearKey"), $rule['catalog']);

		usort($price, array("akiyatkin\dealers\Dealers","usort"));

		usort($poss, array("akiyatkin\dealers\Dealers","usort"));
		

		$poss_len = count($poss);
		$price_len = count($price);
		$miss = array();
		$bingo = array();
		$lose = array();
		$i = 0;
		$j = 0;
		while ($i < $poss_len && $j < $price_len) {
			$r = strcasecmp($poss[$i]['dealerkey'], $price[$j]['dealerkey']);

			if ($r == 0) {
				$bingo[] = array(
					'catalog' => $poss[$i],
					'price' => $price[$j]
				);
				$i++;
				$j++;
			} else if ($r < 0) { //Ошибки каталога
				$lose[] = array(
					'catalog' => $poss[$i]
				);
				$i++;
			} else if ($r > 0) { //Ошибки прайса
				$miss[] = array(
					'price' => $price[$j]
				);
				$j++;
			}
		}
		while ($i < $poss_len) {
				$lose[] = array(
					'catalog' => $poss[$i]
				);
				$i++;
		}
		while ($j < $price_len) {
				$miss[] = array(
					'price' => $price[$j]
				);
				$j++;
		}

		$ans = Array();
		$ans['bingo'] = $bingo;
		$ans['miss'] = $miss;
		$ans['lose'] = $lose;
		return $ans;
	}
	/**
	 * Массив дилеров в формает fd (nameInfo) с необработанными данными из Excel (data)
	 **/
	public static function getList()
	{
		return Once::exec(__FILE__.'getList', function(){
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
		});
		
	}
	public static function getRule($name) 
	{
		$rules = Load::loadJSON('~dealers.json');
		$rule = isset($rules[$name])? $rules[$name]: array();

		if (!isset($rule['start'])) $rule['start'] = 4;
		if (!isset($rule['ignore'])) $rule['ignore'] = [];
		if (!isset($rule['price'])) $rule['price'] = 'Артикул';
		if (!isset($rule['catalog'])) $rule['catalog'] = 'Артикул';

		return $rule;
	}
	public static function getKey (&$pos, $name)
	{
		//Логика в том что мы умнее производителя и знаем как ему надо называть Артикулы для позиций
		//И если надо ставим точку там, где производитель ставит запятую. Или добавляем пробел, где пробела нет.
		//Для сравнения не важно есть пробел или нет.
		//И вообще все страныые символы убираем и рассчитываем что это не приведёт к совпадению разных артикулов.
		//Символы могут выглядеть одинакого, но быть разными
		if (!isset($pos[$name])) return '';
		return Path::encode($pos[$name]);
		//return str_replace(["\n","\r"," ", "\t",'.',',','%','(',')','-','‐'], '', $pos[$name]); 
	}
	public static function initKey (&$pos, $name)
	{
		if (empty($pos[$name])) $pos[$name] = false;
		$pos['dealerorig'] = $pos[$name];
		$pos['dealerkey'] = Dealers::getKey($pos, $name);
	}
	public static function clearKey(&$pos, $key, $name) 
	{
		Dealers::initKey($pos, $name);
	}
	public static function usort(&$a, $b) 
	{
		$val = strcasecmp($a['dealerkey'], $b['dealerkey']);
		if ($val == 0) return 0;
		return $val > 0 ? 1 : -1;
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
		$rule = Dealers::getRule($name);
		
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