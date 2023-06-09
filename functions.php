<?php

$url = "https://".$_SERVER["HTTP_HOST"].dirname($_SERVER["PHP_SELF"]);
$url = explode("?", $url);
$url = $url[0];
if (substr($url, -1) != "/") {
    $url = $url."/";
}

function send_bearer($url, $token, $type = "GET", $param = []) {
    $descriptor = curl_init($url);
    curl_setopt($descriptor, CURLOPT_POSTFIELDS, json_encode($param));
    curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($descriptor, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . $token));
    curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $type);
    $itog = curl_exec($descriptor);
    curl_close($descriptor);
    return $itog;
}
function send_forward($inputJSON, $link) {
    $request = "POST";
    $descriptor = curl_init($link);
    curl_setopt($descriptor, CURLOPT_POSTFIELDS, $inputJSON);
    curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($descriptor, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
    curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $request);
    $itog = curl_exec($descriptor);
    curl_close($descriptor);
    return $itog;
}