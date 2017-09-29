<?php

	set_time_limit(0);
	header('Content-Type: text/plain');

	if(!isset($_GET['rkid']) || !is_numeric($_GET['rkid'])) exit("Not a valid rkid");

	$rkid = (int) $_GET['rkid'];

	// load data handler
	include("../rk-config.php");
	include("../models/model-import.php");


	$import_model = new RKVoters_ImportModel();
	$geocode_data = $import_model -> geoCodeVoter($rkid);

	exit(json_encode($geocode_data, JSON_PRETTY_PRINT));
