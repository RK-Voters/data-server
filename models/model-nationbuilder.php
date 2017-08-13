<?php
// NATIONBUILDER STUFF
Class RKVoters_NationbuilderImportModel {

  function __construct(){
    global $rkdb;
    $this -> db = $rkdb;

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

// "nbvf.csv"

  function importVoterFile($voter_file, $fieldsToDisplay = array()){

    $handle = fopen($voter_file, "r");

    if ($handle) {
      $i=0;
      while (($line = fgets($handle)) !== false) {

        // if the line is blank, skip it
        if(trim($line) == '') continue;

        // read csv row as associative array
        $i++;
        $fields = str_getcsv(trim($line));
        if($i == 1) {
          foreach($fields as $f) $headers[] = trim($f);
          continue;
        }
        $rowData = array_combine($headers, $fields);

        // any interesting fields to track???
        // $fieldsToDisplay = array("is_deceased", "support_probability_score");
        // foreach($fieldsToDisplay as $f){
        //   if($rowData[$f] != '' && $rowData[$f] != "false") echo $f . ": " . $rowData[$f] . "\n";
        // }

        $rkvoter = $this -> _loadVoter($rowData);
        $this -> db -> updateOrCreate('voters', $rkvoter, array('nbid' => $rkvoter['nbid']));
      }
      fclose($handle);
    } else {

      echo "file not found";

      exit("unable to load the data file: " . $voter_file);
    }
  }


  function _loadVoter($nb_row){

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

    return $rkvoter_row;

  }
}
