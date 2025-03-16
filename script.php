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
        "https://api.github.com/user/" . $type . "?per_page=100&page=" . $page
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
    $obj = json_decode($json, true);
    if (isset($obj["message"])) {
        $GLOBALS["message"] = $GLOBALS["message"] . $obj["message"];
    }
    return $json;
}

function notify($telegram_api, $chat_id, $msg)
{
    $cURLConnection = curl_init();
    curl_setopt(
        $cURLConnection,
        CURLOPT_URL,
        "https://api.telegram.org/bot" . $telegram_api . "/sendMessage?chat_id=" . $chat_id . "&parse_mode=html&text=" . urlencode($msg)
    );
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
    $json = curl_exec($cURLConnection);
    curl_close($cURLConnection);
	
}


$username = $argv[1];
$password = $argv[2];
$tokenAPI = $argv[3];
$chatID = $argv[4];

$res = checkCount($username, $password);
$data = json_decode($res, true);

$cTs = $data["followers"];
$cTg = $data["following"];
$cFs = 0;
$cFg = 0;

if ($data["followers"] != $data["following"]) {
    $followers = [];
    $Followers = [];
    $Following = [];
    $following = [];
    $dif1 = [];
    $dif2 = [];

    $z = 1;
    while ($z <= 30) {
        $list = json_decode(
            getUsers($username, $password, "followers", $z),
            true
        );
        if (count($list) == 0) {
            break;
        }
        if ($message != "") {
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
    while ($z <= 30) {
        $list = json_decode(
            getUsers($username, $password, "following", $z),
            true
        );
        if (count($list) == 0) {
            break;
        }
        if ($message != "") {
            break;
        }
        $following = array_merge($list, $following);
        $z++;
    }

    //array_multisort(array_column($followers, 'login'), SORT_ASC, $followers);
    //array_multisort(array_column($following, 'login'), SORT_ASC, $following);

    $change = "";

    foreach ($followers as $fl) {
        $Followers[$fl["login"]] = $fl["html_url"];
    }
	
	$changes = false;
	$ms = "<b>New change</b>" . PHP_EOL  . PHP_EOL;

    foreach ($following as $fl) {
break;
        $Following[$fl["login"]] = $fl["html_url"];
        if (!array_key_exists($fl["login"], $Followers)) {
            $dif2[$fl["login"]] = $fl["html_url"];
            doAction($username, $password, "DELETE", $fl["login"]);
            $change = $change . "Unfollow " . $fl["login"] . PHP_EOL;
            $cFs = $cFs - 1;
			$changes = true;
			$ms .= "⛔ Unfollow -> <a href=\"" .  $fl["html_url"] . "\">" . $fl["login"] . "</a> " . PHP_EOL;
        }
    }
	$ms .=  PHP_EOL;
    foreach ($followers as $fl) {
        if (!array_key_exists($fl["login"], $Following)) {
            $dif1[$fl["login"]] = $fl["html_url"];
            doAction($username, $password, "PUT", $fl["login"]);
            $change = $change . "Follow " . $fl["login"] . PHP_EOL;
            $cFg = $cFg + 1;
			$changes = true;
			$ms .= "✅ Follow -> <a href=\"" .  $fl["html_url"] . "\">" . $fl["login"] . "</a> " . PHP_EOL;
        }
    }
	notify($tokenAPI, $chatID, $ms);
    //file_put_contents("change.txt", $change . $message);
} else {
    //file_put_contents("change.txt", "No changes". $message);
}
//$res = $username;


date_default_timezone_set("UTC");

function generateReadme($used, $limit, $cFs, $cTs, $cFg, $cTg) {
    $readme = "# auto-follow-unfollow\n";
    $readme .= "Follow and unfollow users automatically\n\n";

    $readme .=
        "[![Script](https://github.com/fbiego/auto-follow-unfollow/actions/workflows/main.yml/badge.svg)](https://github.com/fbiego/auto-follow-unfollow/actions/workflows/main.yml)";

    $readme .= "\n### Run details\n";

    $readme .= "- Last run `" . date(DATE_RFC2822) . "`\n";
    $readme .= "- X-RateLimit-Used: `" . $used . "`\n";
    $readme .= "- X-RateLimit-Limit: `" . $limit . "`\n\n";

    $readme .= "|  | Followers | Following |\n";
    $readme .= "| - | --------- | --------- |\n";
    $readme .= "| Current | " . ($cFs + $cTs). " | " . ($cFg + $cTg) . " |\n";
    $readme .= "| Change | " . $cFs . " | " . $cFg . "|\n";

    return $readme;
}

file_put_contents("README.md", generateReadme($used, $limit, $cFs, $cTs, $cFg, $cTg));

?>
