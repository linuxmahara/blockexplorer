<?php

class AppConfig {
	static $dbname = "testexplore";
	//static $address_version = "00";
	static $address_version = "6F";
	static $rpc = array("user" => "bitcoin",
						"password" => "7AvathEBracheCra",
						"target" => "127.0.0.1",
						"port" => 8331);
}

class AppConfigTestnet {
	static $dbname = "testexplore";
	static $address_version = "6F";
	static $rpc = array("user" => "bitcoin",
						"password" => "7AvathEBracheCra",
						"target" => "127.0.0.1",
						"port" => 8331);
}

?>