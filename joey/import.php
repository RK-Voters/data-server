<?php

	$filename = 'survey.txt';

	header('Content-Type: text/plain');


	$header = NULL;
	$data = array();

	$file_str = file_get_contents($filename);
	$flag = true;

	$rows = explode("\n", $file_str);

	foreach($rows as $k => $row){
		$fields = explode("\t", $row);

		// get field names from the first row
		if($k == 0) {

			// sanitize field names
			$headers = array();
			foreach($fields as $f){
				$headers[] = str_replace(' ', '', trim($f));
			}

			// move to next row	of data
			continue;
		}

		$row_raw = array_combine($headers, $fields);

		// process raw data as necessary
		$row = array();
		foreach($row_raw as $field => $v){
			$row[$field] = $v;

			$unique[$field][$v] = true;

			// if(in_array($field, $goodFields) && $v != ''){
		}

		$data[] = $row;

	}

	print_r($data);
	print_r($unique);

	// echo count($data);
	//
	//
	// global $wpdb;
	// foreach($data as $k => $row){
	//
	// 	$wpdb -> insert('voters', $row);
	// }

	echo "\n\n\nfinish!";
