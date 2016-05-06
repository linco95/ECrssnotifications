<?php
header("Content-Type: application/rss+xml; charset=UTF-8");
// Log into emocore and return contents of startpage
function loginAndFetch(){
	$username = ""; // INSERT USERNAME HERE
	$password = ""; // INSERT PASSWORD HERE
	$cookie = NULL; // TODO: Look into using cookies instead of logging in each poll
	$url = "https://www.emocore.se/actions.php?action=log_in";
	$postdata = "u_user=" . $username . "&u_pass=" . $password;
	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CAINFO, 'certs.pem');
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
	curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie);
	curl_setopt ($ch, CURLOPT_REFERER, $url);
	curl_setopt ($ch, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt ($ch, CURLOPT_POST, 1);
	$result = curl_exec ($ch);
	curl_close($ch);
	return $result;
}
 // Fetch the notifications from the notificationbar. $types define the type of notification, $string is the string to search 
 // Returns an array with the number of notifications, in order relative to $types. Set to -1 if the type wasn't found
 function fetchNotifications($types, $string) {
 	$result=[];
 	foreach($types as $type) {
 		preg_match("/a_$type\">(\d+)<\/b>/", $string, $array);
 		if(isset($array[1]))
	 		$result[]=$array[1];
	 	else $result[]=-1;
 	}
 	return $result;
 }


// main
// Save content of startpage to $contents
$contents = loginAndFetch();

// Declare the types we're intrested in.
$types = [
'gb',
'pm',
'fr',
'fo',
'bl',
'ga',
'li'
];
$result = fetchNotifications($types, $contents);

// Timestamp
$time = date("D, d M Y H:i:s");

// Prepare description of notifications
$description="";
for($i = 0; $i < count($result); $i++){
	$description.="$types[$i]: $result[$i], ";
}
$description.="Updated: $time.";

// Create rss
// TODO: Replace username with variable (from cookie?)
$rssfeed ='<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
	<channel>
		<title>"ECnotifs"</title>
		<link>http://www.emocore.se</link>
		<description>"Notifications for emocore for NaxXx"</description> 
		<language>sv-se</language>
		<copyright>Copyright (C) 2016 Andreas Kjellqvist (NaxXx@EC)</copyright>
		<item>
			<title>'.$description.'</title>
			<description>'.$description.'</description>
			<link>www.emocore.se</link>
			<pubDate>' . $time . '</pubDate>
		</item>
	</channel>
</rss>';
echo $rssfeed;
