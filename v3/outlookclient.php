<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class OutlookClient {
    private $clientId;
    private $clientSecret;
    private $tenantId;
    private $redirectUri;
    private $accessToken;
    private $httpClient;

    public function __construct($clientId, $clientSecret, $tenantId, $redirectUri) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->tenantId = $tenantId;
        $this->redirectUri = $redirectUri;
        $this->httpClient = new Client();
    }

    public function getAuthUrl($scopes = ['User.Read Mail.Read']) {
        $scopeStr = implode(' ', $scopes);
        return "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/authorize?" . http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'response_mode' => 'query',
            'scope' => $scopeStr,
        ]);
    }

    public function fetchAccessToken($authCode) {
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
            echo "❌ Chyba při získávání access tokenu. Zkontroluj redirect URI v Azure.\n";
            return null;
        }
    }

    public function listInboxMessages($top = 10) {
        try {
            $response = $this->httpClient->get("https://graph.microsoft.com/v1.0/me/mailFolders/inbox/messages?\$top={$top}", [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Accept' => 'application/json',
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            $this->logError($e);
            echo "❌ Chyba při načítání zpráv z inboxu.\n";
            return [];
        }
    }

    private function logError($exception) {
        $message = date('Y-m-d H:i:s') . " - " . $exception->getMessage() . "\n";
        file_put_contents('error_log.txt', $message, FILE_APPEND);
    }
}

// Inicializace
$tenantId = 'cbfce3bc-3bc6-4e18-b684-9e5fa7b80819';
$clientId = '354a8051-b6a3-457a-8277-26a4d984506a';
$clientSecret = 'sIl8Q~hy17mNmwG~sfikBHnDUiVoJ6tEJ7.Bla2k';
$redirectUri = 'https://your-redirect-uri.com';

$outlookClient = new OutlookClient($clientId, $clientSecret, $tenantId, $redirectUri);

// 1. Získání URL pro autorizaci
$authUrl = $outlookClient->getAuthUrl();
echo "🔗 Otevři následující URL pro autorizaci:\n$authUrl\n";

// 2. Získání autorizačního kódu
$authCode = $_GET['code'] ?? null;

if ($authCode) {
    $accessToken = $outlookClient->fetchAccessToken($authCode);
    if ($accessToken) {
        echo "✅ Access Token získán.\n";
        $messages = $outlookClient->listInboxMessages(5);
        echo "📬 Zprávy z inboxu:\n";
        print_r($messages);
    }
} else {
    echo "⏳ Čekám na autorizační kód...\n";
}
