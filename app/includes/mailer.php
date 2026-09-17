<?php
/**
 * Global Mailer Dispatcher via Mailtrap Sandbox API
 * Forces transmission over the correct backend developer testing subdomains
 */
function send_campuslink_email(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
    // 1. Paste your explicit API Token string here
    $apiToken = '37d7645999888142ea5220b89af7dd05';     
    // 2. The exact API endpoint URL for your testing sandbox inbox
    $url = "https://mailtrap.io";

    // 3. Mailtrap Sandbox API requires arrays nested in exactly this property structure
    $payload = [
        'from' => [
            'email' => 'no-reply@campuslink.local',
            'name'  => 'CampusLink'
        ],
        'to' => [
            [
                'email' => $toEmail,
                'name'  => $toName
            ]
        ],
        'subject' => $subject,
        'html'    => $htmlBody
    ];

    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer " . $apiToken,
            "Content-Type: application/json"
        ],
        // Safety overrides for your local XAMPP Windows architecture
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Mailtrap returns an HTTP 200 code only when it accepts the mail into your inbox panel
    if ($httpCode === 200) {
        return true;
    }

    // Logs the hidden rejection code straight to your apache logs if Mailtrap turns it away
    error_log("Mailtrap API Rejection Status Code [{$httpCode}]: " . $response);
    return false;
}
