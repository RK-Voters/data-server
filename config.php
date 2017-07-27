<?php

  include("models/rk_mysql.php");

  $config = array(
    "servername" => "localhost",
    "username" => "root",
    "password" => "root",
    "database" => "rkvoters_data"
  );

  global $rkdb;
  $rkdb = new RK_MySQL($config);
