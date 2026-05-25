<?php

$webhook = "https://aesoftengineering.webhook.office.com/webhookb2/5efb087d-427a-4955-8fb4-e5da9d3faa2b@9cc6d905-9b96-474b-b135-54cea50dd753/IncomingWebhook/e8a1c2d0b8384c4ba876297bc2f99efe/459ccce4-52fc-4480-9429-12b52a999048/V2xmBec87MKTz9vG1miUeInSqihQLJ17v3pFd450oSGnQ1";

$data = [
    "type" => "message",
    "attachments" => [
        [
            "contentType" => "application/vnd.microsoft.card.adaptive",
            "content" => [
                "\$schema" => "http://adaptivecards.io/schemas/adaptive-card.json",
                "type" => "AdaptiveCard",
                "version" => "1.4",
                "body" => [
                    [
                        "type" => "TextBlock",
                        "text" => "GLPI Teams Notification",
                        "weight" => "Bolder",
                        "size" => "Medium"
                    ],
                    [
                        "type" => "TextBlock",
                        "text" => "Webhook test successful.",
                        "wrap" => true
                    ]
                ]
            ]
        ]
    ]
];

$payload = json_encode($data);

$ch = curl_init($webhook);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'cURL Error: ' . curl_error($ch);
} else {
    echo "SUCCESS<br><br>";
    echo $response;
}

curl_close($ch);

?>