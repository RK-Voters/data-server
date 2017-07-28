<?php

  include("models/rk_mysql.php");


  $cwd = getcwd();

  if($cwd == "/var/www/html"){
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


  global $rkdb;
  $rkdb = new RK_MySQL($config);
