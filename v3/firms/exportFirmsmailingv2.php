<?php
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
require_once "../conn.php";
require_once "../cors.php";
require_once "../dbdriver.php";
$dbdrv=new dbdriver($conn);

require __DIR__ . '/../vendor/autoload.php';

$vocative = new Granam\CzechVocative\CzechName(); 
// Základní nastavení NameCase které používám já, více v dokumentaci
Tamtamchik\NameCase\Formatter::setOptions([ 'Czech' => false, 'lazy' => false ]);

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) exit;
//print_r($input);

$subjects = $input["fields"];
$params = $input["params"];
$columns = $input["columns"];

$where =" ";

if (count($subjects)==0) {return json_encode(array("msg"=>-1));}
if (count($subjects)<3) {
  $arr = array();
  if ($subjects["IT"]==1) $arr[] = 1;
  if ($subjects["ele"]==1) $arr[] = 2;
  if ($subjects["eleit"]==3) $arr[] = 3;

  $where = implode(", ", $arr);
  $where .= "AND ";
}

if ($params["Hlavnikontakt"] == 1 && $params["Vsechnyvedlejsi"] == 1) $where .= "";
else
  if ($params["Hlavnikontakt"] == 1) $where .= "main=1 AND ";
else
  if ($params["Vsechnyvedlejsi"] == 1) $where .= "main=0 AND ";

  if ($params["Aktivni"] == 1) $where .= "fc.active_c = 1 AND ";

if ($where) $where .= " 1";

if ($params["google"]==1) { // generování pro google
  exportGoogle ($where,$conn ); 
  exit;
}


//TODO: další sloupce: dodělat překlad na reálný název
$select="";

$sql = "SELECT * from columns";
$columns_list = $dbdrv->selectQ($sql);
// print_r($columns_list);
$columns_header = "";

if (count($columns))  {
  // columns
  $arr2 = array();
  foreach ($columns as $key => $value) {
    if ($value) {
      for($i=0;$i<count($columns_list);$i++)
      {
        if ($columns_list[$i][1] == $key)
        {
          $key2="c".$columns_list[$i][0];
          $columns_header.=$columns_list[$i][1].";";
          $arr2[] = "f.$key2 as '$key'";
          continue;
        }  
      }
      
    }

  }
  $select = implode(", ", $arr2);
  $select = ", ".$select;
}

$sql = "SELECT f.name, fc.id AS contact_id, fc.main, fc.surname, fc.email,fc.gender  $select FROM
firm_contacts fc JOIN firm f ON fc.firm_id = f.id WHERE $where order by f.name;";


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
  $csv_first = "name;contat_id;main;vokative;surname;email;gender;".$columns_header;
else
$csv_first = "name;contat_id;main;surname;email;gender;".$columns_header;

$csv = $csv_first . "\n" . $csv;

$file = "../export.csv";
$txt = fopen($file, "w") or die("Unable to open file!");
if ($params["cp1250"])
  fwrite($txt, iconv("UTF-8", "Windows-1250//IGNORE", $csv));
else
fwrite($txt, $csv);
fclose($txt);
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
echo json_encode(array("file"=>URI."/".basename($file)));


function exportGoogle ($where, $conn ) {
$csv_head = "Name Prefix;First Name;Middle Name;Last Name;Name Suffix;Phonetic First Name;Phonetic Middle Name;Phonetic Last Name;"
          . "Nickname;File As;E-mail 1 - Label;E-mail 1 - Value;Phone 1 - Label;Phone 1 - Value;Address 1 - Label;Address 1 - Country;"
          . "Address 1 - Street;Address 1 - Extended Address;Address 1 - City;Address 1 - Region;Address 1 - Postal Code;Address 1 - PO Box;"
          . "Organization Name;Organization Title;Organization Department;Birthday;Event 1 - Label;Event 1 - Value;Relation 1 - Label;Relation 1 - Value;"
          . "Website 1 - Label;Website 1 - Value;Custom Field 1 - Label;Custom Field 1 - Value;Notes;Labels";

$sql = "SELECT f.name AS org_name, fc.surname AS surname, fc.email AS email FROM firm_contacts fc
        JOIN firm f ON fc.firm_id = f.id WHERE $where ORDER BY f.name;";

$result = $conn->query($sql);
$csv = "";

if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $line = [
      "", "", "",                          // Name Prefix; First Name; Middle Name
      $row["surname"],                     // Last Name
      "", "", "", "", "", "",              // Name Suffix through File As
      "home",                              // E-mail 1 - Label
      $row["email"],                       // E-mail 1 - Value
      "", "", "", "", "", "", "", "", "",  // Phone 1 - Label → PO Box
      $row["org_name"],                    // Organization Name
      "", "", "", "", "", "", "", "", "", "", "", "", "" // zbytek sloupců
    ];
    $csv .= implode(";", $line) . "\n";
  }
}

$csv = $csv_head . "\n" . $csv;
$file = "../export.csv";
$txt = fopen($file, "w") or die("Unable to open file!");
fwrite($txt, $csv);
fclose($txt);

echo json_encode(["file" => URI . "/" . basename($file)]);

}

?>
