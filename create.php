<?php

ini_set('max_execution_time', '1700');
set_time_limit(1700);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Content-Type: application/json; charset=utf-8');
http_response_code(200);

$input = json_decode(file_get_contents("php://input"), true);
include("functions.php");

$result["state"] = true;
if (file_exists("tokens.php")) {
    include("tokens.php");
} else {
    $result["state"] = false;
    $result["error"]["message"][] = "Скрипт не налаштовано";
    $result["settingUrl"] = $url;
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}
if (file_exists("sender.php")) {
    include("sender.php");
} else {
    $result["state"] = false;
    $result["error"]["message"][] = "Налаштування скрипта не завершено";
    $result["settingUrl"] = $url;
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}


if ($input["action"] == "confirm") {
    if ($input["requestId"] == NULL) {
        $result["state"] = false;
        $result["error"]["message"][] = "'requestId' is missing";
    } else if (file_exists("pre/".$input["requestId"])) {
        $request = json_decode(file_get_contents("pre/".$input["requestId"]), true);
    } else {
        $result["state"] = false;
        $result["error"]["message"][] = "requestId is not found";
    }
    if ($input["warehouse"] == NULL) {
        $result["state"] = false;
        $result["error"]["message"][] = "'warehouse' is missing";
    }
    if ($result["state"] == false) {
        echo json_encode($result);
        exit;
    }
    // Створення ТТН
    if ($request["posts"][$input["warehouse"]] != NULL) {
        $request["methodProperties"]["CityRecipient"] = $request["posts"][$input["warehouse"]]["CityRef"];
        $request["methodProperties"]["RecipientAddress"] = $request["posts"][$input["warehouse"]]["Ref"];
        unset($request["posts"]);
    } else {
        // Перевірка на наявність передбачених помилок та їх виправлення
        if (in_array(20000204637, $createTTN["errorCodes"])) {
            // Неможливо використати післяплату, використовуємо "Контроль оплати"
            $request["methodProperties"]["AfterpaymentOnGoodsCost"] = $request["methodProperties"]["BackwardDeliveryData"][0]["RedeliveryString"];
            unset($request["methodProperties"]["BackwardDeliveryData"]);
        }
        // Повторна спроба
        $createTTN = json_decode(send_forward(json_encode($request), "https://api.novaposhta.ua/v2.0/json/"), true);
        if ($createTTN["success"] == true) {
            $ttnData = [
                "userId" => explode("-", $input["requestId"])[0],
                "status" => 1,
                "ttn" => $createTTN["data"][0]["IntDocNumber"],
                "phone" => $phone,
            ];
            file_put_contents("ttn/".$createTTN["data"][0]["IntDocNumber"], json_encode($ttnData));
            $result["document"] = $ttnData;
        } else {
            $result["state"] = false;
            $result["error"]["message"][] = "failed create ttn";
            $result["error"]["novaposhta"] = $createTTN;
        }
        $result["state"] = false;
        $result["error"]["message"][] = "'warehouse' is not found in this request";
        echo json_encode($result);
        exit;
    }
    $createTTN = json_decode(send_forward(json_encode($request), "https://api.novaposhta.ua/v2.0/json/"), true);
    if (file_exists("ttn") != true) {
        mkdir("ttn");
    }
    if ($createTTN["success"] == true) {
        $ttnData = [
            "userId" => explode("-", $input["requestId"])[0],
            "status" => 1,
            "ttn" => $createTTN["data"][0]["IntDocNumber"],
            "phone" => $phone,
        ];
        file_put_contents("ttn/".$createTTN["data"][0]["IntDocNumber"], json_encode($ttnData));
        $result["document"] = $ttnData;
    } else {
        $result["state"] = false;
        $result["error"]["message"][] = "failed create ttn";
        $result["error"]["novaposhta"] = $createTTN;
    }
} else {
    if ($input["userId"] == NULL) {
        $result["state"] = false;
        $result["error"]["message"][] = "'userId' is missing";
    }
    if ($input["city"] == NULL) {
        $result["state"] = false;
        $result["error"]["message"][] = "'city' is missing";
    }
    if ($input["post"] == NULL) {
        $result["state"] = false;
        $result["error"]["message"][] = "'post' is missing";
    }
    if ($input["phone"] == NULL) {
        $result["state"] = false;
        $result["error"]["message"][] = "'phone' is missing";
    }
    if ($input["weight"] == NULL) {
        $result["state"] = false;
        $result["error"]["message"][] = "'weight' is missing";
    }
    if ($input["width"] == NULL) {
        $input["width"] = "30";
    }
    if ($input["height"] == NULL) {
        $input["height"] = "30";
    }
    if ($input["length"] == NULL) {
        $input["length"] = "30";
    }
    if ($input["volume"] == NULL) {
        $input["volume"] = "10";
    }
    if ($input["firstName"] == NULL) {
        $result["state"] = false;
        $result["error"]["message"][] = "'firstName' is missing";
    }
    if ($input["lastName"] == NULL) {
        $result["state"] = false;
        $result["error"]["message"][] = "'lastName' is missing";
    }
    if ($input["description"] == NULL) {
        $result["state"] = false;
        $result["error"]["message"][] = "'description' is missing";
    }
    if ($input["payer"] == NULL) {
        $result["state"] = false;
        $result["error"]["message"][] = "'payer' is missing";
    } else {
        $input["payer"] = ucfirst(strtolower($input["payer"]));
        if (in_array($input["payer"], ["Sender","Recipient"]) != true) {
            $result["state"] = false;
            $result["error"]["message"][] = "'payer' must be 'Sender' or 'Recipient'";
        }
    }
    if ($result["state"] == false) {
        echo json_encode($result);
        exit;
    }
    // Створення контрагента
    $contact = [
        "apiKey" => $npToken,
        "modelName" => "Counterparty",
        "calledMethod" => "save",
        "methodProperties" => [
            "FirstName" => $input["firstName"],
            "MiddleName" => "",
            "LastName" => $input["lastName"],
            "Phone" => $input["phone"],
            "Email" => "",
            "CounterpartyType" => "PrivatePerson",
            "CounterpartyProperty" => "Recipient"
        ]
    ];
    $createContact = json_decode(send_forward(json_encode($contact), "https://api.novaposhta.ua/v2.0/json/"), true);
    if ($createContact["success"] != true) {
        $result["state"] = false;
        $result["error"]["message"][] = "failed create contactPerson";
        $result["error"]["novaposhta"] = $createContact;
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    // Пошук відділень
    $post = [
        "apiKey" => $npToken,
        "modelName" => "Address",
        "calledMethod" => "getWarehouses",
        "methodProperties" => [
            "CityName" => $input["city"],
            "WarehouseId" => $input["post"],
        ],
    ];
    $searchPost = json_decode(send_forward(json_encode($post), "https://api.novaposhta.ua/v2.0/json/"), true);
    if ($searchPost["data"] == NULL) {
        $result["state"] = false;
        $result["error"]["message"][] = "failed search post";
        $result["error"]["novaposhta"] = $searchPost;
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    $tempNumber = 1;
    foreach ($searchPost["data"] as $onePost) {
        $result["posts"][] = [
            "tempNumber" => $tempNumber,
            "description" => $onePost["Description"],
            "short" => $onePost["ShortAddress"],
        ];
        $posts[$tempNumber] = ["Ref"=>$onePost["Ref"],"CityRef"=>$onePost["CityRef"]];
        $tempNumber++;
    }
    $tempData = [
        "apiKey" => $npToken,
        "modelName" => "InternetDocument",
        "calledMethod" => "save",
        "methodProperties" => [
            "PayerType" => $input["payer"],
            "PaymentMethod" => "Cash",
            "CargoType" => "Cargo",
            "Weight" => $input["weight"],
            "ServiceType" => "WarehouseWarehouse",
            "SeatsAmount" => "1",
            "Description" => $input["description"],
            "CitySender" => $city,
            "Sender" => $senderRef,
            "SenderAddress" => $warehouse,
            "ContactSender" => $senderContactRef,
            "SendersPhone" => $phone,
            "Recipient" => $createContact["data"][0]["Ref"],
            "ContactRecipient" => $createContact["data"][0]["ContactPerson"]["data"][0]["Ref"],
            "RecipientsPhone" => $input["phone"],
            "OptionsSeat" => [
                [
                    "volumetricVolume" => $input["volume"],
                    "volumetricWidth" => $input["width"],
                    "volumetricLength" => $input["length"],
                    "volumetricHeight" => $input["height"],
                    "weight" => $input["weight"],
                ]
            ]
        ],
        "posts" => $posts
    ];
    if ($input["postpaid"] != NULL) {
        $tempData["methodProperties"]["BackwardDeliveryData"][0] = [
            "CargoType" => "Money",
            "RedeliveryString" => $input["postpaid"],
            "PayerType" => $input["payer"],
        ];
        if ($input["postPayer"] != NULL) {
            $input["postPayer"] = ucfirst(strtolower($input["postPayer"]));
            if (in_array($input["postPayer"], ["Sender","Recipient"]) != true) {
                $tempData["methodProperties"]["BackwardDeliveryData"][0]["PayerType"] = $input["postPayer"];
            }
        }
    }
    // Збереження даних
    if (file_exists("pre") != true) {
        mkdir("pre");
    }
    $requestId = $input["userId"]."-".time()."-".mt_rand(1000000, 9999999);
    $write = file_put_contents("pre/".$requestId, json_encode($tempData, JSON_UNESCAPED_UNICODE));
    if ($write == false) {
        unset($result);
        $result["state"] = false;
        $result["error"]["message"][] = "failed write temp data";
        echo json_encode($result);
        exit;
    }
    $result["requestId"] = $requestId;
}
echo json_encode($result, JSON_UNESCAPED_UNICODE);

