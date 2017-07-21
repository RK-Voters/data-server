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
include('rkvoters-api.php');



function rkv_api(){


	// get request (handle both json and typical http post)
	if(count($_POST) == 0){
		$request = (array) json_decode(file_get_contents('php://input'));
	}
	else {
		$request = $_POST;
	}


	// access token shit goes here


	// if we're still here, get ready to rock!
	$data_client = new RKVoters_Client();


	// process api
	if(isset($request['api'])){
		extract($request);

		$data_client -> request = $request;

		// check if specified api exists?

		$response = $data_client -> $api();
		echo json_encode($response);
		exit;
	}


	// handle csv export request
	if(isset($_GET['export'])){
		header("Content-Type: text/csv");
		header("Content-Disposition: attachment; filename=\"Contacts.csv\"");

		// export emails
		if($_GET['export'] == 'emails'){
			$contacts = $data_client -> getContactsWithEmails();
			echo $data_client -> generate_csv($contacts);
			exit;
		}

		// export mailing list
		if($_GET['export'] == 'mailinglist'){
			$contacts = $data_client -> getMailingList();

			echo "Name; Address 1; Address 2 \n";
			foreach($contacts as $k => $contact){
				echo "Everybody at; " . $contact['addr1'] . '; ' . $contact['addr2'] . "\n";
			}
			exit;
		}


		// export donors
		if($_GET['export'] == 'donors'){
			$contacts = $data_client -> exportDonations();
			echo $data_client -> generate_csv($contacts);
			exit;
		}

	}

}
