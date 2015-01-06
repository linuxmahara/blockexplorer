<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
 
require_once('libraries/FirePHPCore/fb.php');
@include_once("system/multisite.inc.php"); //Enables Multisite Capabilites
require_once("system/app.conf.php"); //Application Constants
@include_once("config/local.conf.php"); //User Made Configuration Options

//Autoload classes
spl_autoload_register(function ($class) {
	include 'classes/' . $class . '.class.php';
});

//Check if database is installed
if(is_file(APPLICATION_CONFDIR . 'db.conf.php')) {
	include('includes/database.inc.php');	
}
else {
	//No database configuration found, redirect to installer
	header('Location: install.php');
	exit;
}

//Check if coin is installed
if(is_file(APPLICATION_CONFDIR . 'coin.conf.php')) {
	include('includes/coin.inc.php');	
}
else {
	//No coin configuration found, redirect to installer
	header('Location: install.php');
	exit;
}

//Check if explorer is installed
if(is_file(APPLICATION_CONFDIR . 'explorer.conf.php')) {
	include('includes/explorer.inc.php');	
}
else {
	//No explorer configuration found, redirect to installer
	header('Location: install.php');
	exit;
}

//Load Language
include('includes/lang.inc.php');

//Load Settings
include('includes/settings.inc.php');

//Setup Theme
include('includes/theme.inc.php');
?>