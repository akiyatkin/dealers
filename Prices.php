<?php
namespace akiyatkin\prices;
use infrajs\excel\Xlsx;
use infrajs\ans\Ans;
use infrajs\path\Path;
use infrajs\once\Once;
use infrajs\load\Load;
use infrajs\catalog\Catalog;
use infrajs\template\Template;

class Prices {
	public static $folder = '~.prices/';
	/*public static function getKey(&$pos, $name)
	{
		//Логика в том что мы умнее производителя и знаем как ему надо называть Артикулы для позиций
		//И если надо ставим точку там, где производитель ставит запятую. Или добавляем пробел, где пробела нет.
		//Для сравнения не важно есть пробел или нет.
		//И вообще все страныые символы убираем и рассчитываем что это не приведёт к совпадению разных артикулов.
		//Символы могут выглядеть одинакого, но быть разными
		if (!isset($pos[$name])) return '';
		return Path::encode($pos[$name]);
		//return str_replace(["\n","\r"," ", "\t",'.',',','%','(',')','-','‐'], '', $pos[$name]); 
	}*/
	public static function getHash(&$pos, $name)
	{
		$hash = Template::parse(array($name),$pos);
		return $hash;
	}
	public static function init($dealer) 
	{
		
		$data = Catalog::init();

		
		$rule = Prices::getRule($dealer);
		$poss = array();
		Xlsx::runPoss($data, function &($pos) use (&$poss, $dealer, $rule) {
			$r = null;
			if ($pos['Производитель'] != $dealer) return $r;
			
			$name = $rule['catalog'];
			$pos['pricekey'] = Prices::getHash($pos, $name);
			
			if (!$pos['pricekey']) $pos[$name] = 'Нет ключа синхронизации '.$pos['article'];
			
			$pos = Catalog::getPos($pos);
			$poss[$pos['pricekey']] = array('catalog'=>$pos);
			return $r;
		});


		$list = Prices::getList();
		$info = $list[$dealer];
		$miss = array();
		$bingo = array();
		$lose = array();
		if ($info) {
			Xlsx::runPoss($info['data'], function &(&$pos) use ($rule, &$poss, &$bingo, &$miss) {
				$r = null;
				$name = $rule['price'];
				$pos['pricekey'] = Prices::getHash($pos, $name);
				if (!$pos['pricekey']) return $r;

				if (isset($poss[$pos['pricekey']])) {
					$bingo[] = array(
						'catalog' => $poss[$pos['pricekey']]['catalog'],
						'price' => $pos
					);
					unset($poss[$pos['pricekey']]);
				} else {
					$miss[] = array(
						'price' => $pos
					);
				}
				return $r;
			});
		}
		$lose = array_values($poss);
		
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

				$folder = Prices::$folder;

				$file = Path::toutf($file);
				$fd = Load::nameInfo($file);
				$name = $fd['name'];
				
				
				if (Path::theme($folder.$file)){
					if (!in_array($fd['ext'], array('xlsx'))) return;
					$fd['data'] = Prices::getData($folder.$file, $name);
				} else {
					$fd['data'] = Prices::getDirData($folder.$file.'/', $name);
				}
				//Данные из прайса Дилера
				
				

				
				$list[$name] = $fd;

			}, scandir(Path::resolve(Prices::$folder)));
			return $list;
		});
		
	}
	public static function getRule($name) 
	{
		$rules = Load::loadJSON('~prices.json');
		$rule = isset($rules[$name])? $rules[$name]: array();

		if (!isset($rule['start'])) $rule['start'] = 1;
		if (!isset($rule['head'])) $rule['head'] = false;
		if (!isset($rule['ignore'])) $rule['ignore'] = [];
		if (!isset($rule['price'])) $rule['price'] = '{Артикул}';
		if (!isset($rule['catalog'])) $rule['catalog'] = '{Артикул}';

		return $rule;
	}

	/*public static function usort(&$a, $b) 
	{
		$val = strcasecmp($a['dealerkey'], $b['dealerkey']);
		if ($val == 0) return 0;
		return $val > 0 ? 1 : -1;
	}*/
	public static function getData($src, $name)
	{	
		$data = Xlsx::parseAll($src);
		Prices::applyRules($data, $name);
		return Xlsx::get($data, $name);
	}
	public static function getDirData($src, $name)
	{
		$data = array();
		array_map(function ($file) use ($src, &$data, $name) {

			if ($file[0] == '.') return;
			if ($file[0] == '~') return;

			$file = Path::toutf($file);
			
			if (!Path::theme($src.$file)) return;
			$fd = Load::nameInfo($file);
			if (!in_array($fd['ext'], array('xlsx'))) return;

			
			$newdata = Prices::getData($src.$file, $name);
			
			if (!$data) {
				$data = $newdata;
			} else {
				$data['childs'] = array_merge($data['childs'], $newdata['childs']);
			}
		}, scandir(Path::theme($src)));
		$data['title'] = $name;
		return $data;
	}
	public static function applyRules(&$data, $name)
	{
		$rule = Prices::getRule($name);
		
		foreach ($data as $sheetname => $sheet) {
			if (in_array($sheetname, $rule['ignore'])) {
				unset($data[$sheetname]);
				continue;
			}
			foreach ($sheet as $i => $row) {
				if ($i > $rule['start']-1) break;
				unset($data[$sheetname][$i]);
			}
		}

		if ($rule['head']) {
			foreach ($data as $name => $list) {
				$head = array_shift($list);
				if (!$head) continue;
				
				foreach ($head as $i => $val) {
					$head[$i] = array_shift($rule['head']);
				}
				array_unshift($data[$name], $head);
			}
		}
	}
}
