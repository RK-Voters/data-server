<?php

  // SERVER CONFIG
  $cwd = getcwd();

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



  // CREATE GLOBAL DATABASE OBJECT
  include("models/db-rk_mysql.php");
  global $rkdb;
  $rkdb = new RK_MySQL($config);


  // LOAD UTILITIES
  include("models/db-utilities.php");
