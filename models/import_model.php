<?php


  // data handling methods

  Class RKVoters_ImportModel {

    function __construct(){

      global $rkdb;
      $this -> db = $rkdb;

    }

    function run($filetype, $filepath){
      switch($filetype) {

  			case "Voter File" :
  				$this -> loadVoterFile($filepath);
  			break;

  			case "VAN Contacts" :
  				$this -> loadContactsFile($filepath);
  			break;

  			case "VAN Survey" :
  				$this -> loadSurveyFile($filepath);
  			break;

        default :
          exit(array("error" => "Filetype not recognized."));
  		}

      return array("Result" => "Success!");
    }


    // PROCESS VOTER FILE
    function loadVoterFile($voter_file){

      $campaignId = $this -> campaignId;

      // read voter file
      $voters = $this -> _readVoterFile($voter_file);
      //echo count($voters) . " rows to import";

      // and update database
      foreach($voters as $k => $row){
        $this -> db -> updateOrCreate('voters', $row, array('vanid' => $row['vanid']));
      }

      // once the voters are in, load the streets for them
      $this -> _processStreets($campaignId);
    }

    function _readVoterFile($voter_file){

      $campaignId = $this -> campaignId;

      // load hash file
      include("data/van_rkvoters_hash.php");

      // read voter file into memory
      $votersRaw = readFileAsCSV($voter_file);
        // array(  //"StreetNoHalf", "StreetPrefix", "StreetSuffix", "StreetType", "AptType",
        //         "General16", "General15", "General14", "General13", "General12",
        //         "Primary16", "Primary14"),
        // 'Voter File VANID');



      $data = array();


      // go through voters list
      foreach($votersRaw as $voter_raw){


        // for each field
        foreach($voter_raw as $van_field => $v){

          if(!isset($hash[trim($van_field)])){
            continue;
          }

          // use hash to rename to rk voters schema
          $rk_field = $hash[trim($van_field)];

          // format dates
          if($rk_field == "dob" || $rk_field == "datereg"){
            $v = date('Y-m-d', strtotime($v));
          }

          // and add to data obj
          $row[$rk_field] = $v;
        }

        $row["campaignId"] = $campaignId;


        extract($voter_raw);

        // If there's a street number, load the address.

        // stnum     = StreetNo . StreetNoHalf
        // stname    = StreetPrefix . StreetName . StreetType . StreetSuffix
        // unit      = AptType . AptNo
        if(isset($voter_raw['StreetNo'])){
          $row["stnum"]   = $StreetNo . $StreetNoHalf;

          $row['stname']  = makeString( $voter_raw,
                                        array('StreetPrefix', 'StreetName', 'StreetType', 'StreetSuffix'));

          $unit  =  $AptType;
          $unit .= ($unit != '' && $unit != '#') ? ' ' . $AptNo : $AptNo;
          $row['unit'] = $unit;
        }

        // IS THE PERSON "ACTIVE"?
        //
        // UPDATE VOTERS
        // SET active=1
        // WHERE General15 != "" OR General13 != ""

        $data[] = $row;
      }

      return $data;
    }

    function _processStreets(){

      $campaignId = $this -> campaignId;

      	$sql =  "SELECT DISTINCT(stname) as street_name FROM voters " .
                "WHERE campaignId=" . (int) $campaignId . " ORDER BY stname";

      	$streets = $this -> db -> get_results($sql);

      	foreach($streets as $street){
      		$s['street_name'] = $street -> street_name;
          $s['campaignId'] = $campaignId;
      		$this -> db -> getOrCreate('voters_streets', $s, $s);
      	}
    }

    function geoCodeVoter($rkid){

      $sql = "SELECT * FROM voters WHERE rkid=" . (int) $rkid;

      $voter = $this -> db -> get_row($sql);


      // if lattitude is already set, continue
      if($voter -> lat != 0) {
        return $voter;
      }

      echo "calculating";

      $address = $voter -> address1 . ", " . $voter -> city . ", " . $voter -> state . " " . $voter -> zip;

      $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' .
              urlencode($address) .
              '&key=%20AIzaSyCZlSd7CYYktdeZIeELO0dmIZfp-Ca5vZA';

      $addr_data = json_decode(file_get_contents($url));

      $location = (array) $addr_data -> results[0] -> geometry -> location;

  		$address_components = $addr_data -> results[0] -> address_components;

  		$neighborhoodName = "";
  		foreach($address_components as $c){
  			if($c -> types[0] == 'neighborhood'){
  				$neighborhoodName = $c -> long_name;
  				break;
  			}
  		}

      $update = array(
        "lat" => $location['lat'],
        "lon" => $location['lng'],
        "google_neighborhood" => $neighborhoodName
      );

      $where = array(
        "rkid" => $voter -> rkid
      );

      $this -> db -> update("voters", $update, $where);

      return $update;

    }

    function getAllVotersInCampaign(){
      $campaignId = $this -> campaignId;
      $sql = "SELECT rkid, lat from VOTERS where campaignId=" . (int) $campaignId;
      $rkids = $this -> db -> get_results($sql);
      exit(json_encode($rkids));
    }



    // PROCESS CONTACTS
    function loadContactsFile($contacts_file){
      $campaignId = $this -> campaignId;
      $contacts = $this -> _readContactsFile($contacts_file, $campaignId);
      foreach($contacts as $contact){
        $this -> db -> insert("voters_contacts", $contact);
      }
    }

    function _readContactsFile($contacts_file){

      $campaignId = $this -> campaignId;

      // read voter file into memory
      $contactsRaw = readFileAsCSV($contacts_file);

      $contacts = array();

      foreach($contactsRaw as $row){

        $rkid = $this -> get_rkid_from_vanid($row['Voter File VANID'], $campaignId);

        if(!$rkid) {
          echo "van id #" . $row['Voter File VANID'] . " not in database.\n\n";
          continue;
        }

        $contact = array(
          "rkid" => $rkid,
          "type" => "Door Knock",
          "status" => $row['ResultShortName'],
          "datetime" => date('Y-m-d', strtotime($row['DateCanvassed'])),
          "userId" => 1, // placeholder - joey's done everything so far
        );

        $contacts[] = $contact;

      }

      return $contacts;
    }



    // PROCESS SURVEY
    function loadSurveyFile($survey_file){

      $campaignId = $this -> campaignId;

      $survey_responses = $this -> _readSurveyFile($survey_file);

      foreach($survey_responses as $response){

        if($response['type'] == 'Support'){
          $this -> db -> update(  "voters",
                                  array("support_level" => $response['support_level']),
                                  array("rkid" => $response['rkid']));

          $this -> db -> update(  "voters_contacts",
                                  array("support_level" => $response['support_level']),
                                  array("rkid" => $response['rkid'], "datetime" => $response['datetime'] . ' 00:00:00'));
        }

        if($response['type'] == 'LawnSign' && $response['support_level'] == "Yes"){
          $this -> db -> update(  "voters",
                                  array("LawnSign" => "1"),
                                  array("rkid" => $response['rkid']));

          $contact = array(
            "rkid" => $response['rkid'],
            "type" => "Sign Request",
            "datetime" => $response['datetime'],
            "support_level" => 1,
            "userId" => 1 // placeholder - joey's done everything so far
          );


          $this -> db -> insert(  "voters_contacts", $contact);

        }

      }
    }

    function _readSurveyFile($survey_file){

      $campaignId = $this -> campaignId;

      // read survey file into memory
      $surveyRaw = readFileAsCSV($survey_file);
      $survey_responses = array();

      foreach($surveyRaw as $row){

        $rkid = $this -> get_rkid_from_vanid($row['Voter File VANID'], $campaignId);

        $type = "Unknown";

        if(strpos($row['SurveyQuestionLongName'], 'Support') !== false) $type = "Support";

        if(strpos($row['SurveyQuestionLongName'], 'LawnSign') !== false){
          $type = "LawnSign";
        }

        if($type == "Unknown") {
          echo "question unidentifiable:";
          print_r($row);
        }

        $response = array(
          "rkid" => $rkid,
          "type" => $type,
          "support_level" => explode(' ', $row['SurveyResponseName'])[0],
          "datetime" => date('Y-m-d', strtotime($row['DateCanvassed']))
        );

        $survey_responses[] = $response;

      }

      return $survey_responses;
    }



    // GET RK ID FROM VAN ID
    function get_rkid_from_vanid($vanid){
      $campaignId = $this -> campaignId;
      $sql = 'SELECT rkid from voters where vanid=' . (int) $vanid . ' and campaignId=' . (int) $campaignId;
      return $this -> db -> get_var($sql);
    }

  }



  // utilities
  function readFileAsCSV($filename, $unique_fields = array(), $primaryKey = ''){
    $data = array();
    $uniques = array();

    $file_str = file_get_contents($filename);
    $rows = explode("\n", $file_str);

    foreach($rows as $k => $row){
      $fields = explode("\t", $row);

      // get field names from the first row
      if($k == 0) {
        foreach($fields as $f) $headers[] = trim($f);
        continue;
      }

      if(count($fields) == 1) continue;

      $rowData = array_combine($headers, $fields);


      // compile a list of unique values ()
      foreach($unique_fields as $u){
        if($rowData[$u] != ''){
          $uniques[$u][$rowData[$u]] = $rowData[$primaryKey];
        }


      }

      $data[] = $rowData;

    }

    if(count($uniques) > 0) print_r($uniques);

    return $data;
  }


  function makeString($obj, $fields){
    $strs = array();
    foreach($fields as $f){
      if(trim($obj[$f]) != '') $strs[] = $obj[$f];
    }
    return implode(' ', $strs);
  }
