<?php
//namespace App\Mail;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class MailAgent
{
  private string $tenantId;
  private string $clientId;
  private string $clientSecret;
  private string $scope;
  private string $token;
  private Client $http;
  private string $userEmail;

  public function __construct()
  {

    $tenantId = 'cbfce3bc-3bc6-4e18-b684-9e5fa7b80819';
    $clientId = '354a8051-b6a3-457a-8277-26a4d984506a';
    $clientSecret = 'sIl8Q~hy17mNmwG~sfikBHnDUiVoJ6tEJ7.Bla2k';
    $scope = 'https://graph.microsoft.com/.default';
    $userEmail = 'masopust@spsejecna.cz';

    $this->tenantId = $tenantId;
    $this->clientId = $clientId;
    $this->clientSecret = $clientSecret;
    $this->scope = $scope;
    $this->http = $http ?? new Client();
    $this->userEmail = $userEmail;
    $this->getAccessToken();

  }

  /**
   * Získá access token z Azure AD (client_credentials).
   *
   * @throws \RuntimeException|GuzzleException
   */
  public function getAccessToken(): string
  {
    try {
      $response = $this->http->post("https://login.microsoftonline.com/$this->tenantId/oauth2/v2.0/token", [
        'form_params' => [
          'client_id' => $this->clientId,
          'client_secret' => $this->clientSecret,
          'scope' => $this->scope,
          'grant_type' => 'client_credentials',
        ],
      ]);
      return $this->token = json_decode($response->getBody(), true)['access_token'];
    } catch (Exception $e) {
      die("Chyba při získávání tokenu: " . $e->getMessage());
    }
  }

  /**
   * Vyhledá zprávy uživatele pomocí Graph API /messages?$search="..."
   * @param string $userEmail UPN/e-mail účtu
   * @param string $searchQuery Hledaný text (podporuje předmět a tělo)
   * @param int    $limit Maximální počet záznamů
   * @return array{messages: array<int, array{from:string,date:string,subject:string,body:string}>, raw: array}
   * @throws \RuntimeException|GuzzleException
   */

  public function searchMessages(
    string $email = null,
    int $contact_id,
    ?string $subject = null,
    ?string $id = null,
    int $limit = 10
  ): array {
    $messages = array();
    if ($email == null)
      return array();
    if (!empty($id)) {
      $searchQuery = urlencode("{#$id}");
    } else {
      $searchQuery = $email . " " . $subject;
    }
    echo "searchMessages: $email: $subject \n";

  $searchTerm = $email;
  if (!empty($subject)) {
    // Odstraníme uvozovky a speciální znaky z předmětu
    $cleanSubject = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $subject);
    $searchTerm .= " " . $cleanSubject;
  }

  // Odstraníme nadbytečné mezery
  $searchTerm = trim(preg_replace('/\s+/', ' ', $searchTerm));
  
  // URL encode celého search parametru
  $url = "https://graph.microsoft.com/v1.0/users/{$this->userEmail}/messages?\$search=" . urlencode('"' . $searchTerm . '"');

  try {
    $response = $this->http->get($url, [
      'headers' => [
        'Authorization'    => "Bearer {$this->token}",
        'Accept'           => 'application/json',
        'ConsistencyLevel' => 'eventual'
      ]
    ]);
    
      $data = json_decode($response->getBody(), true);
       echo "<pre>";  
        print_r($data);


      // Výpis hlaviček zpráv
      foreach ($data['value'] as $message) {
       // echo "<pre>";  
        //print_r($message);
        //echo "------------------------<br>\n";

        $content = strip_tags($message['body']['content']);
        //$pos = stripos(Strip_tags($message['body']['content']), 'From:');
        //$body = substr(trim($content), 0, $pos);

        //if (strlen($body < 3))
        $body = $content;

        $hasId = false;
        if (!empty($id)) {
          $hasId = (stripos($body, "{#$id}") !== false);
          echo "je id:" . (stripos($body, "{#$id}"));
          if (!$hasId) {// když není vyhodnotucji dle předmětu a tak


        if (stripos($message['subject'], $subject) !== false) {
            print_r($message);
            }

            // hledání shodu s předmětem
            //echo stripos($message['subject'], $subject) ;
            if (strlen($subject)) {
              if ($message['subject'] == null)
                continue;
              if (stripos($message['subject'], $subject) === false) {
                //echo "Nedetekováno dle předmětu: ";
                //print_r($message);
                continue;
              }
            }
            echo "nalezeno\n";
            
            
            $email = mb_strtolower($email);

            //echo mb_strtolower($message['from']['emailAddress']['address']) . " !=" . $email;

            $recipient = $message['from']['emailAddress']['address'];

            if (mb_strtolower($message['from']['emailAddress']['address']) != $email) {
              // pokud není shoda, hledávem v těle pr případ, že se odpovídá z jiné adresy.

              if (stripos($message['from']['emailAddress']['address'], $body) === false)
                continue;
            }

          }
        }

        //$status = $this->detectEmailStatus($message['subject'] . $body);
        $status = $this->detectEmailStatus($message['subject']);

        $recipient = mb_strtolower($recipient);
        $messages[] = [
          'from' => mb_strtolower($recipient),
          'contact_id' => $contact_id,
          'date' => $message['receivedDateTime'],
          'subject' => $message['subject'],
          //'body' => $body,
          'status' => $status
        ];

      }

      return $messages;
    } catch (Exception $e) {

      die("Chyba při načítání zpráv: " . $e->getMessage());
    }

  }
  /*
public function searchMessages(
string $email = null,
int $contact_id,
?string $subject = null,
?string $id = null,
int $limit = 10
): array {
$messages = [];
if ($email == null) return [];

$searchQuery = trim(($email ?? '') . ' ' . ($subject ?? ''));

try {
  $response = $this->http->get(
    "https://graph.microsoft.com/v1.0/users/$this->userEmail/messages?\$search=\"" . addslashes($searchQuery) . "\"",
    [
      'headers' => [
        'Authorization'       => "Bearer $this->token",
        'Accept'              => 'application/json',
        'ConsistencyLevel'    => 'eventual'
      ]
    ]
  );
  $data = json_decode($response->getBody(), true);

  foreach ($data['value'] as $message) {
    // Tělo zprávy bez HTML
    $content = isset($message['body']['content']) ? $message['body']['content'] : '';
    $body = strip_tags((string)$content);

    // 1) Korelační token {#ID} – pokud $id zadáno, a token v těle chybí, přeskoč
    $hasId = false;
    if (!empty($id)) {
      $hasId = (stripos($body, "{#$id}") !== false);
      if (!$hasId) {
        continue;
      }
    }

    // 2) Filtrování dle předmětu (pokud je zadaný)
    if (!empty($subject)) {
      $msgSubject = (string)($message['subject'] ?? '');
      if ($msgSubject === '' || stripos($msgSubject, $subject) === false) {
        continue;
      }
    }

    // 3) Kontrola odesílatele
    $expectedEmail = mb_strtolower($email);
    $from = mb_strtolower((string)($message['from']['emailAddress']['address'] ?? ''));

    // Pomocné – domény
    $getDomain = static function (string $addr): string {
      $pos = strrpos($addr, '@');
      return ($pos !== false) ? substr($addr, $pos + 1) : '';
    };

    $fromDomain = $getDomain($from);
    $expectedDomain = $getDomain($expectedEmail);

    $senderOk = false;

    if ($from !== '' && $from === $expectedEmail) {
      // Přesná shoda (nejpřísnější varianta)
      $senderOk = true;
    } else {
      if ($hasId) {
        // Máme {#ID} – stačí shoda domény
        if ($fromDomain !== '' && $fromDomain === $expectedDomain) {
          $senderOk = true;
        }
      }

      if (!$senderOk) {
        // Poslední možnost: původní adresa se vyskytuje v těle (citace, forward)
        // (Oprava původní chyby – hledáme $expectedEmail v $body, ne opačně)
        if ($expectedEmail !== '' && stripos($body, $expectedEmail) !== false) {
          $senderOk = true;
        }
      }
    }

    if (!$senderOk) {
      continue;
    }

    // 4) Stav (detekce keywords)
    $status = $this->detectEmailStatus(($message['subject'] ?? '') . ' ' . $body);

    $messages[] = [
      'from'       => $from,
      'contact_id' => $contact_id,
      'date'       => $message['receivedDateTime'] ?? '',
      'subject'    => (string)($message['subject'] ?? ''),
      // 'body'     => $body, // případně odkomentuj, pokud chceš vracet tělo
      'status'     => $status,
    ];
  }

  return $messages;
} catch (\Exception $e) {
  die("Chyba při načítání zpráv: " . $e->getMessage());
}
}*/

  /**
   * Detekce stavu z textu zprávy (předmět+tělo).
   * Vrací např. 'not_delivered', 'delivered', 'sent', 'unread', 'read', ...
   */
  public function detectEmailStatus(string $text): string
  {
    echo "detectEmailStatus: " . $normalized = mb_strtolower($this->removeDiacritics($text));

    $statusKeywords = [
      'not_delivered' => ['nedorucen', 'nedoručen', 'not delivered', 'undelivered', 'Nedoručitelná'],
      'vacation' => ['dovolen', 'Automatická', 'Automatic', 'Out of Office', 'Mimo kancelář'], // dovolená / out-of-office
      're' => ['Re: ', 'Odp: ', 'FW:'],
      //'sent' => ['odeslano', 'odesláno', 'sent'],
      'unread' => ['neprecteno', 'nepřečteno', 'unread', 'not read'],
      'read' => ['precteno', 'přečteno', 'read', 'přečtena'],
      'failed' => ['selhalo', 'failed', 'deleted'],
      'delayed' => ['zpozdeno', 'zpožděno', 'delayed'],
      'bounced' => ['vraceno', 'vráceno', 'bounced'],
      'pending' => ['ceka na doruceni', 'čeká na doručení', 'pending delivery'],
      'processed' => ['zpracovano', 'zpracováno', 'processed'],
      'delivered' => ['dorucen', 'doručen', 'doručeno', 'delivered']
      
      //'accept' => ['Přijato', 'zpracováno', 'processed'],
      //'declined' => ['Odmítnuto', 'declined']

    ];

    foreach ($statusKeywords as $status => $keywords) {
      foreach ($keywords as $kw) {
        $needle = mb_strtolower($this->removeDiacritics($kw));
        if (strpos($normalized, $needle) !== false) {
          return $status;
        }
      }
    }
    return 'unknown';
  }

  /**
   * Utility: odstraní českou diakritiku.
   */
  private function removeDiacritics(string $str): string
  {
    $map = [
      'á' => 'a',
      'č' => 'c',
      'ď' => 'd',
      'é' => 'e',
      'ě' => 'e',
      'í' => 'i',
      'ň' => 'n',
      'ó' => 'o',
      'ř' => 'r',
      'š' => 's',
      'ť' => 't',
      'ú' => 'u',
      'ů' => 'u',
      'ý' => 'y',
      'ž' => 'z',
      'Á' => 'A',
      'Č' => 'C',
      'Ď' => 'D',
      'É' => 'E',
      'Ě' => 'E',
      'Í' => 'I',
      'Ň' => 'N',
      'Ó' => 'O',
      'Ř' => 'R',
      'Š' => 'S',
      'Ť' => 'T',
      'Ú' => 'U',
      'Ů' => 'U',
      'Ý' => 'Y',
      'Ž' => 'Z',
    ];
    return strtr($str, $map);
  }
}
