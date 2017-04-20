<?php
namespace akiyatkin\dealers;


class Dealers {
	public static function applyRules(&$data)
	{
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