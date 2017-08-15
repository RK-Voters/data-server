<?php
// NATIONBUILDER STUFF
Class RKVoters_NationbuilderImportModel {

  function __construct(){
    global $rkdb;
    $this -> db = $rkdb;

    $this -> greenlight = false;

    $this -> fieldHash = array(

      // foreign primary key to nationbuilder
      "nationbuilder_id" => "nbid",

      // name
      "first_name" => "firstname",
      "last_name" => "lastname",
      "middle_name" => "middlename",
      "suffix" => "suffix",


      // core data
      "party" => "enroll",
      "born_at" => "dob", // must be cast
      "registered_at" => "datereg", // must be cast
      "sex" => "sex",
      "note" => "bio",
      "employer" => "employer",
      "occupation" => "profession",


      // email
      "email" => "email",
      "email_opt_in" => "email_opt_in", // cast as int


      // more phone stuff dynamically appended
      "phone_number" => "phone",


      // primary (registered) address
      "registered_street_number" => "stnum",
      "registered_city" => "city",
      "registered_county" => "county",
      "registered_zip5" => "zip",
      "registered_zip4" => "zip4",
      "registered_state" => "state",
      "registered_unit_number" => "unit",


      // mailing address
      "mailing_street_number" => "mailstnum",
      "mailing_city" => "mailcity",
      "mailing_county" => "mailcounty",
      "mailing_zip5" => "mailzip",
      "mailing_zip4" => "mailzip4",
      "mailing_state" => "mailstate",
      "mailing_unit_number" => "mailunit",


      // district
      "federal_district" => "cd", // cast as int
      "state_upper_district" => "sd",  // cast as int
      "state_lower_district" => "hd",  // cast as int

    );
  }

  function importVoterFile($voter_file, $fieldsToDisplay = array()){
    $options = array(
      // "fieldsToDisplay" => array("election_at", "ballot_vote_method"),
      //  "maxRows" => 4
    );
    runFileLineByLine($voter_file, $this, "writeVoterToDatabase", $options);

  }

  function writeVoterToDatabase($nb_row){

    $rkvoter_row = array();

    if(!isset($this -> campaignId)) exit("No campaign id set.");

    $rkvoter_row["campaignId"] = $this -> campaignId;


    // do an initial pass, and set direct match fields
    foreach($nb_row as $nb_field => $v){

      if(!isset($this -> fieldHash[trim($nb_field)])){
        continue;
      }

      // use hash to rename to rk voters schema
      $rk_field = $this -> fieldHash[trim($nb_field)];

      // maybe this should be set somewhere else?
      $intFields = array("email_opt_in", "cd", "sd", "hd");
      $dateFields = array("dob", "datereg");

      // format dates
      if(in_array($rk_field, $dateFields)){
        $v = date('Y-m-d', strtotime($v));
      }
      if(in_array($rk_field, $intFields)){
        $v = (int) $v;
      }


      // and add to data obj
      $rkvoter_row[$rk_field] = $v;
    }


    // and then add the fields that require logic...
    $rkvoter_row['stname']  = makeString( $nb_row,
                                array("registered_street_prefix", "registered_street_name",
                                      "registered_street_type", "registered_street_suffix"));

    $rkvoter_row['mailstname']  = makeString( $nb_row,
                                    array("mailing_street_prefix", "mailing_street_name",
                                          "mailing_street_type", "mailing_street_suffix"));


    if(isset($nb_row['mobile_number']) && $nb_row['mobile_number'] != ''){
      $rkvoter_row['phone2'] = $nb_row['mobile_number'];
      $rkvoter_row['phone2Type'] = "mobile";
    }

    else if(isset($nb_row['work_phone_number']) && $nb_row['work_phone_number'] != ''){
      $rkvoter_row['phone2'] = $nb_row['work_phone_number'];
      $rkvoter_row['phone2Type'] = "work";
    }


    // and write to the database
    $nbid = $rkvoter_row['nbid'];
    if(!is_numeric($nbid)) exit("Bad NBID");
    $nbid = (int) $nbid;

    if($this -> greenlight){
        $this -> db -> updateOrCreate('voters', $rkvoter_row, array('nbid' => $nbid));
    }
    else {
      if($nbid == "2254552") $this -> greenlight = true;
    }


  }




  function importVoterHistoryFile($vh_file, $target_file){
    $options = array(
      //"fieldsToDisplay" => array("election_at", "ballot_vote_method"),
      // "maxRows" => 10
    );


    $this -> target_handle = fopen($target_file, "w");

    runFileLineByLine($vh_file, $this, "importVhRecord", $options);
  }

  function importVhRecord($vh_record){
    extract($vh_record);

    $f = false;
    switch($election_at){
      case "2016-11-08" :
        $f = "General16";
      break;

      case "2015-11-03" :
        $f = "General15";
      break;

      case "2014-11-04" :
        $f = "General14";
      break;

      case "2012-11-06" :
        $f = "General12";
      break;

      case "2016-06-14" :
        $f = "Primary16";
      break;

      case "2014-06-10" :
        $f = "Primary14";
      break;

    }

    $v = false;
    switch($ballot_vote_method){
      case "absentee" :
        $v = "A";
      break;

      case "voted":
        $v = "P";
      break;
    }

    if(!$f || !$v) return;
    if(!isset($signup_id) || !is_numeric($signup_id)) return;

    $update = array($f => $v);
    $where = array("nbid" => $signup_id);
    $sql = $this -> db -> getUpdateSQL("voters", $update, $where);

    fwrite($this -> target_handle, $sql . "\n");


  }

}
