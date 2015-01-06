<?php include('includes/bootstrap.inc.php'); ?>
<?php include('modules/explorer/explorer.lib.php'); ?>
<?php include('modules/explorer/encode.lib.php'); ?>
<?php include('modules/explorer/html.lib.php'); ?>
<?php include('modules/explorer/index.lib.php'); ?>
<?php include('modules/explorer/stats.lib.php'); ?>
<?php 

if(MAINTENANCE_MODE && $_SERVER["REMOTE_ADDR"] != MAINTENANCE_MODE_ADMINIP) {
	senderror(503);
	header("Content-Type: text/plain");
	echo "Maintenance mode: Bitcoin Block Explorer will be back shortly";
	die();
}

route();

?>