<?php
	
include_once('settings.php');
	
$addy = $_SERVER['REMOTE_ADDR'];

// valideate input
if (filter_var($addy, FILTER_VALIDATE_IP) === false) {
    exit();
}

$addy = str_replace(".", "x", $addy);
$addy = str_replace(":", "y", $addy);

$uploaddir = $dockDir.$addy.'/';
mkdir($uploaddir);

$file = basename($_FILES['uploadedfile']['name']);
$uploadfile = $uploaddir . $file;

if (move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $uploadfile)) {
    echo $file;
}
else {
    echo "error";
}

uploadNotification($file);

?>