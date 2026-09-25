<?php
require_once __DIR__ . "/../dbdriver.php";
require __DIR__ . '/../vendor/autoload.php';

class UpdateCampaigns
{
  private $dbdrv;
  private $tab = "email_campaign";
  private $tab_sending = "email_campaign_sending";
  private $conn;
  private $campaigns;

  public function __construct($conn)
  {
    $this->dbdrv = new dbdriver($conn);
    $this->conn = $conn;
    $this->campaigns =$this->getCampaigns();
    $this->recomputeUndeliveredForAllCampaigns();
    $this->recomputeDeliveredForAllCampaigns();
  }
  public function getCampaigns()
  {
    $sql = "SELECT * FROM $this->tab";
    $arr = $this->dbdrv->SelectQAssoc($sql);

    return $arr;
  }

  public function recomputeUndeliveredForAllCampaigns(): void
  {
    
    foreach ($this->campaigns as $c) {
      $campaignId = (int) $c['id'];
      $cnt=0;
      $sqlCount = "
            SELECT COUNT(*) AS cnt
            FROM email_campaign_sending
            WHERE email_campaign_id = {$campaignId}
              AND status IS NULL
              AND (status_from_cron IS NULL OR status_from_cron = 'unknow') GROUP BY firm_id order by contact_id;
        ";
      $rows = $this->dbdrv->selectQAssoc($sqlCount);
      foreach ($rows as $row) {
      $cnt += isset($row['cnt']) ? 1 : 0;
      }
      
      echo $cnt." ";

      // Tady už voláme vámi požadované 'update'
      $this->dbdrv->update(
        'email_campaign',
        ['undelivered_count' => $cnt],
        "WHERE id = {$campaignId}"
      );
    }
  }

  public function recomputeDeliveredForAllCampaigns(): void
  {
    $cnt=0;
    foreach ($this->campaigns as $c) {
      $campaignId = (int) $c['id'];

      $sqlCount = "
           SELECT COUNT(*) AS cnt FROM `email_campaign_sending` where email_campaign_id = {$campaignId}
           and (status is not null AND status<>'nedoručeno') or 
           (status_from_cron='re' AND   status<>'unknown') GROUP BY firm_id order by contact_id;
        ";
      $rows = $this->dbdrv->selectQAssoc($sqlCount);
       foreach ($rows as $row) {
      $cnt += isset($row['cnt']) ? 1 : 0;
      }

      // Tady už voláme vámi požadované 'update'
      $this->dbdrv->update(
        'email_campaign',
        ['replied_count' => $cnt],
        "WHERE id = {$campaignId}"
      );
    }
  }


}

$UpdateCampaigns = new UpdateCampaigns($conn);
