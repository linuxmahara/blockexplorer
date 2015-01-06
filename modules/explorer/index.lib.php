<?php

/**
 * Returns the equivalent of Apache's $_SERVER['REQUEST_URI'] variable.
 *
 * Because $_SERVER['REQUEST_URI'] is only available on Apache, we generate an
 * equivalent using other environment variables.
 */
function request_uri() {
  if (isset($_SERVER['REQUEST_URI'])) {
	$uri = $_SERVER['REQUEST_URI'];
  }
  else {
	if (isset($_SERVER['argv'])) {
	  $uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['argv'][0];
	}
	elseif (isset($_SERVER['QUERY_STRING'])) {
	  $uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'];
	}
	else {
	  $uri = $_SERVER['SCRIPT_NAME'];
	}
  }
  // Prevent multiple slashes to avoid cross site requests via the Form API.
  $uri = '/' . ltrim($uri, '/');

  return $uri;
}



function route() {
	$req = new Request();
	switch($req->app) {
		case "stats":
		case "explore":

			require "includes/app_{$req->app}.inc";
			call_user_func("app_" . $req->app, $req);

			break;

		default:
			die("unknown app: " . $req->app);
	}
}

?>