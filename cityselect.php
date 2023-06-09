<?php

include("functions.php");
$input = json_decode(file_get_contents("php://input"), true);

$result["state"] = true;
if (file_exists("tokens.php")) {
    include("tokens.php");
} else {
    $result["state"] = false;
    $result["error"]["message"][] = "scripts is not connected";
    echo json_encode($result);
    exit;
}

if ($input["cityRef"] == NULL) {
    $result["state"] = false;
    $result["error"]["message"][] = "'cityRef' is missing";
    $result["cityes"] = [];
    echo json_encode($result);
    exit;
}

$cs = [
    "apiKey" => $npToken,
    "modelName" => "Address",
    "calledMethod" => "getWarehouses",
    "methodProperties"=> [
        "CityRef" => $input["cityRef"],
    ],
];
$cityes = json_decode(send_forward(json_encode($cs), "https://api.novaposhta.ua/v2.0/json/"), true);
$result["temp"] = [
    "send" => $cs,
    "result" => $cityes,
];
if ($cityes["info"]["totalCount"] >= 1) {
    $result["wh"] = $cityes["data"];
} else {
    $result["state"] = false;
    $result["error"]["message"][] = "not found";
    $result["wh"] = [];
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);