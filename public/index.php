<?php
// Set up paths
define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));
define('SYSTEM_PATH', realpath(dirname(__FILE__) . '/../fw'));

// Do the initialization
require_once(SYSTEM_PATH . '/Initialize.php');
