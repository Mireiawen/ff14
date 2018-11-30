<?php
namespace Page;

require_once(MODEL_PATH . '/Eorzea.php');
require_once(MODEL_PATH . '/Weather.php');
require_once(MODEL_PATH . '/Zone.php');
require_once(MODEL_PATH . '/ZoneWeather.php');

class Skywatcher extends \System\Base implements \System\Page
{
	use \System\SmartyTemplates;
	public function __construct()
	{
		parent::__construct(TRUE);
		$this -> template = FALSE;
		$this -> html = TRUE;
	}
	
	public function Handles($params)
	{
		if (count($params))
		{
			// Wrong number of parameters
			return FALSE;
		}
		else
		{
			// Make sure we have a template to show
			$this -> template = $this -> CreateTpl('Skywatcher.tpl.html');
			if ($this -> template === FALSE)
			{
				// Not found, try showing error page instead
				return '/Errors/404';
			}
		}
		
		// We handle all the other cases
		return TRUE;
	}
	
	public function Show()
	{
		// Assign the page title
		\System\SmartyInstance::Get() -> assign('title', _('Skywatcher'));
		
		// Assign the data
		$this -> template -> assign('regions', \Region::GetAll());
		
		// And show the page
		$this -> template -> display();
		return $this -> html;
	}
	
	public function GetRequiredAccess()
	{
		return array(USER_GROUP_NONE);
	}
	
	protected function ValidateInput($data, $key, $fieldname, $helptext, $filter, $opts = FALSE)
	{
		// Make sure the key exists in the data
		if (!isset($data[$key]))
		{
			throw new \Exception(sprintf(_('Invalid input detected, the value for %s is missing'), $fieldname));
		}
		
		// Validate the variable
		if ($opts === FALSE)
		{
			$v = filter_var($data[$key], $filter);
		}
		else
		{
			$v = filter_var($data[$key], $filter, $opts);
		}
		
		// Check validation
		if ($v === FALSE)
		{
			throw new \Exception(sprintf(_('Invalid value for %s: %s'), $fieldname, $helptext));
		}
		
		return $v;
	}
}
