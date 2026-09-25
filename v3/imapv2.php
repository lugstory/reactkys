<?
require_once 'vendor/autoload.php';

use Webklex\PHPIMAP\ClientManager;

$cm = new ClientManager();

 function fetchAccessToken($authCode) {
        try {
            $response = $this->httpClient->post("https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token", [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'scope' => 'https://graph.microsoft.com/.default',
                    'code' => $authCode,
                    'redirect_uri' => $this->redirectUri,
                    'grant_type' => 'authorization_code',
                    'client_secret' => $this->clientSecret,
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $this->accessToken = $data['access_token'];
            return $this->accessToken;
        } catch (RequestException $e) {
            $this->logError($e);
            echo "? Chyba p�i z�sk�v�n� access tokenu. Zkontroluj redirect URI v Azure.\n";
            return null;
        }
    }

$client = $cm->make([
    'host'          => 'outlook.office365.com',
    'port'          => 993,
    'encryption'    => 'ssl',
    'validate_cert' => true,
    'protocol'      => 'imap',
    'username'      => 'masopust@spsejecna.cz',
    'password'      => fetchAccessToken(),
    'authentication'=> 'oauth',
]);

$client->connect();

$folders = $client->getFolders();

foreach ($folders as $folder) {
    echo "Složka: " . $folder->name . "\n";

    $messages = $folder->messages()->all()->limit(5)->get();

    foreach ($messages as $message) {
        echo "P�edm�t: " . $message->getSubject() . "\n";
        echo "Od: " . $message->getFrom()[0]->mail . "\n";
        echo "Datum: " . $message->getDate()->format('Y-m-d H:i:s') . "\n\n";
    }
}
