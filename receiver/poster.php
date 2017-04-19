<?php

require "twitteroauth/autoload.php";
require "keys.php";

use Abraham\TwitterOAuth\TwitterOAuth;

function decodeEmotion($emotion){

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


$images = scandir("./images/");

$filename = false;

foreach($images as $image){
	if(strpos($image, '.jpg') !== false){
		$filename = $image;
		break;
	}
}

if($filename){

	$e = substr($filename, 10, strpos($filename, ".jpg")-10);

	$e = decodeEmotion($e);
	
	$connection = new TwitterOAuth($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
	
	$media1 = $connection->upload('media/upload', ['media' => "./images/" . $filename]);
	
	$parameters = [
		'status' => $e,
		'media_ids' => $media1->media_id_string,
	];
	
	$status = $connection->post("statuses/update", $parameters);
	
	if(!rename("./images/" . $filename, "./posted/" . $filename)){
		echo "File action error.";
		exit();
	}
	
	echo $filename . " posted.";

} else {
	echo "No files queued.";
}


?>