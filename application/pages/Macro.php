<?php
namespace Page;

require_once(MODEL_PATH . '/Category.php');
require_once(MODEL_PATH . '/Skill.php');
require_once(MODEL_PATH . '/Macro.php');
require_once(MODEL_PATH . '/Skill.php');

// Special skill: Comfort Zone
define('COMFORT_ZONE_ID', 286);
define('COMFORT_ZONE_TICK', 8);
define('COMFORT_ZONE_DURATION', 10);

class Macro extends \System\Base implements \System\Page
{
	use \System\SmartyTemplates;
	public function __construct()
	{
		parent::__construct(TRUE);
		$this -> template = FALSE;
		$this -> html = TRUE;
		$this -> data = array
		(
			'macro_wait_skill' => \Macro::DEFAULT_WAIT_SKILL,
			'macro_wait_buff' => \Macro::DEFAULT_WAIT_BUFF,
			'macro_echo' => 0,
			'macro_name' => _('Crafting Macro'),
			'macro_end' => _('-- [%=o.name%] part [%=o.n%] done'),
			'macro_done' => _('-- [%=o.name%] complete'),
			'skills' => array(),
		);
		$this -> hash = FALSE;
	}
	
	public function Handles($params)
	{
		if (count($params) === 1)
		{
			// Load the macro script
			$p = array_pop($params);
			if ($p === 'macro.js')
			{
				// Make sure we have a template to show
				$this -> template = $this -> CreateTpl('Macro.tpl.js');
				if ($this -> template === FALSE)
				{
					// Not found, try showing error page instead
					return '/Errors/404';
				}
				$this -> html = FALSE;
				header('Content-type: application/javascript');
				return TRUE;
			}
			
			// Load a saved macro from Database
			else
			{
				try
				{
					// Try loading the macro
					$macro = \Macro::CreateByHash($p);
					$data = json_decode($macro -> GetData(), TRUE);
					if (($data === NULL) && (json_last_error() !== JSON_ERROR_NONE))
					{
						\Log::Get() -> Add
						(
							sprintf('Unable to decode JSON data "%s" [%d]: %s', 
								$macro -> GetData(), 
								json_last_error(), 
								json_last_error_msg()
							)
						);
						return '/Errors/503';
					}
					$this -> data = $data;
					$this -> hash = $macro -> GetHash();
				}
				catch (\Exception $e)
				{
					\System\Error::Message(sprintf(_('Macro definition %s was not found'), $p), ERROR_LEVEL_ERROR);
					return '/Errors/404';
				}
			}
		}
		
		if (count($params))
		{
			// Wrong number of parameters
			return FALSE;
		}
		else
		{
			// Make sure we have a template to show
			$this -> template = $this -> CreateTpl('Macro.tpl.html');
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
		\System\SmartyInstance::Get() -> assign('title', _('Macro Generator'));
		
		// Assign the data
		$this -> template -> assign('vals', $this -> data);
		$this -> template -> assign('categories', \Category::GetAll());
		$this -> template -> assign('skills', \Skill::GetAll());
		$this -> template -> assign('hash', $this -> hash);
		
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
	
	// AJAX method to save the macro
	public function Store($d)
	{
		// Verify the data
		$macro_wait_skill = $this -> ValidateInput($d, 'macro_wait_skill', _('Skill wait time'), _('Must be positive number'), FILTER_VALIDATE_INT, array('min_range' => 0, 'max_range' => 100));
		$macro = array
		(
			'macro_wait_skill' => $this -> ValidateInput($d, 'macro_wait_skill', _('Skill wait time'), _('Must be positive number'), FILTER_VALIDATE_INT, array('min_range' => 0, 'max_range' => 100)),
			'macro_wait_buff' => $this -> ValidateInput($d, 'macro_wait_buff', _('Buff wait time'), _('Must be positive number'), FILTER_VALIDATE_INT, array('min_range' => 0, 'max_range' => 100)),
			'macro_echo' => $this -> ValidateInput($d, 'macro_echo', _('Macro echo'), _('Must be on or off'), FILTER_VALIDATE_INT, array('min_range' => 0, 'max_range' => 1)),
			'macro_name' => $this -> ValidateInput($d, 'macro_name', _('Macro name'), _('Must be a string'), FILTER_SANITIZE_FULL_SPECIAL_CHARS, FALSE),
			'macro_end' => $this -> ValidateInput($d, 'macro_end', _('Macro end'), _('Must be a string'), FILTER_SANITIZE_FULL_SPECIAL_CHARS, FALSE),
			'macro_done' => $this -> ValidateInput($d, 'macro_done', _('Macro done'), _('Must be a string'), FILTER_SANITIZE_FULL_SPECIAL_CHARS, FALSE),
			'skills' => array(),
		);
		
		// Verify the skill data
		if ((!isset($d['skills'])) || (empty($d['skills'])) || (!is_array($d['skills'])))
		{
			throw new \Exception(sprintf(_('Skill list cannot be empty')));
		}
		
		foreach ($d['skills'] as $skill)
		{
			// Make sure the skill exists
			$s = \Skill::CreateByXIVDB_ID($skill);
			$macro['skills'][] = $s -> GetXIVDB_ID();
		}
		
		// Write the macro
		$m = \Macro::CreateNew();
		$m -> SetData(json_encode($macro));
		$m -> SetHash(NULL);
		$m -> Write();
		
		// Create the hash from ID
		$m -> SetHash(\ShortCode\Reversible::convert($m -> GetID(), \ShortCode\Code::FORMAT_ALNUM, 4));
		$m -> Write();
		
		return array('hash' => $m -> GetHash());
	}
}
