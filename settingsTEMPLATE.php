<?php

/*
This is a version of settings.php with all credentials and identifying information removed.
If setting up a new instace, all of the strings in all-caps will need to be repopulated,
and this file should be renamed to settings.php.
*/
	
$mysqldomain = "MYSQLDOMAIN";
$mysqluser = "MYSQLUSER";
$mysqlpass = "MYSQLPASSWORD";
$mysqldb = "MYSQLDATABASE";

//user codes
$users = array(
	"USERN1AME" => "USER1ID", // 1
	"USER2NAME" => "USER2ID", // 2
	"USER3NAME" => "USER3ID", // 3
);

// just in case.
// move a user from $users to $blocked to prevent
// their uploads from appearing on the site
$blocked = array(
	"USER4NAME" => "USER4ID", // 4
	"USER5NAME" => "USER5ID", // 5
);

$testingStr = array(
	"ox",
	"Ox",
	"oX",
	"OX",
);

$versionCodes = array(
	"CLIENTVERSIONNUMBER" => "CLIENTVERSIONID"
);

$numPerPage = 20;

$projectURL = "http://www.emoticam.net";

//path to uploads directory
$uploadsDir = "./uploads/";

//path to saved images directory
$imageDir = "./images/";

//path to tester images directory
$testerDir = "./internal/";

$dockDir = "./dock/";

function isTestingStr($checkStr, $checkArr){
	if(in_array($checkStr, $checkArr)) return true;
	
	$checkStr = strtolower($checkStr);
	$checkStr = str_replace('z', '', $checkStr);
	//echo 'string = ' . strlen($checkStr);
	if(!$checkStr) return true;
	
	return false;
}

function translateEmotion($emotion){

		$emotion = str_replace("_colon_", ":", $emotion);
		$emotion = str_replace("_dash_", "-", $emotion);
		$emotion = str_replace("_openparen_", "(", $emotion);
		$emotion = str_replace("_closeparen_", ")", $emotion);
		$emotion = str_replace("_space_", " ", $emotion);
		$emotion = str_replace("_comma_", ",", $emotion);
		$emotion = str_replace("_apostrophe_", "'", $emotion);
		$emotion = str_replace("_bar_", "|", $emotion);
		$emotion = str_replace("_backslash_", "\\", $emotion);
		$emotion = str_replace("_slash_", "/", $emotion);
		$emotion = str_replace("_greaterthan_", "&gt;", $emotion);
		$emotion = str_replace("_lessthan_", "&lt;", $emotion);
		$emotion = str_replace("_asterix_", "*", $emotion);
		$emotion = str_replace("_period_", ".", $emotion);
		$emotion = str_replace("_caret_", "^", $emotion);
		//$emotion = str_replace("_underscore_", "_", $emotion);
		
		$emotion = str_replace("_up_a", "A", $emotion);
		$emotion = str_replace("_up_b", "B", $emotion);
		$emotion = str_replace("_up_c", "C", $emotion);
		$emotion = str_replace("_up_d", "D", $emotion);
		$emotion = str_replace("_up_e", "E", $emotion);
		$emotion = str_replace("_up_f", "F", $emotion);
		$emotion = str_replace("_up_g", "G", $emotion);
		$emotion = str_replace("_up_h", "H", $emotion);
		$emotion = str_replace("_up_i", "I", $emotion);
		$emotion = str_replace("_up_j", "J", $emotion);
		$emotion = str_replace("_up_k", "K", $emotion);
		$emotion = str_replace("_up_l", "L", $emotion);
		$emotion = str_replace("_up_m", "M", $emotion);
		$emotion = str_replace("_up_n", "N", $emotion);
		$emotion = str_replace("_up_o", "O", $emotion);
		$emotion = str_replace("_up_p", "P", $emotion);
		$emotion = str_replace("_up_q", "Q", $emotion);
		$emotion = str_replace("_up_r", "R", $emotion);
		$emotion = str_replace("_up_s", "S", $emotion);
		$emotion = str_replace("_up_t", "T", $emotion);
		$emotion = str_replace("_up_u", "U", $emotion);
		$emotion = str_replace("_up_v", "V", $emotion);
		$emotion = str_replace("_up_w", "W", $emotion);
		$emotion = str_replace("_up_x", "X", $emotion);
		$emotion = str_replace("_up_y", "Y", $emotion);
		$emotion = str_replace("_up_z", "Z", $emotion);
		
		return $emotion;
}

function uploadNotification($file){
	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
	// More headers
	$headers .= 'From: <INTERNALNOTIFACATION@EMAIL.ADDRESS>' . "\r\n";
	
	mail('INTERNALNOTIFACATION@EMAIL.ADDRESS', 'Emoticam Upload', '<html><head><title>New Upload</title></head><body>http://www.emoticam.net/images/d/'.$file.'<br>http://www.emoticam.net<br><img src="http://www.emoticam.net/images/d/'.$file.'"></body></html>', $headers);
}

?>