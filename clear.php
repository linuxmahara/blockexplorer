<?php include('includes/bootstrap.inc.php'); ?>
<?php include('modules/updater/updater.lib.php'); ?>
<?php include('modules/updater/util.lib.php'); ?>
<?php
echo "Clearing Tables";
updater_clear_all($DB);
$DB->close();
?>