<?php
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "../conn.php";
require_once "../cors.php";
require_once "../dbdriver.php";
$dbdrv=new dbdriver($conn);*/

require __DIR__ . '/../vendor/autoload.php';

$vocative = new Granam\CzechVocative\CzechName(); 
// Základní nastavení NameCase které používám já, více v dokumentaci
Tamtamchik\NameCase\Formatter::setOptions([ 'Czech' => false, 'lazy' => false ]);


if ($campaign_sending_id==0) exit;


$where =" ";

$sql = "SELECT send_main_contact, send_other_contact
        FROM email_campaign
        WHERE id = $campaign_sending_id";

$result = $conn->query($sql);
$row = $result ? $result->fetch_assoc() : null;

$sendMain  = (int)($row['send_main_contact'] ?? 0);
$sendOther = (int)($row['send_other_contact'] ?? 0);
$where = "";

if ($sendMain === 1 && $sendOther === 1) {
    // obě skupiny → není třeba filtrovat main vůbec
    $where = "";
} elseif ($sendMain === 1 && $sendOther === 0) {
    // jen hlavní
    $where = "AND fc.main = 1";
} elseif ($sendMain === 0 && $sendOther === 1) {
    // jen ostatní
    $where = "AND fc.main = 0";
} else {
    // nikomu → vrať prázdno (nejjednodušší je vynutit false podmínku)
    $where = "AND 1 = 0";
}

$sql = "SELECT 
        f.id AS firm_id,
        f.name AS firm_name,
        fc.id AS contact_id,
        fc.surname,
        fc.email,
        fc.phone,
        fc.linkedin,
        fc.gender,
        ecs.id AS sending_id,
        ecs.email_campaign_id,
        ecs.sent,
        ecs.status        
    FROM email_campaign_sending ecs
    JOIN firm_contacts fc ON ecs.contact_id = fc.id
    JOIN firm f ON fc.firm_id = f.id
    WHERE email_campaign_id = $campaign_sending_id $where order by f.name;";


$result = $conn->query($sql);
$csv_first_line = false;
$csv_first = "";
$csv = "";
$params["Vokativ"]=1;
$params["cp1250"]=1;
$columns_header="";

//print_r($_POST);echo "<br><br><br>";

if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    $x = 0;
    foreach ($row as $key => $value) {
     if ($value==null) $value="";

     if ($key=="surname")
          {
          if ($params["Vokativ"] == 1) {
               if (strlen($value)>3)
               {
                if ($row["gender"]=="jiné") //nepřechylovat
                  $name = $value;
                else
                $name=explode(" ",$vocative->vocative($value));
               
                if (isset($name[2])) $surname=$name[2];
                  else
                    if (isset($name[1])) $surname=$name[1];else $surname=$name[0];
                
                if ($row["gender"]=="jiné") $salut="Vážená paní ";
                else
                  if ($vocative->isMale($surname)) $salut="Vážený pane ";else $salut="Vážená paní ";
                
                    $csv .= $salut.$surname . ";$value;";
                }else {
                
                    $csv .=  "Dobrý den ;$value;";
                
                }

              }// if vocative
              else
                $csv .=  "$value;";

               }
          

    
    else
    {
      $value = preg_replace("/\n|\r/", " ", $value);
      $value = str_replace(";", " ", $value);
          if(!$csv_first_line){
            if($x < 17){
              if ($key=="surname") $csv_first .= "Vokativ;";
              $csv_first .= $key . ";";
              
            } 
            /*else{
              $csv_first .= $columns_name[$key] . ";";
              
            }*/
            
          }
          $x += 1;


          $csv .= $value . ";";

   // echo " $key => $value<br>";

    }
    }//foreach
    $csv .= "\n";
    $csv_first_line = true;
  }
  // echo "<br>";

}
$conn->close();

if ($params["Vokativ"] == 1)
  $csv_first = "firm_id;name;contact_id;vokative;surname;email".$columns_header;
else
$csv_first = "firm_id;name;contact_id;vokative;surname;email".$columns_header;

$csv = $csv_first . "\n" . $csv;

if ($params["cp1250"])
  $csv = iconv("UTF-8", "Windows-1250//IGNORE", $csv);


/*
$file = "../export.csv";
$txt = fopen($file, "w") or die("Unable to open file!");
if ($params["cp1250"])
  fwrite($txt, iconv("UTF-8", "Windows-1250//IGNORE", $csv));
else
fwrite($txt, $csv);
fclose($txt);
*/
/*
header("Content-Type: text/plain; charset=Windows-1250");
header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename='.basename($file));
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file));
readfile($file);
*/
//echo json_encode(array("file"=>URI."/".basename($file)));


?>
