<?php
// Define library path
define('LIBRARY_PATH', realpath(SYSTEM_PATH . '/../libraries'));

// Load composer autoloader
require_once(LIBRARY_PATH . '/vendor/autoload.php');

// Load the XIVDB icon helper function for Smarty
require_once(MODEL_PATH . '/Helpers.php');
\System\SmartyInstance::Get() -> registerPlugin('function', 'xiv_icon', array('Helpers', 'Smarty_XIV_Icon'));
