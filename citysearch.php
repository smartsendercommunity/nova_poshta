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

$result["term"] = $input["term"];
if ($input["term"] == NULL) {
    $result["state"] = false;
    $result["error"]["message"][] = "'term' is missing";
    $result["cityes"] = [];
    echo json_encode($result);
    exit;
}

$cs = [
    "apiKey" => $npToken,
    "modelName" => "Address",
    "calledMethod" => "searchSettlements",
    "methodProperties"=> [
        "CityName" => $input["term"],
        "Page" => 1,
        "Limit" => 25,
    ],
];
$cityes = json_decode(send_forward(json_encode($cs), "https://api.novaposhta.ua/v2.0/json/"), true);
if ($cityes["data"][0]["TotalCount"] >= 1) {
    $result["cityes"] = $cityes["data"][0]["Addresses"];
} else {
    $result["state"] = false;
    $result["error"]["message"][] = "not found";
    $result["cityes"] = [];
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);