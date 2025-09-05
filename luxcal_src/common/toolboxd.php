<?php
/*
=== DATABASE RELATED FUNCTIONS - SQLITE ===

This file is part of the LuxCal Web Calendar.
Copyright (C) 2009-2025 LuxSoft - www.LuxSoft.eu
License http://www.gnu.org/licenses/gpl.html GPL version 3
*/

//Current LuxCal version
define("LCV","5.3.4L");

//Connect to database
function dbConnect($calID,$exitOnError=1) {
	global $dbDir;

	$fileName = "{$dbDir}{$calID}.cdb";
	if ($exitOnError and (!file_exists($fileName) or @filesize($fileName) == 0)) {
		exit("Calendar '{$dbDir}{$calID}' not found.");
	}
	try {
		$dbH = new PDO("sqlite:{$fileName}");
		$dbH->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
	}
	catch(PDOException $e) {
		if ($exitOnError) {
			logMessage('sql',1,"Database connection error: ".$e->getMessage());
			exit("Could not connect to the calendar database. See 'logs/sql.log'");
		} else {
			return false; //error
		}
	}
	return $dbH; //return db handle
}

//Query database
function dbQuery($query,$logError=1) {
global $dbH;

	try {
		$stH = $dbH->query($query);
	}
	catch (PDOException $e) {
		if ($logError) {
			logMessage('sql',1,"SQL query error: ".$e->getMessage()."\nQuery string: {$query}");
			exit("SQL error. See 'logs/sql.log'");
		} else {
			return false; //error
		}
	}
	return $stH; //result statement handle
}

//Begin / commit / roll back transaction
function dbTransaction($action,$logError=1) {
global $dbH;

	try {
		switch ($action[0]) {
			case 'b': $result = $dbH->beginTransaction(); break;
			case 'c': $result = $dbH->commit(); break;
			case 'r': $result = $dbH->rollBack();
		}
	}
	catch (PDOException $e) {
		if ($logError) {
			logMessage('sql',1,"SQL transaction error: ".$e->getMessage()."\nQuery: {$action} transaction");
			exit("SQL error. See 'logs/sql.log'");
		} else {
			return false; //error
		}
	}
	return $result;
}

//Get last inserted row ID
function dbLastRowId() {
global $dbH;

	return $dbH->lastInsertId();
}

//Prepare SQL statement 
function stPrep($query,$logError=1) {
global $dbH;

	try {
		$stH = $dbH->prepare($query);
	}
	catch (PDOException $e) {
		if ($logError) {
			logMessage('sql',1,"SQL prepare error: ".$e->getMessage()."\nQuery string: {$query}");
			exit("SQL error. See 'logs/sql.log'");
		} else {
			return false; //error
		}
	}
	return $stH; //successful
}

//Execute prepared statement 
function stExec($stH,$values,$logError=1) {
	try {
		$result = $stH->execute(!empty($values) ? $values : array());
	}
	catch (PDOException $e) {
		if ($logError) {
			logMessage('sql',1,"SQL execute error: ".$e->getMessage()."\nValues string: ".implode(',',$values));
			exit("SQL error. See 'logs/sql.log'");
		} else {
			return false; //error
		}
	}
	return $result; //successful
}

function getTableSql($table) { //get SQL code to create table
	$stH = dbQuery("SELECT `sql` FROM `sqlite_master` WHERE `type` = 'table' AND `name` = '{$table}'");
	$sqlCode = $stH->fetch(PDO::FETCH_NUM);
	$stH = null; //release statement handle!
	return $sqlCode[0];
}

function getTables($table='*') { //get array with one or all db tables
	$tableSet = $table == '*' ? "'events','categories','users','groups','settings','styles'" : "'{$table}'";
	$tables = array();
	$stH = dbQuery("SELECT `name` FROM `sqlite_master` WHERE `type` = 'table' AND `name` IN ($tableSet)"); //get table names
	while ($row = $stH->fetch(PDO::FETCH_NUM)) {
		$tables[] = $row[0]; //add table name
	}
	$stH = null; //release statement handle!
	return $tables; //array with table names
}

function getCIDs() { //get array with installed calendar IDs
	global $dbDir;
	
	$cals = array();
	$dirScan = preg_grep('~^[\w-]+\.cdb$~',scandir($dbDir));
	if ($dirScan) { //cals found
		foreach($dirScan as $entry) {
			$cals[] = substr($entry,0,-4);
		}
	}
	return $cals; //array with cal names
}

function getCals() { //get array with name and title of installed calendars
	global $dbH, $dbDir;

	$cals = array();
	$dirScan = preg_grep('~^[\w-]+\.cdb$~',scandir($dbDir));
	if ($dirScan) { //cals found
		foreach($dirScan as $entry) {
			$calID = substr($entry,0,-4);
			if ($dbH = dbConnect($calID,0)) { //connect to db and get calendar title
				if ($stH = dbQuery("SELECT `value` FROM `settings` WHERE `name` = 'calendarTitle'",0)) {
					if ($row = $stH->fetch(PDO::FETCH_NUM)) { //found
						$stH = null; //release statement handle
						$cals[$calID] = $row[0]; //add calendar name and title
					}
				}
				$dbH = null; //close db
			}
		}
	}
	return $cals;
}

function getSettings() { //get settings from database
	$set = array();
	$stH = dbQuery("SELECT `name`,`value` FROM `settings`");
	while ($row = $stH->fetch(PDO::FETCH_ASSOC)) {
		$set[$row['name']] = $row['value'];
	}
	$stH = null; //release statement handle
	return $set; //array with settings
}
?>