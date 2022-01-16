<?php

$limit = 5000;
$used = 0;
$message = "";

function getHeaders($curl, $header_line)
{
    if (strpos($header_line, "X-RateLimit-Limit:") !== false) {
        $GLOBALS["limit"] = (int) preg_replace("/[^0-9]/", "", $header_line);
    }
    if (strpos($header_line, "X-RateLimit-Used:") !== false) {
        $GLOBALS["used"] = (int) preg_replace("/[^0-9]/", "", $header_line);
    }
    return strlen($header_line);
}

function checkCount($u, $p)
{
    $cURLConnection = curl_init();
    curl_setopt($cURLConnection, CURLOPT_URL, "https://api.github.com/user");
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($cURLConnection, CURLOPT_USERPWD, $u . ":" . $p);
    curl_setopt($cURLConnection, CURLOPT_HEADERFUNCTION, "getHeaders");
    curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, [
        "Accept: application/vnd.github.v3+json",
        "User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Mobile Safari/537.36",
    ]);

    $result = curl_exec($cURLConnection);
    curl_close($cURLConnection);
    return $result;
}

function doAction($u, $p, $action, $user)
{
    $cURLConnection = curl_init();
    curl_setopt(
        $cURLConnection,
        CURLOPT_URL,
        "https://api.github.com/user/following/" . $user
    );
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($cURLConnection, CURLOPT_USERPWD, $u . ":" . $p);
    curl_setopt($cURLConnection, CURLOPT_CUSTOMREQUEST, $action);
    curl_setopt($cURLConnection, CURLOPT_HEADERFUNCTION, "getHeaders");
    curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, [
        "Accept: application/vnd.github.v3+json",
        "User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Mobile Safari/537.36",
    ]);
    $result = curl_exec($cURLConnection);
    curl_close($cURLConnection);
    return $result;
}

function getUsers($u, $p, $type, $page)
{
    $cURLConnection = curl_init();
    curl_setopt(
        $cURLConnection,
        CURLOPT_URL,
        "https://api.github.com/users/" . $type . "?per_page=100&page=" . $page
    );
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($cURLConnection, CURLOPT_USERPWD, $u . ":" . $p);
    curl_setopt($cURLConnection, CURLOPT_HEADERFUNCTION, "getHeaders");
    curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, [
        "Accept: application/vnd.github.v3+json",
        "User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Mobile Safari/537.36",
    ]);
    $json = curl_exec($cURLConnection);
    curl_close($cURLConnection);
	$obj = json_decode($json);
	$GLOBALS['message'] = $GLOBALS['message'] . $obj['message'];
    return $json;
}

$username = $argv[1];
$password = $argv[2];

$res = checkCount($username, $password);
$data = json_decode($res, true);

if ($data['followers'] != $data['following']){
	$followers = array();
	$Followers = [];
	$Following = [];
	$following = array();
	$dif1 = [];
	$dif2 = [];
	
	$z = 1;
	    while ($z <= 30)
	    {
	        $list = json_decode(getUsers($username, $password, "followers", $z) , true);
	        if (count($list) == 0)
	        {
	            break;
	        }
	        if ($message != "")
	        {
	            break;
	        }
	        $followers = array_merge($list, $followers);
			
			//foreach ($list as $lg){
			//	array_push($followers, $lg['login'];
			//}
	        $z++;
	    }
	
	    //query following
	    $z = 1;
	    while ($z <= 30)
	    {
	        $list = json_decode(getUsers($username, $password, "following", $z) , true);
	        if (count($list) == 0)
	        {
	            break;
	        }
	        if ($message != "")
	        {
	            break;
	        }
	        $following = array_merge($list, $following);
	        $z++;
	    }
		
		array_multisort(array_column($followers, 'login'), SORT_ASC, $followers);
		array_multisort(array_column($following, 'login'), SORT_ASC, $following);
		
		$change = "";
	    
	    foreach($followers as $fl){
	        $Followers[$fl['login']] = $fl['html_url'];
	    }
		
		foreach($following as $fl){
	        $Following[$fl['login']] = $fl['html_url'];
	        if(!array_key_exists($fl['login'], $Followers)){
	            $dif2[$fl['login']] = $fl['html_url'];
	            doAction($username, $password, "DELETE", $fl['login']);
				$change = $change . "Unfollow ". $fl['login'] .PHP_EOL;
	        }
	    }
	    foreach($followers as $fl){
	        if(!array_key_exists($fl['login'], $Following)){
	            $dif1[$fl['login']] = $fl['html_url'];
				doAction($username, $password, "PUT", $fl['login']);
				$change = $change . "Follow ". $fl['login'] .PHP_EOL;
	        }
	    }
		file_put_contents("change.txt", $change);
	
} else {
	file_put_contents("change.txt", "No changes");
}
//$res = $username;



?>
