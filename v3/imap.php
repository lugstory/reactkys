<?php
echo "\n############\n";
require_once 'vendor/autoload.php';

use GuzzleHttp\Client;
$tenantId = 'cbfce3bc-3bc6-4e18-b684-9e5fa7b80819';
$clientId = '354a8051-b6a3-457a-8277-26a4d984506a';
$clientSecret = 'sIl8Q~hy17mNmwG~sfikBHnDUiVoJ6tEJ7.Bla2k';
$scope = 'https://graph.microsoft.com/.default';
$userEmail = 'masopust@spsejecna.cz';
$searchQuery = 'karolina.vostra@abra.eu'; // ← změň podle potřeby
$subject="Slíbená CV naši maturantů";
$searchQuery ="Slíbená CV naši maturantů";
$limit=10;
$x=0;

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
    $response = $http->get(
    "https://graph.microsoft.com/v1.0/users/$userEmail/messages?\$search=\"$searchQuery\"", [
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
        //if ($message['subject']!=$subject) continue;
        echo "Předmět: " . $message['subject'] . "\n";
        echo "Od: " . $message['from']['emailAddress']['name'] . " <" . $message['from']['emailAddress']['address'] . ">\n";
        echo "Datum: " . $message['receivedDateTime'] . "\n";
        //[contentType] => html
        //echo "Tělo: " . $message['body']['content'] . "\n";
        $content = strip_tags($message['body']['content']); // očistíme HTML
        //echo $content;exit;
        $pos = stripos(Strip_tags($message['body']['content']), 'From:'); 
        $body = substr(trim($content), 0, $pos);
        if (strlen($body<3)) $body=$content;
        echo "Tělo: " .$body;
        echo detectEmailStatus($message['subject'].$message['body']['content']);
        echo "\n----------------------------------------\n";
        $messages[] = [
                'from'    => $message['from']['emailAddress']['name'] . " <" . $message['from']['emailAddress']['address'] . ">",
                'date'    => $message['receivedDateTime'],
                'subject' =>  $message['subject'],
                'body'    => $body,
            ];
        $x++;
        //echo "$limit::$x";
        
        
        if ($limit==$x) break;
        
        }
    //print_r($messages[0]);
    
} catch (Exception $e) {
    die("Chyba při načítání zpráv: " . $e->getMessage());
}


function detectEmailStatus(string $text): string {
    // Normalizace: malá písmena, odstranění diakritiky
    $normalized = mb_strtolower(removeDiacritics($text));

    // Slovník stavů: klíč = interní název, hodnoty = varianty klíčových slov
    $statusKeywords = [
        'not_delivered' => ['nedorucen', 'nedoručen', 'not delivered', 'undelivered'],
        'delivered'     => ['dorucen', 'doručen', 'doručeno', 'delivered'],
        'sent'          => ['odeslano', 'odesláno', 'sent'],
        'unread'        => ['neprecteno', 'nepřečteno', 'unread'],
        'read'          => ['precteno', 'přečteno', 'read', 'přečtena'],
        'failed'        => ['selhalo', 'failed'],
        'delayed'       => ['zpozdeno', 'zpožděno', 'delayed'],
        'bounced'       => ['vraceno', 'vráceno', 'bounced'],
        'pending'       => ['ceka na doruceni', 'čeká na doručení', 'pending delivery'],
        'processed'     => ['zpracovano', 'zpracováno', 'processed'],
        'vacation'      => ['dovolen'],
        
    ];

    foreach ($statusKeywords as $status => $keywords) {
        foreach ($keywords as $kw) {
            if (strpos($normalized, removeDiacritics(mb_strtolower($kw))) !== false) {
                return $status;
            }
        }
    }

    return 'unknown';
}

/**
 * Odstraní diakritiku z textu (pro porovnávání).
 */
function removeDiacritics(string $str): string {
    $map = [
        'á'=>'a','č'=>'c','ď'=>'d','é'=>'e','ě'=>'e','í'=>'i','ň'=>'n','ó'=>'o','ř'=>'r','š'=>'s','ť'=>'t','ú'=>'u','ů'=>'u','ý'=>'y','ž'=>'z',
        'Á'=>'A','Č'=>'C','Ď'=>'D','É'=>'E','Ě'=>'E','Í'=>'I','Ň'=>'N','Ó'=>'O','Ř'=>'R','Š'=>'S','Ť'=>'T','Ú'=>'U','Ů'=>'U','Ý'=>'Y','Ž'=>'Z'
    ];
    return strtr($str, $map);
}

?>
