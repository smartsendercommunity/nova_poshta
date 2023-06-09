<?php


include('functions.php');
if (file_exists("tokens.php")) {
    include("tokens.php");
} else {
    $result["state"] = false;
    $result["error"]["message"][] = "script is not configured";
    echo json_encode($result);
    exit;
}

$setDir = dirname($_SERVER["SCRIPT_FILENAME"])."/ttn/";

$ttnData = [
  "apiKey" => $npToken,
	"modelName" => "InternetDocument",
	"calledMethod" => "getDocumentList",
	"methodProperties" => [
		"DateTimeFrom" => date("d.m.Y", strtotime("-1 month")),
		"DateTimeTo" => date("d.m.Y"),
		"GetFullList" => "1",
    ],
];
$getTtnList = json_decode(send_forward(json_encode($post), "https://api.novaposhta.ua/v2.0/json/"), true);
if ($getTtnList["data"] != NULL) {
    foreach ($getTtnList["data"] as $oneTTN) {
        if (file_exists($setDir.$oneTTN["IntDocNumber"])) {
            $tempTTN = json_decode(file_get_contents($setDir.$oneTTN["IntDocNumber"]), true);
            if ($tempTTN["status"] != $oneTTN["StateId"]) {
                $update = ["values" => ["np_tracking" => $oneTTN["IntDocNumber"]]];
                send_bearer("https://api.smartsender.com/v1/contacts/".$tempTTN["userId"], $ssToken, "PUT", $update);
                $tempTTN["status"] = $oneTTN["StateId"];
                file_put_contents($setDir.$oneTTN["IntDocNumber"], json_encode($tempTTN));
            }
        }
    }
}

