<?php

  // SERVER CONFIG
  $cwd = getcwd();
  global $config;

  if(strpos($cwd, "/var/www/html") !== false) {
    $config = array(
     "servername" => "localhost",
     "username" => "rkvotersdb",
     "password" => "s3r3nity",
     "database" => "rkvotersdb"
   );
  }

  else {
    $config = array(
      "servername" => "localhost",
      "username" => "root",
      "password" => "root",
      "database" => "rkvoters_data"
    );
  }


  $config['googlemaps_apikey'] = '%20AIzaSyCZlSd7CYYktdeZIeELO0dmIZfp-Ca5vZA';


  // CREATE GLOBAL DATABASE OBJECT
  include("models/db-rk_mysql.php");
  global $rkdb;
  $rkdb = new RK_MySQL($config);


  // LOAD UTILITIES
  include("models/db-utilities.php");
