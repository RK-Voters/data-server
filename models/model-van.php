<?php
  include("model-import.php");

  Class RKVoters_VanImportModel extends RKVoters_ImportModel{

    function __construct(){

      global $rkdb;
      $this -> db = $rkdb;

      // van fields >> rk fields
      $this -> fieldHash = array(

        "Voter File VANID" => "vanid",

        "LastName" => "lastname",
        "FirstName" => "firstname",
        "MiddleName" => "middlename",
        "Suffix" => "suffix",

        "City" => "city",
        "State" => "state",
        "Zip5" => "zip",
        "Zip4" => "zip4",


        "mCity" => "mailcity",
        "mState" => "mailstate",
        "mZip5" => "mailzip",
        "mZip4" => "mailzip4",

        "Party" => "enroll",
        "DOB" => "dob", // needs to be cast
        "PreferredEmail" => "email",
        "Preferred Phone" => "phone",
        "Sex" => "sex",

        "CD" => "cd",
        "SD" => "sd",
        "HD" => "hd",

        "DateReg" => "datereg", // needs to be cast

        "General16" => "General16",
        "General15" => "General15",
        "General14" => "General14",
        "General13" => "General13",
        "General12" => "General12",
        "Primary16" => "Primary16",
        "Primary14" => "Primary14",

        "NoteText"  => "Bio"

      );

    }

    function run($filetype, $filepath){
      switch($filetype) {

        case "VAN Voter File" :
          $this -> importVoterFile($filepath);
        break;

        case "VAN Contacts" :
          $this -> importContactsFile($filepath);
        break;

        case "VAN Survey" :
          $this -> importSurveyFile($filepath);
        break;

        default :
          exit(json_encode(array("error" => "Filetype not recognized.")));
      }

      return array("Result" => "Success!");
    }

    // PROCESS VOTER FILE
    function importVoterFile($voter_file){

      $campaignId = $this -> campaignId;

      // read voter file
      $voters = $this -> _readVoterFile($voter_file);
      //echo count($voters) . " rows to import";

      // and update database
      foreach($voters as $k => $row){
        $this -> db -> updateOrCreate('voters', $row, array('vanid' => $row['vanid']));
      }

      // once the voters are in, import the streets for them
      $this -> _processStreets($campaignId);
    }

    function _readVoterFile($voter_file){

      $campaignId = $this -> campaignId;


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

          if(!isset($this-> fieldHash[trim($van_field)])){
            continue;
          }

          // use hash to rename to rk voters schema
          $rk_field = $this -> fieldHash[trim($van_field)];

          // format dates
          if($rk_field == "dob" || $rk_field == "datereg"){
            $v = date('Y-m-d', strtotime($v));
          }

          // and add to data obj
          $row[$rk_field] = $v;
        }

        $row["campaignId"] = $campaignId;


        extract($voter_raw);

        // If there's a street number, import the address.

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



    // PROCESS CONTACTS
    function importContactsFile($contacts_file){
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
    function importSurveyFile($survey_file){

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
