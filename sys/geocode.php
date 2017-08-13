<?php

	set_time_limit(0);
	header('Content-Type: text/plain');

	$rkid = (int) $_GET['rkid'];
	if(!is_int($rkid)) exit("Not a valid rkid");

	// load data handler
	include("config.php");
	include("models/import-api.php");

	getAllVotersInCampaign(1);

	$update = geoCodeVoter($rkid);
	exit(json_encode($update));
