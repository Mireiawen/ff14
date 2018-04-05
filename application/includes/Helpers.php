<?php
class Helpers
{
	public static function Smarty_XIV_Icon($params, &$smarty)
	{
		if (!is_numeric($params['id']))
		{
			return $params['id'];
		}
		
		if (!isset($params['id']))
		{
			trigger_error(sprintf(_('%s: missing or invalid parameter %s'), 'xiv_icon', 'id'), E_USER_ERROR);
			return;
		}
		
		$id = intval($params['id']);
		
		return sprintf('https://secure.xivdb.com/img/game/%06d/%06d.png', $id - $id % 1000, $id);
	}
}
