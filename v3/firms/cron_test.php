<?php
date_default_timezone_set('Europe/Prague');
require_once './campaign.php';
require_once './mailAgent.php';
require_once '../dbdriver.php';

$mailAgent = new MailAgent();
$result = $mailAgent->searchMessages("dvoracek@narran.cz",577, "Pokračování spolupráce se SPŠE Ječná", 75);
echo "<pre>---------------------------\n";
//print_r($result);
 
genUpdateQ($result[0],null );
function genUpdateQ ($arr, $mysqli) {
  $ret = [];
  $q="";
  
  print_r($arr);


  $v = $arr;
  

    echo $q = "update email_campaign_sending SET status_from_cron='{$v['status']}', datum_aktualizace= NOW()  
    where email_campaign_id=$cid AND contact_id='{$v['contact_id']}'";
    //echo "\n";

   // $mysqli->query($q);
    


  
  
}
