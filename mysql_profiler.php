<?php
/*
Plugin Name: MySQL Profiler
Plugin URI: http://wordpress.org/extend/plugins/mysql-profiler/
Description: Displays a list of each page's SQL queries and the functions calling them that can be searched and sorted by time, type, etc.
Author: Tom Benner
Author URI: https://github.com/tombenner
Version: 1.0
*/

require_once dirname(__FILE__).'/lib/mysql_profiler.php';

if (!defined('QUERY_CACHE_TYPE_OFF')) {
	define('QUERY_CACHE_TYPE_OFF', true);
}
if (!defined('SAVEQUERIES')) {
	define('SAVEQUERIES', true);
}
if (!defined('MP_DISPLAY_FILES')) {
	define('MP_DISPLAY_FILES', true);
}
if (!defined('MP_HIGHLIGHT_SYNTAX')) {
	define('MP_HIGHLIGHT_SYNTAX', true);
}

$profiler = new MysqlProfiler();

register_activation_hook((__FILE__), array($profiler, 'activate'));
register_deactivation_hook((__FILE__), array($profiler, 'deactivate'));
register_uninstall_hook((__FILE__), array('MysqlProfiler', 'deactivate'));
		
?>
