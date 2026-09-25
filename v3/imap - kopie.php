<?php

require_once 'vendor/autoload.php';
/*
use Webklex\PHPIMAP\ClientManager;

// Nastavení připojení
$cm = new ClientManager();

$client = $cm->make([
    'host'          => 'outlook.office365.com',
    'port'          => 993,
    'encryption'    => 'ssl',
    'validate_cert' => true,
    'username'      => 'masopust@spsejecna.cz', // e-mail uživatele
    'password'      => 'ef330-j16mx',       // OAuth2 access token
    'protocol'      => 'imap',
    'authentication'=> 'oauth2'               // důležité!
]);

try {
    // Připojení k serveru
    $client->connect();

    // Získání složky "Inbox"
    $folder = $client->getFolder('INBOX');

    // Získání posledních 10 nepřečtených zpráv
    $messages = $folder->query()->unseen()->limit(10)->get();

    foreach ($messages as $message) {
        echo "Od: " . $message->getFrom()[0]->mail . "\n";
        echo "Předmět: " . $message->getSubject() . "\n";
        echo "Datum: " . $message->getDate()->format('Y-m-d H:i:s') . "\n";
        echo "Tělo:\n" . $message->getTextBody() . "\n";
        echo "-----------------------------\n";
    }

} catch (Exception $e) {
    echo "Chyba: " . $e->getMessage();
}

exit;
*/

// Nastavení
use GuzzleHttp\Client;
$tenantId = 'cbfce3bc-3bc6-4e18-b684-9e5fa7b80819';
$clientId = '354a8051-b6a3-457a-8277-26a4d984506a';
$clientSecret = 'sIl8Q~hy17mNmwG~sfikBHnDUiVoJ6tEJ7.Bla2k';
$scope = 'https://graph.microsoft.com/.default';
$userEmail = 'masopust@spsejecna.cz';
$searchQuery = 'm.stara@carbounion.cz'; // ← změň podle potřeby

$http = new Client();
$messages[] = array();

// Krok 1: Získání access tokenu
try {
    $response = $http->post("https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token", [
        'form_params' => [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => $scope,
            'grant_type' => 'client_credentials',
        ],
    ]);
    $token = json_decode($response->getBody(), true)['access_token'];
} catch (Exception $e) {
    die("Chyba při získávání tokenu: " . $e->getMessage());
}

// Krok 2: Volání Graph API pro zprávy uživatele
try {
    $response = $http->get("https://graph.microsoft.com/v1.0/users/$userEmail/messages?\$search=\"$searchQuery\"", [
        'headers' => [
            'Authorization' => "Bearer $token",
            'Accept' => 'application/json',
            'ConsistencyLevel' => 'eventual' // nutné pro vyhledávání
        ]
    ]);
    $data = json_decode($response->getBody(), true);

    // Výpis hlaviček zpráv
    foreach ($data['value'] as $message) {
        //print_r($message);
        echo "Předmět: " . $message['subject'] . "\n";
        echo "Od: " . $message['from']['emailAddress']['name'] . " <" . $message['from']['emailAddress']['address'] . ">\n";
        echo "Datum: " . $message['receivedDateTime'] . "\n";
        //[contentType] => html
        //echo "Tělo: " . $message['body']['content'] . "\n";
        $content = strip_tags($message['body']['content']); // očistíme HTML
        //echo $content;exit;
        $pos = stripos(Strip_tags($message['body']['content']), 'From:'); 
        $body = substr(trim($content), 0, $pos);
        echo "Tělo: " .$body;
        
        echo "\n----------------------------------------\n";
        $messages[] = [
                'from'    => $message['from']['emailAddress']['name'] . " <" . $message['from']['emailAddress']['address'] . ">",
                'date'    => $message['receivedDateTime'],
                'subject' =>  $message['subject'],
                'body'    => $body,
            ];
    }
    print_r($messages);
} catch (Exception $e) {
    die("Chyba při načítání zpráv: " . $e->getMessage());
}
?>
