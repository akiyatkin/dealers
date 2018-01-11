<?php
namespace akiyatkin\prices;
use infrajs\excel\Xlsx;
use infrajs\ans\Ans;
use infrajs\path\Path;
use infrajs\once\Once;
use infrajs\load\Load;
use infrajs\catalog\check\Check;
use infrajs\catalog\Catalog;
use infrajs\each\Each;
use infrajs\template\Template;

class Prices {
	public static $conf = array();
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
	public static function getHash(&$pos, $name, $price)
	{
		$hash = Template::parse(array($name), $pos);
		$hash = preg_replace('/^'.$price.'\-/i', '', $hash);
		return $hash;
	}
	public static function checkSynonyms(&$pos, $rule) 
	{
		if (isset($rule['synonyms'])) {
			foreach ($rule['synonyms'] as $val => $vals) {
				if (!empty($pos[$val])) continue;
				Each::exec($vals, function &($syn) use (&$pos, $val) {
					$r = null;
					if (empty($pos[$syn])) return $r;
					$pos[$val] = $pos[$syn];
					$r = false;
					return $r;
				});
			}
		}
	}
	public static function init($price) 
	{
		return Catalog::cache(__FILE__, function ($price) {
			$data = Catalog::init();
			$rule = Prices::getRule($price);
			$poss = array();

			$doublescat = array();

			$counts = array();
			$counts['price'] = 0;
			$counts['empty'] =array();
			$counts['empty']['price'] = 0;
			$counts['empty']['catalog'] = 0;
			$counts['catalog'] = 0;
			$counts['images'] = 0;
			$counts['noimages'] = 0;
			$counts['cost'] = 0;
			$counts['nocost'] = 0;
			$counts['doubles'] = array();
			$counts['doubles']['price'] = 0;
			$counts['doubles']['catalog'] = 0;
			$counts['doubles']['article'] = 0;

			
			Xlsx::runPoss($data, function &($pos) use (&$poss, $price, $rule, &$counts, &$doublescat) {
				$r = null;
				if ($pos['producer'] != $price) return $r;

				$name = $rule['catalog'];
				$pos['pricekey'] = Prices::getHash($pos, $name, $price);
				

				$pos = Catalog::getPos($pos);
				if (empty($pos['images'])) $counts['noimages']++;
				else $counts['images']++;
				
				if (empty($pos['Цена'])) $counts['nocost']++;
				else $counts['cost']++;

				$counts['catalog']++;

				if (!$pos['pricekey']) {
					$counts['empty']['catalog']++;
					return $r;
				}

				if (isset($poss[$pos['pricekey']])) {
					if (!isset($doublescat[$pos['pricekey']])) {
						$doublescat[$pos['pricekey']] = array();
						$doublescat[$pos['pricekey']][] = $poss[$pos['pricekey']];
					}
					$counts['doubles']['catalog']++;
					$doublescat[$pos['pricekey']][] = $pos;

				}
				$poss[$pos['pricekey']] = array('catalog'=>$pos);
				return $r;
			});


			$list = Prices::getList();
			if (!isset($list[$price])) $list[$price] = array();
			$info = $list[$price];
			$losecat = array();
			$bingo = array();
			$losepr = array();
			$doublespr = array();
			
			if ($info) {
				Xlsx::runPoss($info['data'], function &(&$pos) use ($rule, &$poss, &$bingo, &$losecat, $price, &$counts,&$doublespr) {
					$r = null;
					$name = $rule['price'];
					
					Prices::checkSynonyms($pos, $rule);

					
					$pos['pricekey'] = Prices::getHash($pos, $name, $price);
					if (isset($rule['ignoreart'])) {
						if(in_array($pos['pricekey'], $rule['ignoreart'])) {
							return $r;
						}
					}

					$counts['price']++;
					if (!$pos['pricekey']) {
						$counts['empty']['price']++;
						return $r;
					}

					

					if(empty($doublespr[$pos['pricekey']])) $doublespr[$pos['pricekey']] = array();
					$doublespr[$pos['pricekey']][] = $pos;

					if (sizeof($doublespr[$pos['pricekey']]) > 1) return $r;

					if (isset($poss[$pos['pricekey']])) {
						$pos['finded'] = true;
						$bingo[] = array(
							'catalog' => $poss[$pos['pricekey']]['catalog'],
							'price' => $pos
						);
						unset($poss[$pos['pricekey']]);
					} else {
						$losecat[] = array(
							'price' => $pos
						);
					}
					return $r;
				});
				foreach($doublespr as $k => $v) {
					if(sizeof($v) < 2) unset($doublespr[$k]);
					else $counts['doubles']['price'] += sizeof($v)-1;
				}
			}
			$losepr = array_values($poss);
			
			//$counts['catalog2'] = Prices::getCount($price);	

			$ans = Array();
			$ans['bingo'] = $bingo;
			$ans['losecat'] = $losecat;

			$ans['counts'] = $counts;

			$ans['doublescat'] = $doublescat;
			$ans['doublespr'] = $doublespr;
			$ans['losepr'] = $losepr;
			$ans['price'] = $price;


			$ans['class'] = 'danger';
			if (!sizeof($ans['losecat']) && sizeof($ans['bingo'])) {
				$ans['class'] = 'warning';
				if (!$ans['counts']['doubles']['catalog']
					&& !$ans['counts']['doubles']['article']
					&& !$ans['counts']['noimages']
					) {
					$ans['class'] = 'info';
					if (!$ans['counts']['doubles']['price']
						//&& !sizeof($ans['losepr'])
						&& !$ans['counts']['nocost']
						//&& !$ans['counts']['empty']['price']
						//&& !$ans['counts']['empty']['catalog']
						) {
						$ans['class'] = 'success';
					}
				}
			}
			
			
			$res = Check::repeats();
			$repeats = 0;
			if (!empty($res['list'][$price])) {
				$repeats = sizeof($res['list'][$price]);
			}
			$ans['counts']['doubles']['article'] = $repeats;
			$ans['time'] = time();
			return $ans;
		}, array($price), isset($_GET['re']));
	}
	public static function getCount($price) {
		return Catalog::cache(__FILE__.'getCount', function ($price) {
			//Создали метку, установили ключ и получили данные
			$mark = Catalog::getDefaultMark();
			$mark->setVal(':producer::.'.$price.'=1');
			$md = $mark->getData();
			$list = Catalog::search($md);
			$count = $list['count'];
			return $count;
		}, array($price));
		
	}
	/**
	 * Массив дилеров в формает fd (nameInfo) с необработанными данными из Excel (data)
	 **/
	public static function getList()
	{
		return Once::exec(__FILE__.'getList', function () {
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
			}
		}

		if (isset($rule['merge'])) {
			/* ВОсстаноавливаем значение объеинённых ячеек по высоте в одну строку
			|  !!!	|  !!!	|		|
			|		|		|!!!|!!!|
			*/
			foreach ($data as $sheetname => $sheet) {
				foreach ($sheet as $index => $row) {
					if (sizeof($row)>2) {
						$data[$sheetname][$index+1] = $data[$sheetname][$index+1] + $data[$sheetname][$index];
						ksort($data[$sheetname][$index+1]);
						unset($data[$sheetname][$index]);
						break;
					}
				}
			}
		}

		foreach ($data as $sheetname => $sheet) {
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
