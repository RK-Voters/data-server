<?php


	Class RK_MySQL {

    // constructor
    function __construct($config){
      extract($config);

			// Create connection
			$this -> conn = new mysqli($servername, $username, $password, $database);
			$this -> debugMode = false;

			if ($this -> conn->connect_error) {
				die("Connection failed: " . $this -> conn -> connect_error);
			}
		}

    function run_query($sql){
			if ($this -> conn -> query($sql) !== TRUE) {
				echo "Error: " . $sql . "<br>" . $this -> conn->error;
			}
		}


    // ERROR HANDLING
    //
    // not included yet, probably should be.
    //
    // function outputError(){
		// 	// Print last SQL query string
		// 	echo $this -> wpdb->last_query;
    //
		// 	// Print last SQL query result
		// 	echo $this -> wpdb->last_result;
    //
		// 	// Print last SQL query Error
		// 	echo $this -> wpdb->last_error;
    //
		// }






    // GETTERS

    function get_var($sql){
			$result = $this -> conn -> query($sql);
			 $row = $result -> fetch_array();
			 return $row ? $row[0] : false;
		}

		function get_row($sql){
			$result = $this -> conn -> query($sql);
			return ($result) ? $result -> fetch_assoc() : array();
		}

		function get_rowFromObj($where, $table){
			foreach($where as $k => $v) $whereStrs[] = $k . '=' . $v;
			$sql = 'select * from ' . $table . ' where ' . implode(' AND ', $whereStrs);
			if($this -> debugMode) echo $sql . $this -> _linebreak();
			return $this -> get_row($sql);
		}

		function get_results($sql){
			$result = $this -> conn -> query($sql);
			if(!$result) return array();

			while($response[] = $result -> fetch_assoc());
			unset($response[count($response) -1]);
			return $response;
		}



    // SETTERS

		function update($table, $obj, $where){
			$input = (array) $obj;

			// generate sql
			$sql = 'UPDATE ' . $table;
			foreach($input as $k => $v){
				$params[] = $k . '="' . addSlashes($v) . '"';
			}
			$sql .= ' SET ' . implode(',', $params);


			foreach($where as $k => $v){
				$whereStrs[] = $k . '=' . '"' . addSlashes($v) . '"';
			}
			$sql .= ' WHERE ' . implode(' AND ', $whereStrs);

			// run query
			if($this -> debugMode) echo $sql . $this -> _linebreak();
			$this -> run_query($sql);

			// return updated object
			$sql = 'SELECT * FROM ' . $table . ' WHERE ' . implode(' AND ', $whereStrs);
			if($this -> debugMode) echo $sql . $this -> _linebreak();
			return $this -> get_row($sql);
		}

		function insert($table, $obj){
			$input = (array) $obj;
			foreach($input as $k => $v){
				$kstrs[] = $k;
				$vstrs[] = '"' . addSlashes($v) . '"';
			}
			$sql = 	'INSERT INTO ' . $table .
					' (' . implode(',', $kstrs) . ') VALUES (' . implode(',', $vstrs) . ')';

			if($this -> debugMode) echo $sql . $this -> _linebreak();


			// run query
			$this -> run_query($sql);

			// return input id
			return mysqli_insert_id($this -> conn);

		}

		function updateOrCreate($table, $update, $where){

			// look for it?
			$row = $this -> get_rowFromObj($where, $table);

			// if it's not there, add it!
			if(count($row) == 0){
				$newObject = $update;
				foreach($where as $k => $v) $newObject[$k] = $v;
				$this -> insert($newObject, $table);
			}

			// otherwise, update it
			else {
				$this -> update($update, $table, $where);
			}

		}

		function  getOrCreate($table, $obj){

			// look for it?
			$row = $this -> get_rowFromObj($obj, $table);



			// if it's there, return it!
			if(count($row) != 0) return $row;

			// otherwise, create it and return the new row
			$this -> insert($obj, $table);
			return $obj;

		}


    // delete
    function delete($table, $where){
			$sql = "DELETE FROM $table WHERE ";
			foreach($obj as $k => $v) $where[] = $k . '=' . (int) $v;
			$sql .= implode(' AND ', $where);

			//echo $sql;

			$this -> run_query($sql);
		}

  }
