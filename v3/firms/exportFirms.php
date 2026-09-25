<?php
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
require_once "../conn.php";
require_once "../cors.php";

require __DIR__ . '/../vendor/autoload.php';

$vocative = new Granam\CzechVocative\CzechName(); 
// Základní nastavení NameCase které používám já, více v dokumentaci
Tamtamchik\NameCase\Formatter::setOptions([ 'Czech' => false, 'lazy' => false ]);

$contact_ids = "";
$input = json_decode(file_get_contents('php://input'), true);
//print_r($input);
if (isset($input['contact_ids'])) {

$contact_ids = " AND firm_contacts.id IN (".implode(',', $input['contact_ids']). ")";
}

$sql = "SELECT firm.name,firm_contacts.surname as surname, firm_contacts.email as email
FROM firm inner join subject on firm.subject_id = subject.id  inner join firm_contacts on firm.id = firm_contacts.firm_id  
WHERE main=1 $contact_ids group by surname order by name;";

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
                
                    $csv .= $salut.$surname . ";";
                }else {
                
                    $csv .=  "Dobrý den ;";
                
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
          
         
          
          if($key == "subject_id"){
            $csv .= $subject_names[$value] . ";";
            continue;
          }
         
          
          $csv .= $value . ";";

   // echo " $key => $value<br>";

    }
    }//foreach
    $csv .= "\n";
    $csv_first_line = true;
  }
  // echo "<br>";
  // echo $csv;
}
$conn->close();

$csv_first = str_replace("name;email", "name;vokative;mail",$csv_first);

$csv = $csv_first . "\n" . $csv;

$file = "export.csv";
$txt = fopen($file, "w") or die("Unable to open file!");
//fwrite($txt, iconv("UTF-8", "Windows-1250//TRANSLIT", $csv));
fwrite($txt, $csv);
fclose($txt);

//header("Content-Type: text/plain; charset=Windows-1250");
header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename='.basename($file));
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file));
readfile($file);





?>
