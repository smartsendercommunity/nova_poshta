<?php

include("functions.php");
$input = json_decode(file_get_contents("php://input"), true);

$result["state"] = true;
if ($_SERVER["REQUEST_METHOD"] == 'GET') {
    $result["state"] = true;
    if (file_exists("tokens.php")) {
        include("tokens.php");
        $ssProject = json_decode(send_bearer("https://api.smartsender.com/v1/me", $ssToken), true);
        if ($ssProject["error"] != NULL) {
            $result["smartsender"]["connect"] = false;
            $result["smartsender"]["error"] = $ssProject["error"];
        } else {
            $result["smartsender"]["connect"] = true;
            $result["smartsender"]["account"] = $ssProject;
        }
        $nps = [
            "apiKey" => $npToken,
            "modelName" => "Counterparty",
            "calledMethod" => "getCounterparties",
            "methodProperties" => [
                "CounterpartyProperty" => "Sender"
            ]
        ];
        $npUser = json_decode(send_forward(json_encode($nps), "https://api.novaposhta.ua/v2.0/json/"), true);
        if ($npUser["success"] != true) {
            $result["novaposhta"]["connect"] = false;
            $result["novaposhta"]["error"] = $npUser["errors"];
        } else {
            $result["novaposhta"]["connect"] = true;
        }
        $result["novaposhta"]["data"] = $npUser["data"];
        if ($result["novaposhta"]["data"] != NULL) {
            foreach ($result["novaposhta"]["data"] as &$oneUser) {
                $gus = [
                    "apiKey" => $npToken,
                    "modelName" => "Counterparty",
                    "calledMethod" => "getCounterpartyContactPersons",
                    "methodProperties" => [
                        "Ref" => $oneUser["Ref"]
                    ]
                ];
                $getUser = json_decode(send_forward(json_encode($gus), "https://api.novaposhta.ua/v2.0/json/"), true);
                $oneUser["contacts"] = $getUser["data"];
            }
        }
        if (file_exists("sender.php")) {
            include("sender.php");
            $result["novaposhta"]["sender"] = [
                "name" => $name,
                "phone" => $phone,
                "senderRef" => $senderRef,
                "city" => $city,
                "cityName" => $cityName,
                "warehouse" => $warehouse,
                "warehouseName" => $warehouseName,
            ];
        }
    }
} else if ($input["method"] == "saveApi") {
    if ($input["npKey"] == NULL) {
        $result["state"] = false;
        $result["error"]["form"]["npKey"] = "Вимагається обов'язково";
    }
    if ($input["ssKey"] == NULL) {
        $result["state"] = false;
        $result["error"]["form"]["ssKey"] = "Вимагається обов'язково";
    }
    if ($result["state"] == false) {
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    $ssProject = json_decode(send_bearer("https://api.smartsender.com/v1/me", $input["ssKey"]), true);
    if ($ssProject["error"] != NULL) {
        $result["state"] = false;
        $result["error"]["form"]["ssKey"] = $ssProject["error"]["message"];
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    $ssKey = $input["ssKey"];
    $result["smartsender"]["connect"] = true;
    $result["smartsender"]["account"] = $ssProject;
    $nps = [
        "apiKey" => $input["npKey"],
        "modelName" => "Counterparty",
        "calledMethod" => "getCounterparties",
        "methodProperties" => [
            "CounterpartyProperty" => "Sender"
        ]
    ];
    $npUser = json_decode(send_forward(json_encode($nps), "https://api.novaposhta.ua/v2.0/json/"), true);
    if ($npUser["success"] != true) {
        $result["state"] = false;
        $result["error"]["form"]["npKey"] = $npUser["errors"][0];
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    $npKey = $input["npKey"];
    $result["novaposhta"]["connect"] = true;
    $result["writeTokens"] = file_put_contents("tokens.php", '<?php'.PHP_EOL.PHP_EOL.'$ssToken = "'.$ssKey.'";'.PHP_EOL.'$npToken = "'.$npKey.'";');
    $result["novaposhta"]["data"] = $npUser["data"];
    if ($result["novaposhta"]["data"] != NULL) {
        foreach ($result["novaposhta"]["data"] as &$oneUser) {
            $gus = [
                "apiKey" => $npKey,
                "modelName" => "Counterparty",
                "calledMethod" => "getCounterpartyContactPersons",
                "methodProperties" => [
                    "Ref" => $oneUser["Ref"]
                ]
            ];
            $getUser = json_decode(send_forward(json_encode($gus), "https://api.novaposhta.ua/v2.0/json/"), true);
            $oneUser["contacts"] = $getUser["data"];
        }
    }
} else if ($input["method"] == "saveSender") {
    if ($input["name"] == NULL) {
        $result["state"] = false;
        $result["error"]["message"][] = "'name' Вимагається обов'язково";
    }
    if ($input["phone"] == NULL) {
        $result["state"] = false;
        $result["error"]["message"][] = "'phone' Вимагається обов'язково";
    }
    if ($input["city"] == NULL) {
        $result["state"] = false;
        $result["error"]["message"][] = "'city' Вимагається обов'язково";
    }
    if ($input["warehouse"] == NULL) {
        $result["state"] = false;
        $result["error"]["message"][] = "'warehouse' Вимагається обов'язково";
    }
    if ($input["cityName"] == NULL) {
        $result["state"] = false;
        $result["error"]["message"][] = "'cityName' Вимагається обов'язково";
    }
    if ($input["warehouseName"] == NULL) {
        $result["state"] = false;
        $result["error"]["message"][] = "'warehouseName' Вимагається обов'язково";
    }
    if ($result["state"] == false) {
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    $result["writeSender"] = file_put_contents("sender.php", '<?php'.PHP_EOL.PHP_EOL.'$city = "'.$input["city"].'";'.PHP_EOL.'$warehouse = "'.$input["warehouse"].'";'.PHP_EOL.'$name = "'.$input["name"].'";'.PHP_EOL.'$phone = "'.$input["phone"].'";'.PHP_EOL.'$cityName = "'.$input["cityName"].'";'.PHP_EOL.'$warehouseName = "'.$input["warehouseName"].'";'.PHP_EOL.'$senderRef = "'.$input["senderRef"].'";'.PHP_EOL.'$senderContactRef = "'.$input["senderContactRef"].'";');
    if ($result["writeSender"]) {
        $result["state"] = true;
        $result["sender"] = $input;
    } else {
        $result["state"] = false;
        $result["error"]["message"][] = "failed write sender";
    }
}


echo json_encode($result, JSON_UNESCAPED_UNICODE);
