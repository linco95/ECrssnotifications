<?php
header("Content-Type: application/rss+xml; charset=UTF-8");
main();

function login($ckjar)
{
	$username = ""; // Insert username here
	$password = ""; // Insert password here

	$postdata = "u_user=" . $username . "&u_pass=" . $password;

	// log in

	$ch = curl_init("https://www.emocore.se/actions.php?action=log_in");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, FALSE);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $ckjar);
	// CAINFO needs absolute path to work in apache
	curl_setopt($ch, CURLOPT_CAINFO, realpath(dirname(__FILE__))."\\certs.pem");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_exec($ch);
	curl_close($ch);
}

function fetchNotifData($ckjar)
{
	$ch = curl_init('https://www.emocore.se/_hidden/update.php?json=1');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	// CAINFO needs absolute path to work in apache
	curl_setopt($ch, CURLOPT_CAINFO, realpath(dirname(__FILE__))."\\certs.pem");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $ckjar);
	$page = curl_exec($ch);
	curl_close($ch);
	return $page;
}

function convertDataToRss($data = [])
{

	// Create a timestamp

	$time = date("D, d M Y H:i:s");

	// Create rss from data

	$rssfeed = '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
	<channel>
		<title>"ECnotifs"</title>
		<link>http://www.emocore.se</link>
		<description>"Notifications for emocore."</description>
		<language>sv-se</language>
		<copyright>Copyright (C) 2016 Andreas Kjellqvist (NaxXx@EC)</copyright>
		<item>
			<title>"GB: ' . $data['new_gb'] . ', PM: ' . $data['new_pm'] . ', FR: ' . $data['new_friend'] . ', FO: ' . $data['new_watch'] . ', BL: ' . $data['new_blogcomment'] . ', GA: ' . $data['new_gallery'] . ', LI: ' . $data['new_likes'] . ', Updated: ' . $time . '."</title>
			<description>"GB: ' . $data['new_gb'] . ', PM: ' . $data['new_pm'] . ', FR: ' . $data['new_friend'] . ', FO: ' . $data['new_watch'] . ', BL: ' . $data['new_blogcomment'] . ', GA: ' . $data['new_gallery'] . ', LI: ' . $data['new_likes'] . ', Updated: ' . $time . '."</description>
			<link>www.emocore.se</link>
			<pubDate>' . $time . '</pubDate>
		</item>
	</channel>
</rss>';
	return $rssfeed;
}

function main()
{

	// Name of file to save cookies to

	$prevCookieFile = "cookie.txt";

	// Set up temp file for cookies

	$cookie_jar = tempnam('/tmp', 'cookie');

	// Load previous cookies (if any)

	if (file_exists($prevCookieFile)) {
		$prevCookieData = file_get_contents($prevCookieFile);

		// Save previous cookie to newly created temp cookie file

		file_put_contents($cookie_jar, $prevCookieData);
	}

	// Try to fetch data from server

	$data = fetchNotifData($cookie_jar);

	// If previous call returned null, login and then try fetch data again

	if ($data == NULL) {
		login($cookie_jar);
		$data = fetchNotifData($cookie_jar);
	}

	// Cut off \r\n's at beginning of data

	$data = substr($data, 3, strlen($data) - 3);

	// Decode the data into an json object, saved as an assoc array

	$jsonData = json_decode($data, TRUE);

	// Echo the rssfeed to the page/reader
	
	echo convertDataToRss($jsonData);

	// Save cookie_jar

	file_put_contents($prevCookieFile, file_get_contents($cookie_jar));

	// remove the cookie jar

	unlink($cookie_jar) or die("Can't unlink $cookie_jar");
}

?>

