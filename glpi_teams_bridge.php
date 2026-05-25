<?php

// ================================
// LOGGING
// ================================

$raw = file_get_contents("php://input");

file_put_contents(
    "C:/xampp/htdocs/webhook_log.txt",
    "============================\n" .
    date("Y-m-d H:i:s") . "\n" .
    $raw . "\n\n",
    FILE_APPEND
);

// ================================
// MICROSOFT TEAMS WEBHOOK URL
// ================================

$teamsWebhook = "https://aesoftengineering.webhook.office.com/webhookb2/5efb087d-427a-4955-8fb4-e5da9d3faa2b@9cc6d905-9b96-474b-b135-54cea50dd753/IncomingWebhook/e8a1c2d0b8384c4ba876297bc2f99efe/459ccce4-52fc-4480-9429-12b52a999048/V2xmBec87MKTz9vG1miUeInSqihQLJ17v3pFd450oSGnQ1";

// ================================
// DECODE GLPI JSON
// ================================

$data = json_decode($raw, true);

// ================================
// EXTRACT TICKET DATA
// ================================

// Default values
$ticket_id = "N/A";
$title = "New Ticket";
$status = "Unknown";
$content = "";

// Try common GLPI webhook fields
if (isset($data['id'])) {
    $ticket_id = $data['id'];
}

if (isset($data['name'])) {
    $title = $data['name'];
}

if (isset($data['status'])) {
    $status = $data['status'];
}

if (isset($data['content'])) {
    $content = strip_tags($data['content']);
}

// ================================
// TEAMS ADAPTIVE CARD
// ================================

$teamsData = [
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
                        "text" => "GLPI Ticket Notification",
                        "weight" => "Bolder",
                        "size" => "Large"
                    ],

                    [
                        "type" => "FactSet",
                        "facts" => [

                            [
                                "title" => "Ticket ID",
                                "value" => "#" . $ticket_id
                            ],

                            [
                                "title" => "Title",
                                "value" => $title
                            ],

                            [
                                "title" => "Status",
                                "value" => $status
                            ]

                        ]
                    ],

                    [
                        "type" => "TextBlock",
                        "text" => $content,
                        "wrap" => true
                    ]

                ],

                "actions" => [
                    [
                        "type" => "Action.OpenUrl",
                        "title" => "Open Ticket",
                        "url" => "https://helpdesk.aesoft.com/front/ticket.form.php?id=" . $ticket_id
                    ]
                ]
            ]
        ]
    ]
];

// ================================
// SEND TO TEAMS
// ================================

$payload = json_encode($teamsData);

$ch = curl_init($teamsWebhook);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);

if (curl_errno($ch)) {

    file_put_contents(
        "C:/xampp/htdocs/webhook_log.txt",
        "CURL ERROR: " . curl_error($ch) . "\n\n",
        FILE_APPEND
    );

    echo "CURL ERROR: " . curl_error($ch);

} else {

    file_put_contents(
        "C:/xampp/htdocs/webhook_log.txt",
        "TEAMS RESPONSE:\n" . $response . "\n\n",
        FILE_APPEND
    );

    echo "SUCCESS";
}

curl_close($ch);

?>