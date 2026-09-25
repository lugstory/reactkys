<?
require_once 'vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig('credentials.json');
$client->setRedirectUri('https://crm.skch.cz/v3/syncContacts.php');
$client->addScope('https://www.googleapis.com/auth/contacts');
$client->setAccessType('offline');
$client->setPrompt('consent');

if (!isset($_GET['code'])) {
    $authUrl = $client->createAuthUrl();
    header('Location: ' . $authUrl);
    exit;
} else {
    $client->authenticate($_GET['code']);
    $accessToken = $client->getAccessToken();
    file_put_contents('token.json', json_encode($accessToken));
    echo "Token uložen.";
}
