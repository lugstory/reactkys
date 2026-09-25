<?php
date_default_timezone_set('Europe/Prague');
require_once './campaign.php';
require_once './mailAgent.php';
require_once '../dbdriver.php';
require_once "update_campaingns_count.php";
// --- Konfigurace ---
const CRON_BATCH_SIZE = 15;                              // výchozí počet kontaktů na 1 běh


class Logger
{
  private string $file;
  public function __construct(string $file)
  {
    $this->file = $file;
  }
  public function info(string $msg): void
  {
    $this->write('INFO', $msg);
  }
  public function error(string $msg): void
  {
    $this->write('ERROR', $msg);
  }
  private function write(string $level, string $msg): void
  {
    file_put_contents($this->file, sprintf("[%s] [%s] %s\n", date('Y-m-d H:i:s'), $level, $msg), FILE_APPEND);
  }
}

class CampaignRepository
{
  private mysqli $db;
  public function __construct(mysqli $db)
  {
    $this->db = $db;
  }

  /**
   * Vrátí kampaně s end_date v budoucnu nebo NULL.
   */
  public function getActiveCampaigns(): array
  {
    $sql = "
            SELECT
                id,
                name,
                created_date,
                sent_date_time,
                end_date,
                send_main_contact,
                send_other_contact,
                secondary_recipient_ids,
                recipient_count,
                undelivered_count,
                confirmed_received_count,
                replied_count,
                note
            FROM email_campaign
            WHERE end_date IS NOT NULL AND end_date >= CURDATE() 
            ORDER BY id ASC
        ";
    $res = $this->db->query($sql);
    if ($res === false) {
      throw new RuntimeException("Dotaz na kampaně selhal: " . $this->db->error);
    }
    $rows = [];
    while ($row = $res->fetch_assoc()) {
      $rows[] = $row;
    }
    $res->free();
    return $rows;
  }
}



/**
 * Služba, která připraví payload pro search_status a volá tvou metodu.
 */
class CampaignStatusService
{
  private Logger $logger;

  public function __construct(Logger $logger)
  {
    $this->logger = $logger;
  }


}

/**
 * Orchestrace cronu.
 */
class CampaignStatusCron
{
  private CampaignRepository $campaignRepo;
  private SendingStatsRepository $sendingRepo;
  private CampaignStatusService $statusService;
  private Logger $logger;
  private Campaigns $campaigns;
  private MailAgent $mailAgent;
  public Array $campaigns_result;
  private dbdriver $mysqli;
  public String $last_procesed_contact;

  /**
 * Zjistí, zda už byly obslouženy všechny kontakty kampaně.
 * Pokud ano, provede reset (stav zpracování + volitelně i statusy/countery).
 *
 * @return bool true = kampaň byla dokončena a resetnuta, jinak false
 */
public function finalizeCampaignIfCompleted(int $campaignId, bool $resetSendingStatuses = true): bool
{
    $campaignId = (int)$campaignId;
    if ($campaignId < 1) return false;

    // kolik kontaktů má kampaň celkem
    $contacts = $this->campaigns->getCampaignContacts($campaignId); // používáš už v run() [1](https://spsejecnacz-my.sharepoint.com/personal/masopust_spsejecna_cz/Documents/Soubory%20chatu%20Microsoft%20Copilot/mailing_cron.php)
    $total = is_array($contacts) ? count($contacts) : 0;

    // kolik jich bylo zpracováno (dle campaign_processing_state)
    $processed = $this->getProcessedCount($campaignId); // existuje už teď [1](https://spsejecnacz-my.sharepoint.com/personal/masopust_spsejecna_cz/Documents/Soubory%20chatu%20Microsoft%20Copilot/mailing_cron.php)

    // Logika dokončení:
    // - pokud není žádný kontakt, beru jako hotovo (a resetnu jen stav)
    // - jinak hotovo, když processed >= total
    if ($total === 0 || $processed >= $total) {

        $this->logger->info("Kampaň {$campaignId} dokončena: processed={$processed}, total={$total}. Provádím reset.");

        $this->resetCampaignProcessingState($campaignId);

        if ($resetSendingStatuses) {
            $this->resetCampaignSendingStatusesAndCounters($campaignId);
        }

        return true;
    }

    return false;
}
/**
 * Resetuje stav zpracování kampaně (tabulka campaign_processing_state).
 * Tím se processed_count vrátí na začátek.
 */
private function resetCampaignProcessingState(int $campaignId): void
{
    $campaignId = (int)$campaignId;

    // Buď smažeš jen pro danou kampaň...
    $sql = "DELETE FROM campaign_processing_state WHERE campaign_id = {$campaignId}";
    $this->mysqli->query($sql);

    // ...nebo kdybys chtěl místo toho jen zapsat 0:
    // $sql = "INSERT INTO campaign_processing_state (campaign_id, processed_count) VALUES ({$campaignId}, 0)";
    // $this->mysqli->query($sql);
}

  public function __construct(CampaignRepository $campaignRepo, CampaignStatusService $statusService, Logger $logger, Campaigns $campaigns, $conn)
  {
    $this->campaignRepo = $campaignRepo;
    $this->statusService = $statusService;
    $this->logger = $logger;
    $this->campaigns = $campaigns;
    $this->mailAgent = new MailAgent();
    $this->campaigns_result = [];
    $this->mysqli = new dbdriver($conn);
    $this->cleanupOldState(1); // nech jen dnešek
    $this->last_procesed_contact="";
    
  }
  public function updateCampaign(int $cid):void {
    if ($cid<1) return;
    $statusKeywords = [
      'not_delivered',
      'delivered',
      'sent',
      'unread',
      'read',
      'failed',
      'delayed',
      'bounced',
      'pending',
      'processed',
      're',
      'vacation',
      'unknown' 
    ];
    foreach($statusKeywords as $k) {
      
    $q="
      SELECT COUNT(*) AS pocet
FROM email_campaign_sending
WHERE (status = '$k' OR status_from_cron = '$k') AND email_campaign_id=$cid;";
$count = $this->mysqli->selectQ($q);

switch ($k) {
  case "re": $q1="update email_campaign SET replied_count={$count[0][0]} where id=$cid";
    $this->mysqli->query($q1);
  break;
  case "bounced":
  case "not_delivered": $q1="update email_campaign SET undelivered_count={$count[0][0]} where id=$cid";
    $this->mysqli->query($q1);
    
  break;
   case "delivered": $q1="update email_campaign SET confirmed_received_count={$count[0][0]} where id=$cid";
    $this->mysqli->query($q1);
  break;
  
 }
}


  }
  public function run(): void
  {
    $result_c =[]; 
    try {
      $this->logger->info('--- Spouštím cron detekce stavů kampaní ---');

      $campaigns = $this->campaignRepo->getActiveCampaigns();
      
      if (count($campaigns) === 0) {
        $this->logger->info('Žádné aktivní kampaně (end_date neuplynulo ani není NULL).');
        return;
      }
    $result_c = [];
      foreach ($campaigns as $c) {
        $cid = (int) $c['id']; 
        $x=0;
        $batchDone=0;
        $next_contact = $this->getProcessedCount($cid);
        //$x = $next_contact;
           

        echo "campan: $cid \n";
        $this->logger->info("preskoceno: $next_contact \n");
        foreach ($this->campaigns->getCampaignContacts($cid) as $recipient) {
          $x++;// číslování kontaktů
          if ($x<$next_contact) {continue;}//přeskočím již zpracované.
          $batchDone++;
          if ($batchDone>=CRON_BATCH_SIZE) {//končím tuto dávku
          $this->saveProcessedCount($cid,$x);  
          break;
          
          }
          echo $this->last_procesed_contact = $recipient['email'];
          //print_r($recipient);
          //echo "result:\n";
          $result = $this->mailAgent->searchMessages($recipient['email'],$recipient['contact_id'], $c['name'], $cid);
          //print_r($result);
          /*if (isset($result[0]) ) {
            //$result[0] - je nejnovější e-mail
            //echo $result[0]["from"]." ".$result[0]["date"]." ".$result[0]["subject"]." ".$result[0]["status"];
            
            $result_c[$c['name']] [] = $result[0]["from"]." ".$result[0]["date"]." ".$result[0]["subject"]." ".$result[0]["status"];
          } else   
          if (isset($result["name"]) ) {
            //$result[0] - je nejnovější e-mail
            //echo $result[0]["from"]." ".$result[0]["date"]." ".$result[0]["subject"]." ".$result[0]["status"];
            
            $result_c[$c['name']] [] = $result["from"]." ".$result["date"]." ".$result["subject"]." ".$result["status"];
          }*/
            
          $result_c[$cid] [] = $result;
         
          
         //echo "\n++++++++\n";
        }
        echo "campan: $cid konec\n";
        
        $this->campaigns_result[] = $result_c;
        $this->saveProcessedCount($cid,$x);
         $this->finalizeCampaignIfCompleted($cid, true);
        
      }
      /*echo "\n-result_c:--\n";
        print_r($result_c);
        echo "\n---\n";
        */
      $this->logger->info('Cron detekce stavů — hotovo.');
    } catch (Throwable $e) {
      $this->logger->error('Chyba cronu: ' . $e->getMessage());
      http_response_code(500);
    }
   
  }
  function saveProcessedCount(int $campaignId, int $count): void
{
    $campaignId = (int)$campaignId;
    $count      = (int)$count;

    // 1) SELECT posledního ID pro campaign
    $sql1 = "SELECT id
             FROM campaign_processing_state
             WHERE campaign_id = $campaignId
             ORDER BY id DESC
             LIMIT 1";

    $res = $this->mysqli->query($sql1);
  /*
    if (!$res) {
        echo "SQL ERROR (select): $sql1\n";
        return;
    }
*/
    $row = $res;

    if ($row && isset($row['id'])) {
        $id = (int)$row['id'];

        // 2) UPDATE
        $sql2 = "UPDATE campaign_processing_state
                 SET processed_count = $count
                 WHERE id = $id";

        // echo $sql2; // pokud chceš debug
        $this->mysqli->query($sql2);

    } else {
        // neexistuje řádek -> INSERT (jinak to nepůjde)
        $sql3 = "INSERT INTO campaign_processing_state (campaign_id, processed_count)
                 VALUES ($campaignId, $count)";

        // echo $sql3; // pokud chceš debug
        $this->mysqli->query($sql3);
    }
}
function getProcessedCount(int $campaignId): int
{
    $campaignId = (int)$campaignId;
    if ($campaignId==0) return 0;

    $sql = "SELECT processed_count 
            FROM campaign_processing_state 
            WHERE campaign_id = $campaignId order by id DEsc limit 1";

    $res = $this->mysqli->query($sql);
    if ($res) {
        return (int)$res['processed_count'];
    }

    return 0; // pokud záznam neexistuje
}

function cleanupOldState(int $keepDays = 1): void
{
    $keepDays = max(1, (int)$keepDays);
    $sql = "DELETE FROM campaign_processing_state
            WHERE state_date < (CURDATE() - INTERVAL " . ($keepDays - 1) . " DAY)";
    $this->mysqli->query($sql);
}
  

function cleanupOldStateCapm(): void
{
    $sql = "DELETE FROM campaign_processing_state";
    $this->mysqli->query($sql);
}

  }  

function genUpdateQ ($arr, $mysqli): int {
  $ret = [];
  $q="";
  
foreach ($arr as $cid => $value) {
  if ($cid==0) break;

  $res = $value;
  
  foreach ($res as $vv) {
    if (count($vv)==0) continue;
    $v=$vv[0];
  
    $q = "update email_campaign_sending SET status_from_cron='{$v['status']}', datum_aktualizace= NOW()  
    where email_campaign_id=$cid AND contact_id='{$v['contact_id']}'";
    //echo "\n";

    $mysqli->query($q);
    


  }
  
}

 //return $ret;
 return 0;
}

// start ///

try {
  require_once './../conn.php'; // uprav cestu, pokud je soubor jinde
  $mysqli = $conn;
  
  $dir="/data/b/c/bcc4976a-22ab-432a-8aa9-8928a4b0c8fd/crm.skch.cz/logs/";
    if (!is_dir($dir)) $dir="./";
  $logger = new Logger($dir. '/cron_detect_campaign_status_oop.log');
  $campaignRepo = new CampaignRepository($mysqli);
  $statusSvc = new CampaignStatusService($logger);
  $campaigns = new Campaigns($mysqli);

  

  echo "<pre>";
  //echo __DIR__ ;
  
  print_r($campaignRepo->getActiveCampaigns());
  

  if (count($campaignRepo->getActiveCampaigns())<1) die ("Nejsou aktivní kampaně");

  $cron = new CampaignStatusCron($campaignRepo, $statusSvc, $logger, $campaigns, $mysqli);

  if (isset($_GET["reset"])) $cron->cleanupOldStateCapm();


  $cron->run();
  if (count($cron->campaigns_result)<1) die ("Není co dělat");
  echo "result:"; print_r($cron->campaigns_result);
  genUpdateQ($cron->campaigns_result[0], $mysqli,$cron);
  echo $cron->last_procesed_contact;
  
  print_r(array_keys($cron->campaigns_result[0]));
  // aktualizuji data v kampaních
  foreach (array_keys($cron->campaigns_result[0]) as $cid)
  {
    $cron->updateCampaign($cid);
  }
  
$UpdateCampaigns = new UpdateCampaigns($conn);//aktulizace čísel u kampane
} catch (Throwable $e) {
  print_r("<pre>" . $e);
  
  $dir="/data/b/c/bcc4976a-22ab-432a-8aa9-8928a4b0c8fd/crm.skch.cz/logs/";
  if (!is_dir($dir)) $dir="./";

  file_put_contents($dir. '/cron_detect_campaign_status_oop.log', '[' . date('Y-m-d H:i:s') . '] BOOTSTRAP ERROR ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
  http_response_code(500);
}
