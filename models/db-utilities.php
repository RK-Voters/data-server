<?php

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

  function runFileLineByLine($file, $model, $callback, $options = array()){

    extract($options);

    $handle = fopen($file, "r");


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

        if(isset($maxRows) && $i == $maxRows) exit();

        // any interesting fields to track???
        if(isset($fieldsToDisplay)){
          if(!is_array($fieldsToDisplay)) exit("fields to display must be an array");
          foreach($fieldsToDisplay as $f){
             if($rowData[$f] != '' && $rowData[$f] != "false") echo $f . ": " . $rowData[$f] . "\n";
          }
        }
        $model -> $callback($rowData);
      }
      fclose($handle);
    } else {

      echo "file not found";

      exit("unable to load the data file: " . $voter_file);
    }
  }
