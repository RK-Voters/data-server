<?php

	set_time_limit(0);
	header('Content-Type: text/plain');


	// load data handler
	include("config.php");
	include("models/import-api.php");

	$campaignId = 1;


	// load voters
	// $voter_file = 'data/joey/voters.txt';
	// updateVotersFromFile($voter_file, $campaignId);




	// load contacts
	// $contacts_file = 'data/joey/contacts.txt';
	// inputContactsFromFile($contacts_file, $campaignId);



	// load supports
	$survey_file = 'data/joey/survey.txt';
	// updateVotersFromSurvey($survey_file, $campaignId);


//	_processStreets($campaignId);

	echo getAllVotersInCampaign($campaignId);
	exit;

	geoCodeCampaign($campaignId);


	echo "\n\n\nfinish!";
