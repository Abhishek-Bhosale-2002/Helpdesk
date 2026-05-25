<?php

// =======================================
// CONFIGURATION
// =======================================

// Teams webhook
$teamsWebhook = "https://aesoftengineering.webhook.office.com/webhookb2/5efb087d-427a-4955-8fb4-e5da9d3faa2b@9cc6d905-9b96-474b-b135-54cea50dd753/IncomingWebhook/e8a1c2d0b8384c4ba876297bc2f99efe/459ccce4-52fc-4480-9429-12b52a999048/V2xmBec87MKTz9vG1miUeInSqihQLJ17v3pFd450oSGnQ1";

// File to store last ticket ID
$lastid_file = "C:/xampp/htdocs/last_ticket_id.txt";

// GLPI database connection
$host = "localhost";
$dbname = "glpi";
$user = "root";
$pass = "";

// =======================================
// DATABASE CONNECTION
// =======================================

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("DB Connection failed");
}

// =======================================
// GET LAST SENT TICKET ID
// =======================================

$last_id = 0;

if (file_exists($lastid_file)) {
    $last_id = (int)file_get_contents($lastid_file);
}

// =======================================
// GET NEWEST TICKET
// =======================================

$sql = "
SELECT 
    t.id,
    t.name,
    t.content,
    t.status,
    c.completename AS category,
    u.realname,
    u.firstname

FROM glpi_tickets t

LEFT JOIN glpi_itilcategories c
ON t.itilcategories_id = c.id

LEFT JOIN glpi_tickets_users tu
ON t.id = tu.tickets_id
AND tu.type = 1

LEFT JOIN glpi_users u
ON tu.users_id = u.id

WHERE t.id > $last_id

ORDER BY t.id ASC
LIMIT 1
";

$result = $conn->query($sql);

if ($result->num_rows > 0) {

    $row = $result->fetch_assoc();

    $ticket_id = $row['id'];

    $title = $row['name'];

    $content = $row['content'];

    // Remove HTML tags from description
    $content = html_entity_decode($content);
    $content = preg_replace('/<[^>]*>/', '', $content);
    $content = trim($content);

    // Ticket Status
    $status_code = $row['status'];

    $status_map = [
        1 => "New",
        2 => "Processing (Assigned)",
        3 => "Processing (Planned)",
        4 => "Pending",
        5 => "Solved",
        6 => "Closed"
    ];

    $status = $status_map[$status_code] ?? $status_code;

    // Category
    $category = $row['category'] ?? 'N/A';

    // Requester Name
    $requester = trim(($row['firstname'] ?? '') . ' ' . ($row['realname'] ?? ''));

    if (empty($requester)) {
        $requester = 'Unknown';
    }

    // =======================================
    // TEAMS MESSAGE
    // =======================================

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
                            "text" => "New Ticket",
                            "weight" => "Bolder",
                            "size" => "Large"
                        ],

                        [
                            "type" => "FactSet",
                            "facts" => [

                                [
                                    "title" => "Ticket ID :",
                                    "value" => "#" . $ticket_id
                                ],

                                [
                                    "title" => "Title :",
                                    "value" => $title
                                ],

                                [
                                    "title" => "Status :",
                                    "value" => $status
                                ],

                                [
                                    "title" => "Category :",
                                    "value" => $category
                                ],

                                [
                                    "title" => "Requester :",
                                    "value" => $requester
                                ]

                            ]
                        ],

                        [
                            "type" => "TextBlock",
                            "text" => "Description :",
                            "weight" => "Bolder"
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
                            "url" => "http://helpdesk.aesoft.com/front/ticket.form.php?id=" . $ticket_id
                        ]
                    ]
                ]
            ]
        ]
    ];

    $payload = json_encode($teamsData);

    // =======================================
    // SEND TO TEAMS
    // =======================================

    $ch = curl_init($teamsWebhook);

    curl_setopt($ch, CURLOPT_POST, true);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);

    curl_close($ch);

    // =======================================
    // SAVE LAST TICKET ID
    // =======================================

    file_put_contents($lastid_file, $ticket_id);

    echo "Notification sent for ticket #" . $ticket_id;

} else {

    echo "No new tickets";
}

$conn->close();

?>