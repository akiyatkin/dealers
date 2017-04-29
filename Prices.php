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
			$poss[] = $pos;
			return $r;
		});

		$price = array();

		$list = Prices::getList();
		$info = $list[$dealer];
		if ($info) {
			Xlsx::runPoss($info['data'], function &(&$pos) use (&$price, $rule) {
				$r = null;
				$name = $rule['price'];
				$pos['pricekey'] = Prices::getHash($pos, $name);
				if (!$pos['pricekey']) return $r;
				
				$price[] = $pos;
				return $r;
			});
		}
		

		//usort($price, array("akiyatkin\dealers\Prices","usort"));
		//usort($poss, array("akiyatkin\dealers\Prices","usort"));
		

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

				$folder = Prices::$folder;

				$src = Path::theme($folder.$file); //Проверка что файл.
				if (!$src) return;
				$file = Path::toutf($file);
				$fd = Load::nameInfo($file);

				if (!in_array($fd['ext'], array('xlsx'))) return;
				
				//Данные из прайса Дилера
				$fd['data'] = Prices::getData($folder.$file);

				$name = $fd['name'];
				$list[$name] = $fd;

			}, scandir(Path::resolve(Prices::$folder)));
			
			/*$start = 0;
			$count = 100;
			$args = array($start, $count);
			$prods = Catalog::cache('producers.php', function ($start, $count) {
				$ans=array();

				$data=Catalog::init();
				$prods=array();
				Xlsx::runPoss($data, function &(&$pos) use (&$prods) {
					if (empty($prods[$pos['producer']])) $prods[$pos['producer']] = 0;
					$prods[$pos['producer']]++;
					$r = null; return $r;
				});
				arsort($prods, SORT_NUMERIC);
				$prods=array_slice($prods, $start, $count);
				return $prods;
			},$args,isset($_GET['re']));

			foreach($prods as $name => $count) {
				if (isset($list[$name])) continue;
				$list[$name] = Load::nameInfo($name);
				$list[$name]['data'] = array();
			}*/
			return $list;
		});
		
	}
	public static function getRule($name) 
	{
		$rules = Load::loadJSON('~dealers.json');
		$rule = isset($rules[$name])? $rules[$name]: array();

		if (!isset($rule['start'])) $rule['start'] = 1;
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
	public static function getData($src)
	{
		$fd = Load::srcInfo($src);
		$name = $fd['name'];
		$data = Xlsx::parseAll($src);
		Prices::applyRules($data, $name);
		return Xlsx::get($data, $name);
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
				if ($i >= $rule['start']) break;
				unset($data[$sheetname][$i]);
			}
		}
	}
}
