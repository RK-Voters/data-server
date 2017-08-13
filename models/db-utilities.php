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
