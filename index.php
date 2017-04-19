<?php

// Initialize

include_once('settings.php');

$infoState = $_COOKIE['infostate'];
if(isset($infoState)) $infoState = (int) $infoState;

if($infoState == 2) $infoState = 1;

$filter = $_GET['emotion'];
if(!$filter) $filter = $_GET['s-emotion'];
$page = (int) $_GET['p'];
	if(!$page || $page < 1) $page = 1;

$mysqli = mysqli_connect($mysqldomain, $mysqluser, $mysqlpass, $mysqldb);
if (mysqli_connect_errno($mysqli)) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}


// Check for and process new images
$arrivals = scandir($dockDir);
foreach($arrivals as $arrival)
{
	if(strlen($arrival)<3){	
		continue;
	}
	$packages = scandir($dockDir.'/'.$arrival);
	
	foreach($packages as $filename){
		
		if(strpos($filename, '.jpg') !== false){
			
			$dateStr = substr($filename, 0, 17);
			$year = substr($dateStr, 0, 4);
			$month = substr($dateStr, 4, 2);
			$day = substr($dateStr , 6, 2);
			$hour = substr($dateStr, 8, 2);
			$min = substr($dateStr, 10, 2);
			$sec = substr($dateStr, 12, 2);
			$dateFormatted = "$year-$month-$day $hour:$min:$sec";
			
			$emotion = substr($filename, 18);
			$emotion = substr($emotion, 0, strpos($emotion, "."));
			
			$testerCheck = translateEmotion($emotion);
			if(isTestingStr($testerCheck, $testingStr) || in_array($testerCheck, $versionCodes)){
				if(!rename($dockDir.'/'.$arrival.'/'.$filename, $testerDir."d/".$filename)){
					echo "File action error 1.";
					exit();
				}
				
				continue;
			}
			
			if($mysqli->query("INSERT INTO emoticam_images (UID,filename,date,emotion,IP) VALUES ('d','$filename','$dateFormatted','$emotion','$arrival')")){
				if(!copy($dockDir.'/'.$arrival.'/'.$filename, $imageDir."d/".$filename)){
					echo "File action error.";
					exit();
				}
				
				$unixTime = time();
				$filename2 = "$unixTime" . substr($filename, 18);
				if(!rename($dockDir.'/'.$arrival.'/'.$filename, "./receiver/images/$filename2")){
					echo "File action error 2.";
					exit();
				} 
			} else {
				echo "MySQL Error: (" . $mysqli->errno . ") " . $mysqli->error;
				exit();
			}
		}
	}
	
	rmdir($dockDir.'/'.$arrival);
	
}

// Check for an process images uploaded by older version
foreach($users as $realName => $UID){

	$workingDir = $uploadsDir . $UID . "/";
	$images = scandir($workingDir);

	foreach($images as $filename)
	{
		if(strpos($filename, '.jpg') !== false){
			$dateStr = substr($filename, 0, 17);
			$year = substr($dateStr, 0, 4);
			$month = substr($dateStr, 4, 2);
			$day = substr($dateStr , 6, 2);
			$hour = substr($dateStr, 8, 2);
			$min = substr($dateStr, 10, 2);
			$sec = substr($dateStr, 12, 2);
			$dateFormatted = "$year-$month-$day $hour:$min:$sec";
			
			$emotion = substr($filename, 18);
			$emotion = substr($emotion, 0, strpos($emotion, "."));
			
			$testerCheck = translateEmotion($emotion);
			if(isTestingStr($testerCheck, $testingStr) || in_array($testerCheck, $versionCodes)){
				if(!rename($workingDir.$filename, $testerDir.$UID."/".$filename)){
					echo "File action error 1.";
					exit();
				}
				
				continue;
			}
			
			if($mysqli->query("INSERT INTO emoticam_images (UID,filename,date,emotion) VALUES ('$UID','$filename','$dateFormatted','$emotion')")){
				if(!copy($workingDir.$filename, $imageDir.$UID."/".$filename)){
					echo "File action error.";
					exit();
				}
				
				$unixTime = time();
				$filename2 = "$unixTime" . substr($filename, 18);
				if(!rename($workingDir.$filename, "./receiver/images/$filename2")){
					echo "File action error 2.";
					exit();
				} 
			} else {
				echo "MySQL Error: (" . $mysqli->errno . ") " . $mysqli->error;
				exit();
			}
		}
	}
}

if((int)$_GET['cron'] != 1){

// Get list of used emotions
$emoList = $mysqli->query("SELECT DISTINCT emotion FROM emoticam_images ORDER BY emotion");
$usedEmotions = array();
while ($row = $emoList->fetch_assoc()) {
	$usedEmotions[$row['emotion']] = translateEmotion($row['emotion']);
}

asort($usedEmotions);

// Check GET input against whitelist (used emotions) and discard if no match
if(!array_key_exists($filter, $usedEmotions)) $filter = "";

$eventFilter = $filter;
if(!$eventFilter) $eventFilter = "No Filter";

$startLim = ($page-1)*$numPerPage;

//Temporary version for blocking unsigned users
if(isset($blocked)){
	$blockedStr = "'" . array_pop($blocked) . "'";
	while(!empty($blocked)){
		$blockedStr .= ", '" . array_pop($blocked) . "'";
	}
	
	if($filter) $res = $mysqli->query("SELECT ID,UID,date,emotion,filename FROM emoticam_images WHERE emotion='$filter' AND UID NOT IN ($blockedStr) ORDER BY date DESC LIMIT $startLim, $numPerPage");
	else $res = $mysqli->query("SELECT ID,UID,date,emotion,filename FROM emoticam_images WHERE UID NOT IN ($blockedStr) ORDER BY date DESC LIMIT $startLim, $numPerPage");
} else {
	//if($filter) $res = $mysqli->query("SELECT ID,UID,date,emotion,filename FROM emoticam_images WHERE emotion='$filter' ORDER BY date DESC LIMIT $startLim, $numPerPage");
	//else $res = $mysqli->query("SELECT ID,UID,date,emotion,filename FROM emoticam_images ORDER BY date DESC LIMIT $startLim, $numPerPage");
}


if($res->num_rows === 0){
	header("HTTP/1.0 404 Not Found");
	exit();
}

$nextPageNum = $page+1;

$nextPageURL = "$projectURL/?p=$nextPageNum";
if($filter) $nextPageURL .= "&emotion=" . $filter;

if($page > 1){
	$prevPageURL = $projectURL . "/?p=";
	$prevPageURL .= $page-1;
	if($filter) $prevPageURL .= "&emotion=" . $filter;
}

if(($page*$numPerPage) >= $count) $onLastPage = true;

?>

<!DOCTYPE html>
<html>
<head>

<title>Emoticam</title>

<style>

@media only screen
and (min-device-width : 1px)
and (max-device-width : 1629px) {
	.stretch{
		width:800px;
	}
}

@media only screen
and (min-device-width : 1630px)
and (max-device-width : 2439px) {
	.stretch{
		width:1610px;
	}
}

@media only screen
and (min-device-width : 2440px)
and (max-device-width : 3249px) {
	.stretch{
		width:2420px;
	}
}

@media only screen
and (min-device-width : 3250px)
and (max-device-width : 4059px) {
	.stretch{
		width:3230px;
	}
}

@media only screen
and (min-device-width : 4060px)
and (max-device-width : 4869px) {
	.stretch{
		width:4040px;
	}
}

@media only screen
and (min-device-width : 4870px)
and (max-device-width : 5679px) {
	.stretch{
		width:4850px;
	}
}

@media only screen
and (min-device-width : 5680px)
and (max-device-width : 6489px) {
	.stretch{
		width:5660px;
	}
}

@media only screen
and (min-device-width : 6490px){
	.stretch{
		width:6470px;
	}
}

html{
	margin: 0;
}

body{
	margin: 0 5px 5px 5px;
	background-color: #eee;
	-webkit-text-size-adjust: 100%;
}

p{
	margin: 0;
	padding: 10px 0;
}

h1{
	font-size:13px;

}

@media screen and (-webkit-min-device-pixel-ratio: 0) {
select:focus, textarea:focus, input:focus {
        font-size: 16px;
    }
}

html{
	margin-top: 101px;
}

#stickytop{
	position: fixed;
	z-index: 500;
	top: 0;
}

#header{
	float:left;
	margin-top:0;
	padding:30px 0 5px 0;
	font-size:48px;
	line-height: .5em;
	font-family:helvetica, sans-serif;
	text-transform: uppercase;
	background: #eee;
	z-index: 100;
	position: relative;
}

a#title{
	text-decoration:none;
	color: black;
	z-index: 100;
}

#header #artist{
	font-size:18px;
	color: #bbb;
	z-index: 100;
}
#header #artist a{
	color: #bbb;
	text-decoration: none;
}
#header #artist a:hover{
	color: black;
}

#navbar{
	background: black;
	width:100%;
	display:block;
	float:left;
	margin-bottom: 5px;
	height: 27px;
	z-index: 100;
	position: relative;
}

/*
#stickyNavbar{
	background: black;
	width:100%;
	display:block;
	float:left;
	margin-bottom: 5px;
	height: 27px;
	z-index: 0;
	position: fixed;
	display: none;
	-webkit-transform: translateZ(0);
}
*/

#navInfoButton{
	color: white;
	text-decoration: none;
	float:right;
	display: none;
	height:27px;
	padding:0;
}

/*
#stickyNavInfoButton{
	color: white;
	text-decoration: none;
	float:right;
	height:27px;
	padding:0;
}
*/

#stickyBackToTop{
	color: white;
	text-decoration: none;
	float:right;
	height:27px;
	padding:0;
	display:none;
}

#stickyBackToTop div.outer{
	color:white;
	font-family:helvetica, sans-serif;
	text-transform: uppercase;
	overflow:hidden;
	font-size:12px;
	padding: 0 9px;
	line-height:28px;
	border-left: 1px solid white;
	width: inherit;
	height: inherit;
}


/* #navInfoButton div, #stickyNavInfoButton div{ */
#navInfoButton div{
	color:white;
	font-family:helvetica, sans-serif;
	text-transform: uppercase;
	overflow:hidden;
	font-size:12px;
	padding: 0 9px;
	line-height:28px;
	border-left: 1px solid white;
	<?php if($filter){ ?>
	border-right: 1px solid white;
	<?php } ?>
	width: inherit;
	height: inherit;
}

/* #navInfoButton div:hover, #stickyNavInfoButton div:hover, #stickyBackToTop div:hover{ */
#navInfoButton div:hover{
	background: #666;
}

/*#emoFilter, #stickyEmoFilter{*/
#emoFilter{
	background: black;
	padding:0;
	overflow:hidden;
	float:right;
	height:inherit;
}

/* #emoFilter, #emoFilter .filterIndicator, #emoFilter select, #stickyEmoFilter, #stickyEmoFilter .filterIndicator, #stickyEmoFilter select{ */
#emoFilter, #emoFilter .filterIndicator, #emoFilter select{
	font-family:helvetica, sans-serif;
	color: white;
	font-size:12px;
	text-decoration:none;
}

/* #emoFilter .filterIndicator, #stickyEmoFilter .filterIndicator{ */
#emoFilter .filterIndicator{
	text-transform: uppercase;
	float:left;
	padding:0 7px;
	line-height:28px;
	height:27px;
	background:black;
}

/* #emoFilter .filterIndicator:hover, #stickyEmoFilter .filterIndicator:hover{ */
#emoFilter .filterIndicator:hover{
	background: #444;
}

/* #emoFilter #emoPicker, #stickyEmoFilter #emoPicker{ */
#emoFilter #emoPicker{
	float:right;
	height:inherit;
	line-height:27px;
	padding: 0;
	border-left: 1px solid #eee;
}

/* #emoFilter #emoPicker select, #stickyEmoFilter #emoPicker select{ */
#emoFilter #emoPicker select{
	padding: 0 7px;
	cursor:pointer;
}

/* #emoFilter #emoPicker select:hover, #emoFilter #emoPicker:hover, #stickyEmoFilter #emoPicker select:hover, #stickyEmoFilter #emoPicker:hover{ */
#emoFilter #emoPicker select:hover, #emoFilter #emoPicker:hover{
	<?php
		if($filter) echo "background: #888;";
		else echo "background: #666;";
	?>
}

/* #emoFilter #emoPicker select, #stickyEmoFilter #emoPicker select{ */
#emoFilter #emoPicker select{
	-webkit-appearance: none;
    -moz-appearance: window;
    background: black;
    height: 27px;
    border:0;
}


#description{
	float:left;
	position:relative;
	border-bottom:30px solid #000;
	margin-bottom: 5px;
	text-align:center;
	z-index: 10;
	background: #eee;
	overflow:hidden;
}

#description div#outer{
	float: left;
    position: relative;
    left: 50%;
}

#description div#outer div#inner{
	float: left;
	position: relative;
    left: -50%;
	text-align:left;
	padding:10px;
	color: #666;
	font-size:13px;
	
	padding: 0 15px;
	margin: 5px 5px 10px 5px;
	max-width:900px;
	font-family:arial, sans-serif;
	clear:both;
}

#description ul#participants{
	list-style:none;
	padding: 0;
}

#description div#moreButton{
	float:left;
	position: relative;
    left: 0;
    padding: 0;
    margin: 0;
}

#description div#closeButton{
    padding: 0;
    margin: 0 0 10px 0;
    font-size:13px;
    font-family:arial, sans-serif;
    text-transform: uppercase;
    text-align:center;
    display:none;
}

#description div#closeButton2{
	padding: 0;
    margin: 5px 0 0 0;
    font-size:13px;
    font-family:arial, sans-serif;
    text-transform: uppercase;
    text-align:center;
    display:none;
}

#description a{
	color: black;
	text-decoration: none;
}

#description a:hover{
	color: #aaa;
}


#description h1{
	margin-top: 1.2em;
}

#items{
	float:left;
}

.item{
	margin:5px;
	width: 800px;
	float: left;
	text-align: center;
	overflow: hidden;
	
}

.twitNote{
	margin:20px 5px;
	width: 100%;
	float: left;
	text-align: left;
	overflow: hidden;
	font-family: arial, sans-serif;
}

.imageContainer{
	overflow: hidden;
    width: 800px;
    height: 600px;
    text-align: center;
}

.imageContainer > span {
    display: block;
    width: 1500px;
    height: inherit;
    margin-left: -350px; /* -(width-container width)/2 */
}

.imageContainer .webcamImage{
	min-width:800px;
	background:black;
	height: inherit !important;
	display:inline-block;
}

.item .emotion{
	background: black;
	color: #eee;
	font-family: Helvetica, sans-serif;
	font-size:24px;
	padding: 8px 0;
	margin:0;
}

.item .date{
	color: #444;
	font-variant: small-caps;
	float:right;
}

.activeButton{
	border: 1px solid #aaa;
	padding: 15px 0;
	text-align: center;
	font-size:24px;
	font-family:helvetica, sans-serif;
	text-transform: uppercase;
	color: #999;
	background: #e3e3e3;
}

.activeButton:hover{
	background: #ddd;
	border: 1px solid #888;
	color:#777;
}

.inactiveButton{
	border: 1px solid #ccc;
	padding: 15px 0;
	text-align: center;
	font-size:24px;
	font-family:helvetica, sans-serif;
	text-transform: uppercase;
	color: #ccc;
	background: #eee;
}

.inactiveButton a{
	text-decoration: none;
	color: #bbb;
}

.inactiveButton a:hover{
	color: #999;
}

#navFrame{
	width:100%;
	float:left;
	margin: 15px 5px 30px;
}

#prevPageDiv{
	width: 50%;
	float:left;
}

#nextPageDiv{
	width: 50%;
	float:right;
}

.prevPageButton{
	margin: 0 5px 0 2px;
}

.nextPageButton{
	margin: 0 2px 0 6px;
}

a.pagination{
	text-decoration:none;
}

</style>

<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="apple-touch-icon" href="<?php echo $projectURL ?>/apple-touch-icon.png" />
<!-- <meta name="apple-mobile-web-app-capable" content="yes"> -->

<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
<script type="text/javascript" src="jquery.infinitescroll.min.js"></script>
<script type="text/javascript" src="jquery.cookie.js"></script>
<script type="text/javascript" src="waypoints.min.js"></script>
<script type="text/javascript">

	isiDevice = (
	        (navigator.platform.indexOf("iPhone") != -1) ||
	        (navigator.platform.indexOf("iPod") != -1) ||
	        (navigator.userAgent.indexOf("iPad") != -1)
	    );
	
	var nextPageLoad = 2;
	
	var lastScrollTop = 0;
	var barReady = false;
	var titleReady = true;
	var titleActive = true;
	var barActive = true;
	var infoState = <?php echo ($infoState ? "true" : "false"); ?>;
	
	function moreInfo(){
		$("#moreInfoButton").hide(400);
		//$("#closeButton2").show();
		//$("#moreInfo").fadeToggle(400, function(){
		$("#moreInfo").show(400, function(){
			$.waypoints('refresh');
		});
		$.cookie("infostate", 2, { expires : 10 });
	}
	function closeInfo(){
		infoState = false;
		$("#description").animate({"margin-top": (($("#description").height()*-1)-40)+'px'}, 500, function(){
			$.waypoints('refresh');
			$("#moreInfoButton").show();
			$("#moreInfo").hide();
			//$("#closeButton2").hide();
			$(this).hide();
		});
		if(titleReady) $("#navInfoButton").fadeIn(800);
		$.cookie("infostate", 0, { expires : 10 });
	}
	function openInfo(){
		infoState = true;
		$("#description").show();
		$("#description").animate({"margin-top": '0px'}, 400, function(){
			$.waypoints('refresh');
		});
		$.cookie("infostate", 1, { expires : 10 });
		if(!titleReady){
			moreInfo();
			toTheTop();	
		} else {
			$("#navInfoButton").fadeOut(800);
		}
	}
	function activateBar(){
		barActive = true;
		//$("#stickyNavbar").css({"margin-top" : $("#stickyNavbar").height()*-1+"px", "top" : 0});
		//$("#stickyNavbar").show();
		//$("#stickyNavbar").animate({"margin-top" : 0}, 250);
		if(titleReady){
			titleActive = true;
			$("#stickyBackToTop").fadeOut(250);
			if(infoState) $("#navInfoButton").fadeOut(250);
			$("#stickytop").animate({"margin-top" : 0}, 250);
		} else {
			titleActive = false;
			$("#stickyBackToTop").show();
			$("#navInfoButton").show();
			$("#stickytop").animate({"margin-top" : ($("#stickytop").height()-$("#navbar").height()-5)*-1+"px"}, 250);
		}
	}
	function simpleActivateTitle(){
		titleActive = true;
		$("#stickytop").animate({"margin-top" : 0}, 250);
		if(!infoState) $("#navInfoButton").show(250);
		$(".filterIndicator").show(250);
		
	}
	function simpleDeactivateTitle(){
		if(($(window).width() < 600 && $(window).width() < $(window).height())){
			titleActive = false;
			$("#stickyBackToTop").fadeIn(250);
			$("#navInfoButton").hide(250);
			$(".filterIndicator").hide(250);
			$("#stickytop").animate({"margin-top" : ($("#stickytop").height()-$("#navbar").height()-5)*-1+"px"}, 250);
		} else if(isiDevice){
			titleActive = false;
			$("#stickyBackToTop").fadeIn(250);
			$("#navInfoButton").show(250);
			$("#stickytop").animate({"margin-top" : ($("#stickytop").height()-$("#navbar").height()-5)*-1+"px"}, 250);
		}
		
	}
	function simpleBarPos(){
		$("#stickytop").css({"margin-top" : ($("#stickytop").height()-$("#navbar").height()-5)*-1+"px"});
		//if($(window).width() < $(window).height()){
		//	$("#navInfoButton").hide(250);
		//}
		
	}
	function deactivateBar(){
		barActive = false;
			//$("#stickyNavbar").animate({"margin-top" : $("#stickyNavbar").height()*-1+"px"}, 250, function(){
			//	$("#stickyNavbar").hide();
			//});
			titleActive = false;
			$("#stickytop").animate({"margin-top" : $("#stickytop").height()*-1+"px"}, 250);
	}
	//function deactivateTitle(){
	//	$("#stickytop").animate({"margin-top" : $("#stickytop").height()*-1+"px"}, 250);
	//}
	function disableWaypoints(){
	    $("#items").waypoint('disable');
	    $("#loader").waypoint('disable');
	}
	function enableWaypoints(){
	    $("#items").waypoint('enable');
	    $("#loader").waypoint('enable');
	}
	$( document ).ready(function() {
		sizeCheck();
		<?php 
			if($infoState === 0) {
				echo '$("#description").css({"margin-top": (($("#description").height()*-1)-67)+"px"});';
				echo '$("#description").hide();';
				echo '$("#navInfoButton").show();';
			}
		?>
    	$('.filterIndicator').mouseenter(function() {
	    	$('.filterIndicator').text("FILTER OFF?");
		}).mouseleave(function() {
    		$('.filterIndicator').text("FILTER ON");
		});
		
		$("#closeButton").show();
		
		var st = $(this).scrollTop();
		
		$("#contentTop").waypoint(function(){
			titleReady = false
			barReady = true;
			if(titleActive && ($(window).width() <= 600 || isiDevice)) simpleDeactivateTitle();
			//if(titleActive && ($(window).width() <= 600)) simpleDeactivateTitle();
	
		}, {offset: 50});
		
		
		$("#contentTop2").waypoint(function(){
			titleReady = true;
			barReady = false;
			if(!titleActive){
				barActive = true;
				if($(window).width() <= 600 || isiDevice) simpleActivateTitle();
				//if($(window).width() <= 600) simpleActivateTitle();
				activateBar();
			}
		}, {offset: 51}); 
		
		//$("#title").waypoint(function(){
		//	barReady = false;
		//});
		
		$("#items").waypoint(function(){
			disableWaypoints();
			$("#loader").text("Loading...");
			$('#items').infinitescroll('retrieve');
			_gaq.push(['_trackEvent', 'Inline Content', 'AJAX Load Page', '<?php echo $eventFilter ?>', nextPageLoad]);
		}, {offset: function() {
			return -1*($("#items").height()-$(window).height()-1200);
			}	
		});
		
		$("#loader").waypoint(function(){
			disableWaypoints();
			$("#loader").text("Loading...");
			$('#items').infinitescroll('retrieve');
			_gaq.push(['_trackEvent', 'Inline Content', 'AJAX Load Page', '<?php echo $eventFilter ?>', nextPageLoad]);
		}, {offset: 'bottom-in-view'}); 
		
		$(window).scroll(function(event){
			if($(window).width() > 600 && !isiDevice){
			//if($(window).width() > 600){
				st = $(this).scrollTop();
		   			
		   			if(!barActive){
		   				   if(barReady && st < lastScrollTop) {
		   			 			activateBar();
		   					}
		   			} else if(barReady && st > lastScrollTop) {
		   				deactivateBar();
		   			}
		   			
		   		lastScrollTop = st;
		   	//} else {
		   	//	simpleBarCheck();
		   	}
		});
	});
	
	$(window).resize(function() {sizeCheck();});
	
	function toTheTop(){
		//if($(window).width() > 600){
			//barActive = false;	
		    //deactivateBar();
		//}
		$("html, body").animate({ scrollTop: "0px" });
	}
	
	function sizeCheck(){
		if ($(window).width() < 800 ) {$('.stretch').width($(window).width()-8);
		} else if ($(window).width() < 830 ) {$('.stretch').width($(window).width()-30);
		} else if ($(window).width() < 1630 ) {$('.stretch').width(800);
		} else if($(window).width() < 2440) {$('.stretch').width(1610);
		} else if($(window).width() < 3250) {$('.stretch').width(2420);
		} else if($(window).width() < 4060) {$('.stretch').width(3230);
		} else if($(window).width() < 4870) {$('.stretch').width(4040);
		} else if($(window).width() < 5680) {$('.stretch').width(4850);
		} else if($(window).width() < 6490) {$('.stretch').width(5660);
		} else {$('.stretch').width(6470);
		}
		
		if ($(window).width() < 800 ){
			$(".stretch").css({"margin-right": "2px", "margin-left": "2px"});
			$(".item").css({"margin-right": "2px", "margin-left": "2px"});
			$("body").css({"margin-right": "2px", "margin-left": "2px"});
		} else {
			$(".stretch").css({"margin-right": "5px", "margin-left": "5px"});
			$(".item").css({"margin-right": "5px", "margin-left": "5px"});
			$("body").css({"margin-right": "5px", "margin-left": "5px"});
		}
		
		if ($(window).width() < 800 ){
			var resizePercent = ($(window).width()-8)/800;
			$('.imageContainer').height(resizePercent*600);
			var newItemWidth = resizePercent*800;
			$('.item').width(newItemWidth);
			$(".webcamImage").css({"min-width": resizePercent*800+"px"});
			$(".icSpan").css({"margin-left": ((newItemWidth-1500)/2)+"px"});	
		} else if ($(window).width() < 830 ){
			var resizePercent = ($(window).width()-30)/800;
			$('.imageContainer').height(resizePercent*600);
			var newItemWidth = resizePercent*800;
			$('.item').width(newItemWidth);
			$(".webcamImage").css({"min-width": resizePercent*800+"px"});
			$(".icSpan").css({"margin-left": ((newItemWidth-1500)/2)+"px"});
		} else if($('.item').width() != 800){
			$('.imageContainer').height(600);
			$('.item').width(800);
			$(".webcamImage").css({"min-width": "800px"});
			$(".icSpan").css({"margin-left": "-350px"});
		}
		
		
		
		if ($(window).width() < 600 ){
			var rPercent2 = $(window).width()/600;
			$("#header").css({"font-size" : (rPercent2*48)+"px", "padding-top" : (rPercent2*30)+"px", "line-height" : ".6em"});
			$("#artist").css({"font-size" : (rPercent2*18)+"px"});
			$(".emotion").css({"font-size" : (rPercent2*24)+"px", "padding-top" : (rPercent2*8)+"px", "padding-bottom" : (rPercent2*8)+"px"});
			$(".date").css({"font-size" : ((rPercent2/2)+.5)+"em"});
			
			//$("#stickyNavbar").css({'border-bottom' : '1px solid #222', 'border-top' : '1px solid #222'});
			//$("#stickyNavInfoButton").hide();
			//$("#stickyEmoFilter .filterIndicator").hide();
			//$("#stickyBackToTop").width('50%');
			//$("#stickyBackToTop .outer").css({'border-right' : '1px solid #eee', 'border-left' : 0, 'padding' : 0 });
			//$("#stickyBackToTop .outer").width('100%');
			//$("#stickyBackToTop .inner").css({'padding-left' : '7px'});
			//$("#stickyEmoFilter").width('50%');
			//$("#stickyEmoFilter #emoPicker").width('100%');
			//$("#stickyEmoFilter #emoPicker select").css({'direction' : 'rtl'});
			//$("#stickyEmoFilter #emoPicker option").css({'direction' : 'ltr'});
			//$("#stickyEmoFilter #emoPicker select").width('100%');
			//$("#stickyEmoFilter").css({'direction' : 'rtl' });
			
			/*)
			$("#navbar").css({'border-bottom' : '1px solid #222', 'border-top' : '1px solid #222'});
			$("#navInfoButton").hide();
			$("#emoFilter .filterIndicator").hide();
			$("#stickyBackToTop").width('50%');
			$("#stickyBackToTop .outer").css({'border-right' : '1px solid #eee', 'border-left' : 0, 'padding' : 0 });
			$("#stickyBackToTop .outer").width('100%');
			$("#stickyBackToTop .inner").css({'padding-left' : '7px'});
			$("#emoFilter").width('50%');
			$("#emoFilter #emoPicker").width('100%');
			$("#emoFilter #emoPicker select").css({'direction' : 'rtl'});
			$("#emoFilter #emoPicker option").css({'direction' : 'ltr'});
			$("#emoFilter #emoPicker select").width('100%');
			$("#emoFilter").css({'direction' : 'rtl' });
			*/
			
			if($(window).width() < $(window).height()){
				//$("#stickyNavbar").css({"margin-top" : 0});
				//$("#stickyNavbar").show();
				if(!titleActive || infoState) $("#navInfoButton").hide();
				if(titleActive) $(".filterIndicator").show();
				else $(".filterIndicator").hide();
				if(!barActive) activateBar();
				$("#stickytop").css({'position' : 'fixed'});
				//simpleDeactivateTitle();
				
			} else {
				$(".filterIndicator").show();
				if(!infoState) $("#navInfoButton").show();
				$("#stickytop").css({'position' : 'absolute'});
				//$("#stickyNavbar").css({"margin-top" : $("#stickyNavbar").height()*-1+"px"});
				//$("#stickyNavbar").hide();
			}		
			
		} else {
			$(".filterIndicator").show();
			if(barReady) $("#navInfoButton").show();
			$("#stickytop").css({'position' : 'fixed'});
			//if(barReady) $("#navInfoButton").show();		
			//if(isiDevice){
				//$("#stickyNavbar").css({"margin-top" : 0});
				//$("#stickyNavbar").show();
			//}	
			$("#header").css({"font-size" : "48px", "padding-top" : "30px", "line-height" : ".5em"});
			$("#artist").css({"font-size" : "18px"});
			$(".emotion").css({"font-size" : "24px", "padding-top" : "8px", "padding-bottom" : "8px"});
			$(".date").css({"font-size" : "1em"});
			//$("#stickyNavbar").css({'border-bottom' : 0, 'border-top' : 0});
			//$("#stickyNavInfoButton").show();
			//$("#stickyEmoFilter .filterIndicator").show();
			//$("#stickyBackToTop").width('auto');
			//$("#stickyBackToTop .outer").css({'border-right' : 0, 'padding' : '0 9px' });
			//$("#stickyBackToTop .outer").width('auto');
			//$("#stickyBackToTop .inner").css({'padding-left' : 0});
			//$("#stickyEmoFilter").width('auto');
			//$("#stickyEmoFilter #emoPicker").width('auto');
			//$("#stickyEmoFilter #emoPicker select").css({'background' : '#000', 'direction' : 'ltr'});
			//$("#stickyEmoFilter #emoPicker select").width('auto');
			//$("#stickyEmoFilter").css({'direction' : 'ltr' });
		}
		
		//if(($(window).width() < $(window).height()) && !barActive && isiDevice) activateBar();
		
		$('html').css({"margin-top" : $('#stickytop').height()+"px"});
		
		if(!titleReady && titleActive) simpleDeactivateTitle();
		if(!titleReady && barActive) simpleBarPos();
		
	};
</script>

<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-2839461-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>

</head>
<body>
<div id='contentTop'></div><div id='contentTop2'></div>

<div id="stickytop">

		<div id="header" class="stretch">
			<a id="title" href="<?php echo $projectURL ?>">Emoticam</a> <span id="artist"><!--By-->&nbsp;<!--<a target="_blank" href="http://www.dansakamoto.com">Dan&nbsp;Sakamoto</a>--></span>
		</div>
		
		<div id="navbar" class="stretch">
		
			<div id="emoFilter">
				<?php
				
				if($filter) echo "<a class='filterIndicator' href='$projectURL'>Filter On</a><div id='emoPicker'><form method='GET'><select class='filterOn' name='emotion' onchange='this.form.submit()'><option value=''>FILTER OFF</option>";
				else echo "<div id='emoPicker'><form method='GET'><select name='emotion' onchange='this.form.submit()'><option value=''>FILTER BY EMOTION</option>";
				
				foreach($usedEmotions as $emoCode => $emo){
					if($filter == $emoCode){
						echo "<option value='$emoCode' selected>$emo</option>";
					} else {
						echo "<option value='$emoCode'>$emo</option>";
					}
				}
				
			?>
			
				</select></form></div>
			</div> <!-- emoFilter -->
			
			<a onclick="_gaq.push(['_trackEvent', 'Interface', 'Click', 'Info - Top Nav']);" id="navInfoButton" href="javascript:openInfo();">
			<div>
				Info
			</div>
			</a>
			
			<a onclick="_gaq.push(['_trackEvent', 'Interface', 'Click', 'Back to Top - Sticky Nav']);" id="stickyBackToTop" href="javascript:toTheTop();">
				<div class="outer">
					<div class="inner">
						Back to top
					</div>
				</div>
			</a>
			
		</div> <!-- navbar -->
		
	</div> <!-- stickytop -->
	
	<!--
	<div id="stickyNavbar" class="stretch">
	
		<div id="stickyEmoFilter">
			<?php
			
			if($filter) echo "<a class='filterIndicator' href='$projectURL'>Filter On</a><div id='emoPicker'><form method='GET'><select class='filterOn' name='emotion' onchange='this.form.submit()'><option value=''>FILTER OFF</option>";
			else echo "<div id='emoPicker'><form method='GET'><select name='s-emotion' onchange='this.form.submit()'><option value=''>FILTER BY EMOTION</option>";
			
			foreach($usedEmotions as $emoCode => $emo){
				if($filter == $emoCode){
					echo "<option value='$emoCode' selected><span>$emo</span></option>";
				} else {
					echo "<option value='$emoCode'><span>$emo</span></option>";
				}
			}
			
		?>
		
			</select></form></div>
		</div> <!-- stickyEmoFilter 
		
		<a onclick="_gaq.push(['_trackEvent', 'Interface', 'Click', 'Info - Sticky Nav']);" id="stickyNavInfoButton" href="javascript:openInfo();moreInfo();toTheTop();">
		<div>
			Info
		</div>
		</a>
		
		<a onclick="_gaq.push(['_trackEvent', 'Interface', 'Click', 'Back to Top - Sticky Nav']);" id="stickyBackToTop" href="javascript:toTheTop();">
		<div class="outer">
			<div class="inner">
			Back to top
			</div>
		</div>
		</a>
		
	</div> <!-- sticky navbar -->
	
	<script type="text/javascript">sizeCheck();</script>
	
	<div id="description" class="stretch">
		<div id="outer">
			<div id="inner">
				<div id="closeButton2">
					<a href="javascript:closeInfo();">Close</a>
				</div>
			
				<p>
				    Emoticam is a program running (consensually) in the background of a bunch of computers.<br>
				    Anytime a user types something to imply they're emoting in real life, it takes a photo of their face and uploads it here and to <a target="_blank" href="http://twitter.com/emoticam_net" style="text-decoration:none;">Twitter</a>.
				</p>
				
				<p>
					Emoticam is open participation; you can <a href="/download">download the software here</a>.
				</p>
				
				<p>
					Created by <a target="_blank" href="http://www.dansakamoto.com">Dan Sakamoto</a>. On Twitter as <a target="_blank" href="http://twitter.com/emoticam_net" style="text-decoration:none;">@emoticam_net</a>.
				</p>
					
				<p>
					<a target="_blank" rel="license" href="http://creativecommons.org/licenses/by-nc-sa/4.0/deed.en_US"><img alt="Creative Commons License" style="border-width:0" src="//i.creativecommons.org/l/by-nc-sa/4.0/80x15.png" /> 2013 - <?php echo date("Y"); ?> emoticam.net</a>
				</p>
				
				<div id="moreInfo">
					
				</div>
				
				<script type="text/javascript">$("#moreInfo").hide();</script>
				
				<!--<div id="moreInfoButton"><a onclick="_gaq.push(['_trackEvent', 'Interface', 'Click', 'More Info']);" href="javascript:moreInfo();">More &#8250;</a></div>-->
				
				<div id="closeButton">
					<a href="javascript:closeInfo();">Close</a>
				</div>
				
				
			</div>
			
		</div>
		
	</div>
	
	
	<script type="text/javascript">
		<?php
		
		if($infoState === 2) echo '$("#moreInfo").show();$("#moreInfoButton").hide();';
		
		?>
	</script>
	
	<?php
	
	echo "<div id='items'>";

	
	while ($row = $res->fetch_assoc()) {
		$ID = $row['ID'];
		$UID = $row['UID'];
	    $date = $row['date'];
	    $emotion = $row['emotion'];
	    $filename = $row['filename'];
	    
		if(strpos($filename, '.jpg') !== false){
	
			$year = substr($date, 0, 4);
			$month = (int)substr($date, 5, 2);
			$day = (int)substr($date, 8, 2);
			$hour = (int)substr($date, 11, 2);
			$min = substr($date, 14, 2);
			$ampm = "am";		
	
			if($hour > 11) $ampm = "pm";
			if($hour == 0) $hour = 12;
			if($hour > 12) $hour -= 12;
			
			//$month = date("M", mktime(0, 0, 0, $month, 10));
			
			$emotion = translateEmotion($emotion);
			
			echo "<div class='item id-$ID'><div class='imageContainer'><span class='icSpan'><img class='webcamImage' src='images/$UID/$filename'></span></div>";
			echo "<div class='emotion'>$emotion</div>";
			echo "<div class='date'>$month/$day/$year&nbsp;&nbsp;&nbsp;$hour:$min $ampm</div>";
			echo '</div>';
	
			}
	}
	?>
	
	</div>
	
	<div id="navFrame" class="stretch">
	
		<div id="endOfJSNav" style="display:none;">
			<div class="inactiveButton">
				<?php if($filter){ ?>
				No more images with current filter. <a href="<?php echo $projectURL ?>">Turn&nbsp;it&nbsp;off?</a> <span id="backToTop"><a onclick="_gaq.push(['_trackEvent', 'Interface', 'Click', 'Back to Top - Bottom Nav']);" class="topLink" href="#">Back&nbsp;to&nbsp;top?</a></span>
				<?php } else { ?>
				No more images to load. <a class="topLink" href="#">Back to top</a>
				<?php } ?>
			</div>
		</div>
	
		<div id="JSNav">
		
			<?php if($filter){ ?>
				<a onclick="_gaq.push(['_trackEvent', 'Inline Content', 'Click for more', '<?php $eventFilter ?>', nextPageLoad]);" id="infNext" class="pagination" href="<?php echo "$projectURL/?p=$nextPageNum&emotion=$filter" ?>">
			<?php } else { ?>
				<a onclick="_gaq.push(['_trackEvent', 'Inline Content', 'Click for more', '<?php $eventFilter ?>', nextPageLoad]);" id="infNext" class="pagination" href="<?php echo "$projectURL/?p=$nextPageNum" ?>">
			<?php } ?>
			
				<div id="garbage" style="display:none;"></div>
			
				<div id="loader" class="activeButton">
					Load more images
				</div>
			</a>
		
		</div>
	
		<div id="noJSNav">
		
			<?php if($onLastPage && !$prevPageURL){ ?>
		
				<div class="inactiveButton">
					No more images with current filter. <a href="<?php echo $projectURL ?>">Turn&nbsp;it&nbsp;off?</a> <a onclick="_gaq.push(['_trackEvent', 'Interface', 'Click', 'Back to Top - Bottom Nav']);" class="topLink" href="#">Back&nbsp;to&nbsp;top?</a>
				</div>
		
			<?php } else { ?>
			
				<div id="prevPageDiv">
					<?php if($prevPageURL){ ?>
						<a class="pagination" href="<?php echo $prevPageURL ?>"><div class="activeButton prevPageButton">Previous Page</div></a>
					<?php } else { ?>
						<div class="inactiveButton prevPageButton">This is the first page</div>
					<?php } ?>
				</div>
				
				<div id="nextPageDiv">
					<?php if($onLastPage){ ?>
						<div class="inactiveButton nextPageButton">This is the last page</div>
					<?php } else { ?>
						<a class="pagination" href="<?php echo $nextPageURL ?>"><div class="activeButton nextPageButton">Next Page</div></a>
					<?php } ?>
				</div>
			
			<?php } ?>
		
		</div>
		
	</div>
	<script type="text/javascript">
		$('#items').infinitescroll({
	    	navSelector  : "div#noJSNav",            
	    	nextSelector : "a#infNext",    
	    	itemSelector : "#items div.item",
	    	bufferPx: 1200,
	    	loading: {
	    	    selector: "div#garbage",
	    	},
	  	},
		function(arrayOfNewElems)
		{
			nextPageLoad++;
			$("#loader").text("Load more images");
			sizeCheck();
			enableWaypoints();
		});
	 	
		 $(window).unbind('.infscr');
		 
		 $("a#infNext").click(function(){
		     $('#items').infinitescroll('retrieve');
		     return false;
		 });
		 
		 $("a.topLink").click(function(){
		     toTheTop();
		     return false;
		 });
		 
		 $("#noJSNav").hide();
		
		 $(document).ajaxError(
		     function(e,xhr,opt){
		     	if(xhr.status==404){
		     		_gaq.push(['_trackEvent', 'Inline Content', 'End Reached', '<?php echo $eventFilter ?>']);
		     		if($(document).height() < 2000) $("#backToTop").hide();
		     		$('div#endOfJSNav').show();
		     		$('div#JSNav').hide();
		     		enableWaypoints();
		     	}
		     }	
		 );
	</script>
</body>
</html>

<?php } else {

	echo "check successful.";
}


?>