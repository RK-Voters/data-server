<?php

  
  // data handling methods

  // PROCESS VOTER FILE
  function updateVotersFromFile($voter_file, $campaignId){
  
    // read voter file
    $voters = _readVoterFile($voter_file, $campaignId);
    //echo count($voters) . " rows to import";

    // and update database
    foreach($voters as $k => $row){
      $rkdb -> updateOrCreate('voters', $row, array('vanid' => $row['vanid']));
    }
  }

  function _readVoterFile($voter_file, $campaignId){

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

      $row["rk_campaignid"] = $campaignId;
      

      extract($voter_raw);

      // stnum     = StreetNo . StreetNoHalf
      // stname    = StreetPrefix . StreetName . StreetType . StreetSuffix
      // unit      = AptType . AptNo
      $row["stnum"]   = $StreetNo . $StreetNoHalf;
      
      $row['stname']  = makeString( $voter_raw, 
                                    array('StreetPrefix', 'StreetName', 'StreetType', 'StreetSuffix'));
      
      $unit  =  $AptType;
      $unit .= ($unit != '' && $unit != '#') ? ' ' . $AptNo : $AptNo;
      $row['unit'] = $unit;


      $data[] = $row;
    }

    return $data;
  }



  // PROCESS CONTACTS
  function inputContactsFromFile($contacts_file, $campaignId){
    global $rkdb;
    $contacts = _readContactsFile($contacts_file, $campaignId);
    foreach($contacts as $contact){
      $rkdb -> insert("voters_contacts", $contact);
    }
  }

  function _readContactsFile($contacts_file, $campaignId){
    
    // read voter file into memory
    $contactsRaw = readFileAsCSV($contacts_file);

    $contacts = array();

    foreach($contactsRaw as $row){

      $rkid = get_rkid_from_vanid($row['Voter File VANID'], $campaignId);

      if(!$rkid) {
        echo "van id #" . $row['Voter File VANID'] . " not in database.\n\n";
        continue;
      }

      $contact = array(
        "rkid" => $rkid,
        "type" => "Door Knock",
        "status" => $row['ResultShortName'],
        "datetime" => date('Y-m-d', strtotime($row['DateCanvassed'])),
        "agent" => 1, // placeholder - joey's done everything so far
      );

      $contacts[] = $contact;

    }

    return $contacts;
  }



  // PROCESS SURVEY
  function updateVotersFromSurvey($survey_file, $campaignId){
    global $rkdb;

    $survey_responses = _readSurveyFile($survey_file, $campaignId);

    foreach($survey_responses as $response){
      
      if($response['type'] == 'Support'){
        $rkdb -> update(  "voters", 
                          array("support_level" => $response['support_level']), 
                          array("rkid" => $response['rkid']));

        $rkdb -> update(  "voters_contacts",
                          array("support_level" => $response['support_level']),
                          array("rkid" => $response['rkid'], "datetime" => $response['datetime'] . ' 00:00:00'));
      }

      if($response['type'] == 'LawnSign' && $response['support_level'] == "Yes"){
        $rkdb -> update(  "voters", 
                          array("LawnSign" => "1"), 
                          array("rkid" => $response['rkid']));

        $contact = array(
          "rkid" => $response['rkid'],
          "type" => "Sign Request",
          "datetime" => $response['datetime'],
          "support_level" => 1,
          "activistId" => 1 // placeholder - joey's done everything so far
        );


        $rkdb -> insert(  "voters_contacts", $contact);
                          
      }

    }
  }

  function _readSurveyFile($survey_file, $campaignId){
    
    // read survey file into memory
    $surveyRaw = readFileAsCSV($survey_file);
    $survey_responses = array();

    foreach($surveyRaw as $row){
      
      $rkid = get_rkid_from_vanid($row['Voter File VANID'], $campaignId);

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
  function get_rkid_from_vanid($vanid, $campaignId){
    global $rkdb;
    $sql = 'SELECT rkid from voters where vanid=' . (int) $vanid . ' and rk_campaignid=' . (int) $campaignId;
    return $rkdb -> get_var($sql);
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


