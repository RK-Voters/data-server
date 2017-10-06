<?php

	set_time_limit(0);
	// header('Content-Type: text/plain');

	error_reporting(E_ALL);
	ini_set("display_errors", 1);

	// load data handler
	include("../rk-config.php");
	include("../models/model-van.php");


	// authentication here...

	if(isset($_GET['action']) && isset($_GET['campaignId']) && (int) $_GET['campaignId'] != 0) {

		$campaignId = (int) $_GET['campaignId'];
		
		switch($_GET['action']){
			case : "processStreets" :
				
				$import_model = new RKVoters_ImportModel();
				
				$import_model -> campaignId = $campaignId;

				$import_model -> _processStreets();
			break;
		}
	}
	else {

		// read request
		$filetype 	= (isset($_POST['uploaded_filetype'])) ? $_POST['uploaded_filetype'] : false;
		$filepath 	= (isset($_FILES['uploaded_file'])) ? $_FILES['uploaded_file']['tmp_name'] : false;


		// process api
		if($filetype && $filepath) {

			// is it a VAN File?
			$vanFileTypes = array("VAN Voter File", "VAN Contacts", "VAN Survey");
			if(in_array($filetype, $vanFileTypes)){
					$importModel = new RKVoters_VanImportModel();
			}
			else {
				$importModel = new RKVoters_NationbuilderImportModel();
			}



			// pass from primary model
			$importModel -> campaignId = $_POST['campaignId'];

			echo json_encode($importModel -> run($filetype, $filepath));

		}
	}

?>
<html>
	<head>
		<script type="text/javascript">
			function processStreets(){
				var campaignId = document.getElementById('campaignId');
				var url = "?action=processStreets&campaignId=" + campaignId;
				window.location = url;
			}
		</script>
	</head>
	<body style="text-align: center; padding-top: 100px; font-family: sans-serif; font-size: 12px; line-height: 18px;">
		<h2>Upload File</h2>

		<form action="" method="post" enctype="multipart/form-data">

			<b>Campaign Id:</b>
			<br /><input name="campaignId" id="campaignId" />

			<br /><br /><br />

			<b>File Type:</b>
			<br />
			<select name="uploaded_filetype">
				<option>VAN Voter File</option>
				<option>VAN Contacts</option>
				<option>VAN Survey</option>
			</select>

			<br /><br /><br />

			<b>File:</b>
			<br /><input type="file" name="uploaded_file" />

			<br /><br /><br />

			<input type="submit" value="IMPORT!" />

		</form>

		<a onclick="processStreets()">Process Streets!</a>


	</body>
</html>
