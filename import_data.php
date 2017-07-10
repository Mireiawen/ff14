<?php
/*!
 * @brief Script that fetches data from XIVDB.com via their web API
 */
ini_set('display_errors', 'on');

// Set up paths
define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/application'));
define('SYSTEM_PATH', realpath(dirname(__FILE__) . '/fw'));
define('LOAD_CONTENT', FALSE);

// Which parts to run
define('CREATE_CATEGORIES', TRUE);
define('REQUEST_JOBS', TRUE);
define('REQUEST_CRAFTER_SKILLS', TRUE);

// Uncomment this to not touch the database
#define('DRY_RUN', TRUE);

// Do the initialization
require_once(SYSTEM_PATH . '/Initialize.php');

// Load the models
require_once(MODEL_PATH . '/cURL.php');
require_once(MODEL_PATH . '/Category.php');
require_once(MODEL_PATH . '/Job.php');
require_once(MODEL_PATH . '/Skill.php');

// Knowledge about the skill
define('SKILL_CATEGORY_QUALITY', 'Increases quality.');
define('SKILL_CATEGORY_PROGRESS', 'Increases progress.');
define('SKILL_CATEGORY_SPECIALIST', 'Specialist Action');

// Function to assign category by skill ID
function get_category_by_xivdb_id($id)
{
	switch ($id)
	{
	case	100039:	// Piece by Piece
	case	100083:	// Flawless Synthesis
	case	100136: // Muscle Memory
		return \Category::PROGRESS;
		
	case	100108:	// Hasty Touch
	case	100227:	// Prudent Touch
		return \Category::QUALITY;
		
	case	244:	// Steady Hand
	case	281:	// Steady Hand II
	case	252:	// Inner Quiet
	case	260:	// Great Strides
	case	277:	// Ingenuity
	case	283:	// Ingenuity II
	case	279:	// Waste Not
	case	285:	// Waste Not II
	case	284:	// Innovation
	case	287:	// Reclaim
	case	4568:	// Name of the Wind
	case	4569:	// Name of the Fire
	case	4570:	// Name of the Ice
	case	4571:	// Name of the Earth
	case	4572:	// Name of the Lightning
	case	4573:	// Name of the Water
	case	100010:	// Observe
	case	100178:	// Maker's Mark
	case	100251:	// Initial Preparations
		return \Category::BUFF;
		
	case	276:	// Rumination
	case	286:	// Comfort Zone
	case	100098:	// Tricks of the Trade
		return \Category::RESTORE_CP;
		
	case	278:	// Manipulation
	case	4574:	// Manipulation II
	case	100003:	// Master's Mend
	case	100005:	// Master's Mend II
		return \Category::RESTORE_DURABILITY;
		
	default:
		return \Category::OTHER;
	}
}

// Function to grab JSON data from Garland Tools database
function garland_json($path)
{
	// Get the class/job information
	$url = 'http://www.garlandtools.org/db' . $path;
	$c = new cURL();
	$c -> SetoptArray
	(
		array
		(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_CONNECTTIMEOUT => 5,
		)
	);
	
	// Garland Tools has BOM....
	$r = $c -> Exec();
	$bom = pack('H*','EFBBBF');
	$r = preg_replace('/^' . $bom. '/', '', $r);
	$result = json_decode($r, TRUE);
	if ($result === NULL)
	{
		if (json_last_error() !== JSON_ERROR_NONE)
		{
			throw new Exception(sprintf(_('API error %d: %s'), json_last_error(), json_last_error_msg()));
		}
	}
	
	$info = $c -> GetInfo();
	$c -> Close();
	
	// Check the HTTP result code
	if ($info['http_code'] !== 200)
	{
		throw new Exception(sprintf(_('API error %d'), $info['http_code']));
	}
	
	return $result;
}		

try
{
	echo _('XIVDB.com data fetcher') , "\n";
	
	// Get current database connection
	$db = Database::Get();
	if ($db === FALSE)
	{
		throw new Exception(sprintf(_('Unable to execute database query: %s'), _('No database')));
	}
	
	$db -> foreign_key_checks(FALSE);
	
	if ((defined('CREATE_CATEGORIES')) && (CREATE_CATEGORIES))
	{
		echo _('Creating categories') , "\n";
		
		// Truncate the category data
		if ((!defined('DRY_RUN')) || (DRY_RUN === FALSE))
		{
			$db -> truncate('Category');
		}
		
		// Create the categories
		$c = \Category::CreateNew();
		$c -> SetName('Progress');
		if ((!defined('DRY_RUN')) || (DRY_RUN === FALSE))
		{
			$c -> Write();
		}
		
		$c = \Category::CreateNew();
		$c -> SetName('Quality');
		if ((!defined('DRY_RUN')) || (DRY_RUN === FALSE))
		{
			$c -> Write();
		}
		
		$c = \Category::CreateNew();
		$c -> SetName('Buff');
		if ((!defined('DRY_RUN')) || (DRY_RUN === FALSE))
		{
			$c -> Write();
		}
		
		$c = \Category::CreateNew();
		$c -> SetName('Restore CP');
		if ((!defined('DRY_RUN')) || (DRY_RUN === FALSE))
		{
			$c -> Write();
		}
		
		$c = \Category::CreateNew();
		$c -> SetName('Restore Durability');
		if ((!defined('DRY_RUN')) || (DRY_RUN === FALSE))
		{
			$c -> Write();
		}
		
		$c = \Category::CreateNew();
		$c -> SetName('Specialist');
		if ((!defined('DRY_RUN')) || (DRY_RUN === FALSE))
		{
			$c -> Write();
		}
		
		$c = \Category::CreateNew();
		$c -> SetName('Other');
		if ((!defined('DRY_RUN')) || (DRY_RUN === FALSE))
		{
			$c -> Write();
		}
	}
	
	if ((defined('REQUEST_JOBS')) && (REQUEST_JOBS))
	{
		echo _('Getting class and job information') , "\n";
		
		// Truncate the data
		if ((!defined('DRY_RUN')) || (DRY_RUN === FALSE))
		{
			$db -> truncate('Job');
		}
		
		// Get the class/job information
		$url = 'https://api.xivdb.com/data/classjobs';
		$c = new cURL();
		$c -> SetoptArray
		(
			array
			(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_CONNECTTIMEOUT => 5,
			)
		);
		$result = json_decode($c -> Exec(), TRUE);
		$info = $c -> GetInfo();
		$c -> Close();
		
		// Check the HTTP result code
		if ($info['http_code'] !== 200)
		{
			throw new Exception(sprintf(_('API error %d'), $info['http_code']));
		}
		
		// Check for error message
		if (isset($result['error']))
		{
			throw new Exception(sprintf(_('API error: %s'), $result['error']));
		}
		
		// Create the SQL query
		$sql = sprintf(
			'INSERT INTO %s (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			$db -> escape_identifier('Job'),
			$db -> escape_identifier('XIVDB_ID'),
			$db -> escape_identifier('Icon'),
			$db -> escape_identifier('Name_EN'),
			$db -> escape_identifier('Name_JP'),
			$db -> escape_identifier('Name_DE'),
			$db -> escape_identifier('Name_FR'),
			$db -> escape_identifier('Abbr_EN'),
			$db -> escape_identifier('Abbr_JP'),
			$db -> escape_identifier('Abbr_DE'),
			$db -> escape_identifier('Abbr_FR'));
		$stmt = $db -> prepare($sql);
		if ($stmt === FALSE)
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}
		
		// Bind the parameters
		$xivdb_id = 0;
		$icon = '';
		$name_en = '';
		$name_jp = '';
		$name_de = '';
		$name_fr = '';
		$abbr_en = '';
		$abbr_jp = '';
		$abbr_de = '';
		$abbr_fr = '';
		if (!$stmt -> bind_param('isssssssss', $xivdb_id, $icon, $name_en, $name_jp, $name_de, $name_fr, $abbr_en, $abbr_jp, $abbr_de, $abbr_fr))
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Go through the data
		foreach ($result as $row)
		{
			$xivdb_id = $row['id'];
			$icon = $row['icon'];
			$name_en = $row['name_en'];
			$name_jp = $row['name_ja'];
			$name_de = $row['name_de'];
			$name_fr = $row['name_fr'];
			$abbr_en = $row['abbr_en'];
			$abbr_jp = $row['abbr_ja'];
			$abbr_de = $row['abbr_de'];
			$abbr_fr = $row['abbr_fr'];
			if ((!defined('DRY_RUN')) || (DRY_RUN === FALSE))
			{
				$stmt -> execute();
			}
		}
		
		// Close the SQL statement
		$stmt -> close();
	}
	
	if ((defined('REQUEST_CRAFTER_SKILLS')) && (REQUEST_CRAFTER_SKILLS))
	{
		echo _('Getting crafter skill information') , "\n";
		
		// Truncate the data
		if ((!defined('DRY_RUN')) || (DRY_RUN === FALSE))
		{
			$db -> truncate('Skill');
		}
		
		// Crafter classes
		$jobabbrs = explode(';', CRAFTER_JOBS);
		$jobs = array();
		foreach ($jobabbrs as $abbr)
		{
			$job = Job::CreateByAbbr_EN($abbr);
			if ($job === FALSE)
			{
				throw new Exception(sprintf(_('Unknown job %s'), $abbr));
			}
			$jobs[] = $job;
		}
		
		// Crafter skills
		$url = 'https://api.xivdb.com/action?columns=id,name_en,name_ja,name_de,name_fr,icon,cost_cp,is_in_game,is_trait,classjob';
		$c = new cURL();
		$c -> SetoptArray
		(
			array
			(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_CONNECTTIMEOUT => 5,
			)
		);
		$result = json_decode($c -> Exec(), TRUE);
		$info = $c -> GetInfo();
		$c -> Close();
		
		// Check the HTTP result code
		if ($info['http_code'] !== 200)
		{
			throw new Exception(sprintf(_('API error %d'), $info['http_code']));
		}

		// Check for error message
		if (isset($result['error']))
		{
			throw new Exception(sprintf(_('API error: %s'), $result['error']));
		}
		
		// Create the SQL query
		$sql = sprintf(
			'INSERT INTO %s (%s, %s, %s, %s, %s, %s, %s, %s, %s) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
			$db -> escape_identifier('Skill'),
			$db -> escape_identifier('XIVDB_ID'),
			$db -> escape_identifier('Name_EN'),
			$db -> escape_identifier('Name_JP'),
			$db -> escape_identifier('Name_DE'),
			$db -> escape_identifier('Name_FR'),
			$db -> escape_identifier('Category'),
			$db -> escape_identifier('Icon'),
			$db -> escape_identifier('Cost'),
			$db -> escape_identifier('Buff'));
		$stmt = $db -> prepare($sql);
		if ($stmt === FALSE)
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), $db -> error));
		}
		
		// Bind the parameters
		$xivdb_id = 0;
		$name_en = '';
		$name_jp = '';
		$name_de = '';
		$name_fr = '';
		$category = 0;
		$icon = '';
		$cost = 0;
		$buff = 0;
		$job = NULL;
		$level = 1;
		if (!$stmt -> bind_param('isssssiii', $xivdb_id, $name_en, $name_jp, $name_de, $name_fr, $category, $icon, $cost, $buff))
		{
			throw new Exception(sprintf(_('Unable to execute database query: %s'), $stmt -> error));
		}
		
		// Go through the data
		$added = array();
		foreach ($result as $row)
		{
			// Check if the skill is in game
			if (!$row['is_in_game'])
			{
				continue;
			}
			
			// Check if it is trait
			if ($row['is_trait'])
			{
				continue;
			}

			// Check if it is crafter action
			$job = FALSE;
			foreach ($jobs as $j)
			{
				if ($j -> GetXIVDB_ID() === $row['classjob'])
				{
					$job = $j;
					break;
				}
			}
			
			// Not crafter action, skip
			if ($job === FALSE)
			{
				continue;
			}
			
			// Check if the skill already exists, for multi-class actions
			if (array_key_exists($row['name_en'], $added))
			{
				continue;
			}
			
			// Set up the data for write
			$xivdb_id = $row['id'];
			$name_en = $row['name_en'];
			$name_jp = $row['name_ja'];
			$name_de = $row['name_de'];
			$name_fr = $row['name_fr'];
			$icon = $row['icon'];
			$cost = $row['cost_cp'];
			$buff = 1;
			$added[$row['name_en']] = TRUE;
			
			// Collectable synthesis is not usually useful in macros, ignore it
			if ($xivdb_id === 4560)
			{
				continue;
			}
			
			// Quality Assurance is not marked as trait, fix it manually here
			if ($xivdb_id === 50342)
			{
				continue;
			}
			
			// Stroke of Genius is not marked as trait, fix it manually here
			if ($xivdb_id === 50350)
			{
				continue;
			}
			
			// Fetch the buff knowledge
			// XXX TODO
			
			// Fetch the CP cost from Garland Tools, since XIVDB does not have it
			$data = garland_json('/data/action/' . $xivdb_id . '.json');
			if (isset($data['action']['cost']))
			{
				$cost = $data['action']['cost'];
			}
			
			// Category information
			$category = FALSE;
			if (isset($data['action']['desc']))
			{
				$desc = strip_tags($data['action']['desc']);
				if (strncmp($desc, SKILL_CATEGORY_QUALITY, strlen(SKILL_CATEGORY_QUALITY)) === 0)
				{
					$category = \Category::QUALITY;
				}
				
				else if (strncmp($desc, SKILL_CATEGORY_PROGRESS, strlen(SKILL_CATEGORY_PROGRESS)) === 0)
				{
					$category = \Category::PROGRESS;
				}
				
				else if (strncmp($desc, SKILL_CATEGORY_SPECIALIST, strlen(SKILL_CATEGORY_SPECIALIST)) === 0)
				{
					$category = \Category::SPECIALIST;
				}
			}
			else
			{
				$desc = '';
			}
			
			if ($category === FALSE)
			{
				$category = get_category_by_xivdb_id($xivdb_id);
				if ($category === \Category::OTHER)
				{
					echo sprintf('OTHER %d "%s" "%s"', $xivdb_id, $name_en, $desc) , "\n";
				}
			}
			
			// Execute the query
			if ((!defined('DRY_RUN')) || (DRY_RUN === FALSE))
			{
				$stmt -> execute();
			}
		}
	}
}

catch (Exception $e)
{
	echo sprintf(_('Error: %s'), $e -> getMessage()) , "\n";
}