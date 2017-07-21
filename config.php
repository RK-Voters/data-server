<?php

  include("rk_mysql.php");

  $config = array(
    "servername" => "localhost",
    "username" => "root",
    "password" => "root",
    "database" => "rkvoters"
  );

  global $rkdb;
  $rkdb = new RK_MySQL($config);
