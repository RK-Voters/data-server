<?php
  set_time_limit(0);
  header('Content-Type: text/plain');
  include("rk-config.php");
  include("models/model-nationbuilder.php");
  $nbmodel = new RKVoters_NationbuilderImportModel();



  $voter_file = "data/nbvf.csv";
  $nbmodel -> campaignId = 2;
  $nbmodel -> importVoterFile($voter_file);

  
