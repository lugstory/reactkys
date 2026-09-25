<?
require_once 'vendor/autoload.php';
require_once 'conn.php';
require_once 'vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig('credentials.json');
$client->addScope('https://www.googleapis.com/auth/contacts');
$client->setAccessType('offline');
/*
// Načti uložený token
$tokenPath = 'token.json';
if (!file_exists($tokenPath)) {
    exit("Chybí token.json. Spusť nejprve ručně přihlášení přes oauth.php.");
}
*/
 $client->authenticate($_GET['code']);
    $accessToken = $client->getAccessToken();
//$accessToken = json_decode(file_get_contents($tokenPath), true);
$client->setAccessToken($accessToken);

// Obnova tokenu pokud expiroval
if ($client->isAccessTokenExpired()) {
    if ($client->getRefreshToken()) {
        $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($tokenPath, json_encode($newToken));
        $client->setAccessToken($newToken);
    } else {
        exit("Chybí refresh token. Spusť znovu ruční přihlášení.");
    }
}

// Nyní můžeš volat People API
$service = new Google_Service_PeopleService($client);
$connections = $service->people_connections->listPeopleConnections('people/me', [
    'personFields' => 'names,emailAddresses'
]);

foreach ($connections->getConnections() as $person) {
    // Zpracuj kontakty...
}
