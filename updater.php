<?php include('includes/bootstrap.inc.php'); ?>
<?php include('modules/updater/libraries/coin.inc.php'); ?>
<?php include('modules/updater/updater.lib.php'); ?>
<?php include('modules/updater/util.lib.php'); ?>
<?php

$CONFLICT_LOG = "conflict.log";

$lockfile = fopen("/tmp/blockupdate.lock","w+");
if(!flock($lockfile,LOCK_EX | LOCK_NB)) {
	die(); //other instance running
}

//check to see if the system has been shut down
/*
$tail=`tail -n 1 $CONFLICT_LOG`;
if(preg_match("/shutdown/",$tail)!=0)
{
	die("Stop: system has been shut down");
}
*/

// Get the block count from the blockchain and the database
$blockcount = updater_getrpcblockcount($COIN_RPC);
$wehave = updater_getdbblockheight($DB);

echo "\r\n\r\n".date("r")."\r\n\r\n";

if($wehave<5) {
	$wehave=5;
}

if($blockcount <= $wehave) {
	echo "No update necessary\n";
	die();
}

$wehave = $wehave - 5;
echo "Starting block update: $wehave to $blockcount\r\n";
sleep(5);

$earliest = true;
for($current = $wehave; $blockcount >= $current; $current++){
	$blockstatus = updater_processblock($DB, $COIN_RPC, $current);
	if($blockstatus == 3 && $earliest == true) {
		logconflict("Reorg limit: system shutdown");
		die("Error: Updating blocks too far back");
	}
	$earliest = false;
}

echo "\n";
$DB->close();
?>