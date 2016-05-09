<?php
/**
 * ECrssnotifications
 */

// Login credentials
// This is all you have to set
$username = "user123";
$password = "pass123";


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

// Try to fetch data from server, if call returns null, try and fetch data again
$data = fetchNotifData($cookie_jar);
if ($data == null) {
    login($cookie_jar, $username, $password);
    $data = fetchNotifData($cookie_jar);
}

// Cut off \r\n's at beginning of data
$data = substr($data, 3, strlen($data) - 3);

$jsonData = json_decode($data, true);

// Set header and echo the rssfeed to the page/reader
echo rssDocument($jsonData);

// Save cookie_jar
file_put_contents($prevCookieFile, file_get_contents($cookie_jar));

// remove the cookie jar
unlink($cookie_jar) or die("Can't unlink $cookie_jar");

/**
 * Functions
 */

function login($ckjar, $username, $password)
{
    $postdata = 'u_user=' . $username . '&u_pass=' . $password;

    // Send log in request
    curl('https://www.emocore.se/actions.php?action=log_in', $ckjar, $postdata);
}

function fetchNotifData($ckjar)
{
    $page = curl('https://www.emocore.se/_hidden/update.php?json=1', $ckjar);

    return $page;
}

function rssDocument($jsonData)
{
    header("Content-Type: application/rss+xml; charset=UTF-8");

    return convertDataToRss($jsonData);
}

function convertDataToRss($data = [])
{
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

function curl($url, $ckjar = null, $postdata = null)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CAINFO, realpath(dirname(__FILE__)) . "\\certs.pem"); // CAINFO needs absolute path to work in apache
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    if ($postdata != null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_POST, 1);
    }
    if ($ckjar != null) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $ckjar);
    }
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}