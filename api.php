<?php

/**
* @package RK VOTERS
*/
/*
Plugin Name: RK VOTERS
Plugin URI: http://robkforcouncil.com/
Description: Super simple campaign management tool.
Version: 1.0.0
Author: Rob Korobkin
Author URI: http://robkorobkin.org
License: GPLv2 or later
Text Domain: crowdfolio
*/

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: X-Requested-With, content-type, access-control-allow-origin, access-control-allow-methods, access-control-allow-headers');


include('config.php');
include('models/rkvoters_model.php');

// load API model (reads request in constructor)
$data_model = new RKVoters_Model();
$request 		= $data_model -> request;


// access token shit goes here (placeholder for now)
$data_model -> campaignId = 1;





// process api
if(isset($request['api'])){
	extract($request);

	// check if specified api exists?
	if(!method_exists($data_model, $api)){
		echo json_encode(array("error" => $api . " is not a valid method."));
		exit;
	}

	$response = $data_model -> $api();
	echo json_encode($response, JSON_PRETTY_PRINT);
	exit;
}



echo json_encode(array("error" => "No API or EXPORT requested."));
