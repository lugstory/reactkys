<?php
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
require_once "../conn.php";

require __DIR__ . '/../vendor/autoload.php';

$vocative = new Granam\CzechVocative\CzechName(); 
// Základní nastavení NameCase které používám já, více v dokumentaci
Tamtamchik\NameCase\Formatter::setOptions([ 'Czech' => false, 'lazy' => false ]);

if (isset($_GET["other"]) && $_GET["other"] ==1 )
  $main = "";
else
  $main = "AND fc.main=1";

$sql = "SELECT f.name, fc.id AS contact_id, fc.main, fc.surname, fc.email FROM
firm_contacts fc JOIN firm f ON fc.firm_id = f.id WHERE fc.active_c = 1 AND f.active = 1 $main order by f.name;";


$result = $conn->query($sql);
$csv_first_line = false;
$csv_first = "";
$csv = "";

//print_r($_POST);echo "<br><br><br>";

if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    $x = 0;
    foreach ($row as $key => $value) {
     if ($value==null) $value="";

     if ($key=="surname")
               {

               if (strlen($value)>3)
               {
                $name=explode(" ",$vocative->vocative($value));
               
                if (isset($name[1])) $surname=$name[1];else $surname=$name[0];
                
                if ($vocative->isMale($surname)) $salut="Vážený pane ";else $salut="Vážená paní ";
                
                    $csv .= $salut.$surname . ";$value;";
                }else {
                
                    $csv .=  "Dobrý den ;$value;";
                
                }
               }
          
    
    
    else
    {
      $value = preg_replace("/\n|\r/", " ", $value);
      $value = str_replace(";", " ", $value);
          if(!$csv_first_line){
            if($x < 17){
              if ($key=="surname") $csv_first .= "Vokativ;";
              $csv_first .= $key . ";";
              
            } else{
              $csv_first .= $columns_name[$key] . ";";
              
              
            }
            
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

$csv_first = "name;contat_id;main;vokative;email";

$csv = $csv_first . "\n" . $csv;

$file = "export.csv";
$txt = fopen($file, "w") or die("Unable to open file!");
//fwrite($txt, iconv("UTF-8", "Windows-1250//TRANSLIT", $csv));
fwrite($txt, $csv);
fclose($txt);

header("Content-Type: text/plain; charset=Windows-1250");
header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename='.basename($file));
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file));
readfile($file);





?>
