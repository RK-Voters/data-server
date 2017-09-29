<?php

	set_time_limit(0);
	header('Content-Type: text/plain');

	// load data handler
	include("../rk-config.php");
	include("../models/model-import.php");
	$import_model = new RKVoters_ImportModel();
	ob_start();


	// if an rkid is specified, geo-code that voter
	if(isset($_GET['rkid']) && is_numeric($_GET['rkid'])) {
		$rkid = (int) $_GET['rkid'];
		$geocode_data = $import_model -> geoCodeVoter($rkid);
		exit(json_encode($geocode_data, JSON_PRETTY_PRINT));
	}


	// if a campaignid is specified, geo-code all the remaining active voters in that campaign
	else if(isset($_GET['campaignId']) && is_numeric($_GET['campaignId'])) {
		global $rkdb;
		$campaignid =  (int) $_GET['campaignId'];

		$sql = "SELECT Count(*) FROM voters where campaignid=" . $campaignid . " AND active=1";
		$totalSize = $rkdb -> get_var($sql);


		$sql = "SELECT rkid FROM voters where campaignId=" . $campaignid . " AND active=1 AND lat=0";
		$targetList = $rkdb -> get_results($sql);

		
		foreach($targetList as $k => $voter){

			$rkid = $voter -> rkid;
			$geocode_data = $import_model -> geoCodeVoter($rkid);

			if (isset($geocode_data["addr_error"])) {
    			echo $geocode_data["addr_error"];
			}

			$sql = "SELECT Count(*) FROM voters where campaignid=" . $campaignid . " AND active=1 AND lat=0";
			$remainingSize = $rkdb -> get_var($sql);

			echo ($k + 1) . ". Mapped Voter #" . $rkid . " - " . $remainingSize . " of " . $totalSize . " remaining.\n\n";

			ob_flush(); flush();

		}
		
	}

	// else it's a dud
	else {
		exit("Please provide an rkid or a campaignid.");		
	}



