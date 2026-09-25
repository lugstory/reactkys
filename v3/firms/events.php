<?php

require_once "dbdriver.php";

Class events {
private $dbdrv;
private $tab="events";
private $conn;

public function __construct($conn) {

    $this->dbdrv=new dbdriver($conn);
    $this->conn = $conn;

}


public function getevent($id) {

    $id = (int) $id;
    if ($id==0) return null;

   $sql = "SELECT * FROM $this->tab where id=$id order by time_start DESC";

   return $this->dbdrv->SelectQAssoc($sql);

}

public function insert($input)
{
    $r=false;

    if ($this->dbdrv->insert($this->tab,$input) != -1)
        $r = true;

return $r;

}

public function update($input) {

  if (!$input) return 0;
    $id = (int) $input["id"];
    if ($id==0) return 0;

   $fields = [];
    foreach ($input as $column => $value) {
        if ($column=="firma") continue;
      $fields[] = "$column = '" . $this->replaceNewlines(addslashes($value)) . "'";

    }
    $fields = implode(', ', $fields);
    $query = "UPDATE $this->tab SET $fields  WHERE id = " . intval($id);

      return $this->dbdrv->query($query);



     }

private function replaceNewlines($text) {
      // Replace newline characters with \n
      return str_replace(["\r\n", "\r", "\n"], '\\n', $text);
  }


public function delete($id) {
    if ($id) return $this->dbdrv->delete ($this->tab, "where id=$id","limit 1");
    else -1;

}

public function getEvents($firm_id=null) {
  $where="";
  $j=0;
  $arr= [];


  if ($firm_id) $where=" where firm_id=$firm_id";

//$q ="SELECT `events`.id,`events`.name as 'Název',`events`.description as 'Popis',`events`.time_start as 'čas',f.name as firma FROM `events` LEFT JOIN firm f ON events.firm_id = f.id $where order by time_start DESC";
$q ="SELECT f.name as firma,`events`.id,`events`.name,`events`.description,`events`.time_start , events.firm_id as firm_id FROM `events` LEFT JOIN firm f ON events.firm_id = f.id $where order by time_start DESC";

 $result = $this->conn->query($q);
   if ($result->num_rows > 0) {
     while($row = $result->fetch_assoc()) {

     $arr[$j] = $this->replaceDateStr($row);


     $j++;


 }
 }
   return $arr;
}

public function getFutureEvents() {
  $j=0;
  $arr = array();
  $where = "WHERE `events`.time_start >= CURDATE()
          AND `events`.time_start < DATE_ADD(CURDATE(), INTERVAL 1 MONTH)";

  $q ="SELECT `events`.id,`events`.name,`events`.description,`events`.time_start ,f.name as firma,f.id as firm_id FROM `events` LEFT JOIN firm f ON events.firm_id = f.id $where order by time_start ASC";

 $result = $this->conn->query($q);
   if ($result->num_rows > 0) {
     while($row = $result->fetch_assoc()) {

     $arr[$j] = $this->replaceDateStr($row);
     $j++;
 }
 }
   return $arr;
}


private function replaceDateStr($row) {

  foreach ($row as $key=>$value) {

  $row[$key] =  convertDateTimetoCzech($value);

  }
  return $row;
  }

  public function generateICS ($id) {
    $_event = $this->getevent($id)[0];
    // print_r($_event);

    $event['start'] = $_event["time_start"];
    $event['end'] = $_event["time_end"];
    $event['summary'] = $_event["name"];
    $event['description'] = $_event["description"];

    if (!$event['end']) {
      $date = new DateTime("2025-01-13 13:13:00");
      $date->modify('+1 hour');
      $event['end'] = $date->format('Y-m-d H:i:s');
    }

    $icsContent = "BEGIN:VCALENDAR\n";
    $icsContent .= "VERSION:2.0\n";
    $icsContent .= "PRODID:-//Your Organization//NONSGML v1.0//EN\n";
    $icsContent .= "CALSCALE:GREGORIAN\n";

        $icsContent .= "BEGIN:VEVENT\n";
        $icsContent .= "UID:" . uniqid() . "\n";
        $icsContent .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\n";
        $icsContent .= "DTSTART:" . gmdate('Ymd\THis\Z', strtotime($event['start'])) . "\n";
        $icsContent .= "DTEND:" . gmdate('Ymd\THis\Z', strtotime($event['end'])) . "\n";
        $icsContent .= "SUMMARY:" . $event['summary'] . "\n";
        $icsContent .= "DESCRIPTION:" . $event['description'] . "\n";
        $icsContent .= "END:VEVENT\n";


    $icsContent .= "END:VCALENDAR\n";

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="calendar'.$event['start'].'.ics"');

    // Output ICS content
    echo $icsContent;
  }


} // class

?>
