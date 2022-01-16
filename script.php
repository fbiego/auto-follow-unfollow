<?php

$limit = 5000;
$used = 0;

function getHeaders($curl, $header_line)
	{
	    //$GLOBALS['message'] = $GLOBALS['message']. $header_line . "<br>";
	    if (strpos($header_line, "X-RateLimit-Limit:") !== false)
	    {
	        $GLOBALS['limit'] = (int)preg_replace('/[^0-9]/', '', $header_line);
	    }
	    if (strpos($header_line, "X-RateLimit-Used:") !== false)
	    {
	        $GLOBALS['used'] = (int)preg_replace('/[^0-9]/', '', $header_line);
	    }
	    return strlen($header_line);
	}


function checkCount()
{
    $cURLConnection = curl_init();

    curl_setopt($cURLConnection, CURLOPT_URL, 'https://api.github.com/user');
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($cURLConnection, CURLOPT_HEADERFUNCTION, "getHeaders");
    curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
        'Accept: application/vnd.github.v3+json',
        'User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Mobile Safari/537.36'
    ));

    $result = curl_exec($cURLConnection);
	return $result;
}


function doAction($action, $user)
{
    $cURLConnection = curl_init();

    curl_setopt($cURLConnection, CURLOPT_URL, 'https://api.github.com/user/following/' . $user);
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($cURLConnection, CURLOPT_CUSTOMREQUEST, $action);
	curl_setopt($cURLConnection, CURLOPT_HEADERFUNCTION, "getHeaders");
    curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
        'Accept: application/vnd.github.v3+json',
        'User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Mobile Safari/537.36'
    ));

    $result = curl_exec($cURLConnection);
	return $result;
}


$username = $argv[1];
$token = $argv[2];


?>