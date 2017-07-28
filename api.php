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


include('config.php');
include('models/rkvoters-api.php');

// load API model (reads request in constructor)
$data_model = new RKVoters_Model();
$request 		= $data_model -> request;


// access token shit goes here (placeholder for now)
$data_model -> rk_campaignId = 1;


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


// handle csv export request
if(isset($request['export'])){
	header("Content-Type: text/csv");
	header("Content-Disposition: attachment; filename=\"Contacts.csv\"");

	// export emails
	if($request['export'] == 'emails'){
		$contacts = $data_model -> getContactsWithEmails();
		echo $data_model -> generate_csv($contacts);
		exit;
	}

	// export mailing list
	if($request['export'] == 'mailinglist'){
		$contacts = $data_model -> getMailingList();

		echo "Name; Address 1; Address 2 \n";
		foreach($contacts as $k => $contact){
			echo "Everybody at; " . $contact['addr1'] . '; ' . $contact['addr2'] . "\n";
		}
		exit;
	}


	// export donors
	if($request['export'] == 'donors'){
		$contacts = $data_model -> exportDonations();
		echo $data_model -> generate_csv($contacts);
		exit;
	}

}

echo json_encode(array("error" => "No API or EXPORT requested."));
