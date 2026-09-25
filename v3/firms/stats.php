<?php

require_once "dbdriver.php";

Class stats {
private $dbdrv;
private $tab_firm="firm";
private $tab_colm="columns";
private $conn;
private $retArr;
private $types = array("Praxe","CV","WS","Schůzky","Dary","Pozvánky");
private $firm_sum;
private $addInvitations=1;

public function __construct($conn) {
    
    $this->dbdrv=new dbdriver($conn);
    $this->conn = $conn;
    // $this->getAll();
}
public function getAll() {

  $original=$this->getFirms();
  $this->retArr = array_map(function ($item) {
      return [
         // 'firm_id' => $item['id'],
          'id' => $item['id'],
          'name' => $item['name']
      ];
  }, $original);

  
  $m =$this->getAllPractices();
  $this->retArr = $this->mergeArrays($this->retArr,$this->addPrefix($m,"Praxe"));
  
     
  $m = $this->getCVs();
  $this->retArr = $this->mergeArrays($this->retArr,$this->addPrefix($m,"CV"));

  $m = $this->getWSs();
  $this->retArr = $this->mergeArrays($this->retArr,$this->addPrefix($m,"WS"));
  $this->addAllSYears();

  $m = $this->getMeets();
  $this->retArr = $this->mergeArrays($this->retArr,$this->addPrefix($m,"Schůzky"));

  $m = $this->getGifts();
  $this->retArr = $this->mergeArrays($this->retArr,$this->addPrefix($m,"Dary"));
  
  if ($this->addInvitations) {
    $m = $this->AllGetInvitations();  
    $this->retArr = $this->mergeArrays($this->retArr,$this->addPrefix($m,"Pozvánky"));
  }
  //print_r($this->getStatBySYears());

  return $this->retArr;
}
/* přidává WS,atd se školními roky:
Array
(
    [id] => 1
    [name] => 2N
    [CV23] =>
    [CV23 odpověd] =>
    [CV24] =>
    [CV24_neadres] =>
    [WS_2021/2022] => 1
    [WS_2024/2025] => 1
    [WS_2018/2019] => 0
    [CV_2018/2019] => 0
    [Schůzky_2018/2019] => 0
    [Dary_2018/2019] => 0
    [WS_2019/2020] => 0
    [CV_2019/2020] => 0
    [Schůzky_2019/2020] => 0
    [Dary_2019/2020] => 0
    [WS_2020/2021] => 0
    [CV_2020/2021] => 0
    [Schůzky_2020/2021] => 0
    [Dary_2020/2021] => 0
    [CV_2021/2022] => 0
    [Schůzky_2021/2022] => 0
    [Dary_2021/2022] => 0
    [WS_2022/2023] => 0
    [CV_2022/2023] => 0
    [Schůzky_2022/2023] => 0
    [Dary_2022/2023] => 0
    [WS_2023/2024] => 0
    [CV_2023/2024] => 0
    [Schůzky_2023/2024] => 0
    [Dary_2023/2024] => 0
    [CV_2024/2025] => 0
    [Schůzky_2024/2025] => 0
    [Dary_2024/2025] => 0
)
    */
  public  function getAllPractices($y=0) { //první select to třídí i dle ročníků
    /* SELECT firm.name as firm_name, firm.id as firm_id, annual,count, date_time as datum, sum(practices.count) as zaci,
 CASE WHEN MONTH(date_time) >= 9 THEN CONCAT(YEAR(date_time), '/', YEAR(date_time) + 1) ELSE CONCAT(YEAR(date_time) - 1, '/', YEAR(date_time)) END AS school_year
FROM `practices`
join firm on firm.id=practices.firm_id group by firm.id, annual Order by zaci DESC, name
*/
if ($y) $where="where YEAR(`date_time`) = $y"; else $where="";
    $sql = "SELECT firm.name as firm_name, firm.id as firm_id, annual,count, date_time as datum, sum(practices.count) as count,
 CASE WHEN MONTH(date_time) >= 9 THEN CONCAT(YEAR(date_time), '/', YEAR(date_time) + 1) ELSE CONCAT(YEAR(date_time) - 1, '/', YEAR(date_time)) END AS school_year
FROM `practices`
join firm on firm.id=practices.firm_id $where group by firm.id";
    $meets = $this->dbdrv->selectQAssoc($sql);
    
    return $meets;


  }

  /*
  public  function getAllWSs() { //vrátí seznam z workshop tabulky s daty
    $sql = "SELECT f.id AS firm_id, f.name AS firm_name,m.date as datum, CASE WHEN MONTH(m.date) >= 9 THEN CONCAT(YEAR(m.date), '/', YEAR(m.date) + 1) ELSE CONCAT(YEAR(m.date) - 1, '/', YEAR(m.date)) END AS school_year FROM workshops m JOIN firm f ON m.firmID = f.id ORDER BY f.id, school_year DESC;";
    $meets = $this->dbdrv->selectQAssoc($sql);

    return $meets;


  }*/

    private function addAllSYears () {
  $sy = $this->SchoolYear();
  $arr = &$this->retArr;
  $types =$this->types;
  $sum=array();

  //print_r($arr);

  for ($i=0;$i<count($arr);$i++ ){
      for ($j=0;$j<count($sy);$j++) {
        for ($k=0;$k<count($types);$k++) {
          if (!isset($arr[$i][$types[$k]."_".$sy[$j]]))
          {
            $arr[$i][$types[$k]."_".$sy[$j]]=0;
          }




      }
    }
  }

  for ($i=0;$i<count($arr);$i++ ){
    foreach ($arr[$i] as $key=>$value) {
      $new_key = $arr[$i]["name"];
      if (!isset($sum[$new_key] ))
        $sum[$new_key]=0;

      for ($j=0;$j<count($sy);$j++) {
        if (strpos($key,$sy[$j])!==false) {
         $sum[$new_key]+=$value;
        }
      }
    }

  }
  
  $this->firm_sum=$sum;
  //print_r($this->firm_sum);
 }
public function getFirmStats() {
  $ret=array();
  foreach ($this->firm_sum as $key=>$value) {
    if ($value>0)
    $ret[$key]=$value;
  }
  return $ret;
}


public function getStatBySYears() {
  $sy = $this->SchoolYear();
  $this->getAll();
  $arr = &$this->retArr;
  $types =$this->types;
  $stats=array();
  /*
  $stats["WS"] = array(
    "2021/2022" => array ("2N" =>3),
    "2022/2023" => array ("2N" =>3),
  );
  */

  for ($i=0;$i<count($arr);$i++)
    for ($j=0;$j<count($sy);$j++) {
      for ($k=0;$k<count($types);$k++) {
        //$stats["WS"][WS_2021/2022] = ("2N" =>3)
        if ($arr[$i][$types[$k]."_".$sy[$j]]>0)
          $stats[$types[$k]][$sy[$j]][$arr[$i]["name"]] = $arr[$i][$types[$k]."_".$sy[$j]];

    }

  }
  return  $stats;
}

private function mergeArrays($array1, $array2) {
  $merged = [];

  // Create a map for the second array based on firm_id
  $map = [];
  foreach ($array2 as $item) {
    if (!isset($item['firm_id'])) {
      $item['firm_id']=0;
    }

    if (!isset($item['school_year'])) {
      $item['school_year']=0;
    }

    if (!isset($item['count'])) {
      $item['count']=0;
    }
      $firmId = $item['firm_id'];
      $schoolYear = $item['school_year'];
      if (isset($item['datum'])) $datum = $item['datum']; else $datum="";
      $wsCount = $item['count'];

      if (!isset($map[$firmId])) {
          $map[$firmId] = [];
      }
      $map[$firmId][$schoolYear] = $wsCount;
  }
//print_R($array1[0]);
//print_R($array2[0]);
//print_R($map);

  // Merge the first array with the mapped data from the second array
  foreach ($array1 as $item) {
  
      $id = $item['id'];
      if (isset($map[$id])) {
          foreach ($map[$id] as $schoolYear => $wsCount) {
              $item[$schoolYear] = $wsCount;
          }
      }
      $merged[] = $item;
  }
  
  return $merged;
}

private function addPrefix($array,$Prefix) {
  foreach ($array as &$item) {
      if (isset($item['school_year'])) {
          $item['school_year'] = $Prefix.'_'.$item['school_year'];
      }
  }
  return $array;
}
/*
private function transposeArray($data) {
  $result = [];

foreach ($data as $item) {
    $firm_id = $item['firm_id'];
    $school_year = $item['school_year'];
    $ws_count = $item['ws_count'];

    if (!isset($result[$firm_id])) {
        $result[$firm_id] = [
            'firm_name' => $item['firm_name'],
            'school_years' => [],
        ];
    }

    $result[$firm_id]['school_years'][$school_year] = $ws_count;
}
return $result;
}
*/
private function getCVs() {
$cv_ids = $this->dbdrv->selectQ("SELECT * FROM $this->tab_colm where name like 'cv%'");
$arr=array();
$where="where ";
$sql = "select $this->tab_firm.id,$this->tab_firm.name,";
for ($i=0;$i<count($cv_ids);$i++) {
    $sql .= " c".$cv_ids[$i][0]." as '".$cv_ids[$i][1]."', ";
    /*
    $where.=  " (".
    "c".$cv_ids[$i][0]."!='null' AND ".
    "c".$cv_ids[$i][0]."!='' AND ".
    "c".$cv_ids[$i][0]."!=null".
    
    ") OR ";*/ 
}

$sql = substr($sql, 0, -1); 
//$where.= " 1"; 
$sql.=" from $this->tab_firm order by $this->tab_firm.id";

$i=0;

  foreach ($this->dbdrv->selectQAssoc($sql) as $key=>$value) {
  	
    //echo "$key:";    print_r($value);
    
   // if ($this->testNullValue($value)) 
   //     $arr[$i++]=$value;
    $value = $this->cleanNullValue($value);
    // $arr[$i++] = array_merge($value,$this->getRowbyFirmId($value["id"],$this->getMeets()));
    $arr[$i++] = $value;   
  }
    return $arr;    
}
private function cleanNullValue($arr) {
$x=0;
$i=0;
$sum=0;
foreach ($arr as $key=>$value) {
    	
        if ($key[0]=="C") {
          if ($value!==null) {
            $value = preg_replace("/\d{4}-\d{2}-\d{2}/", "", $value);
            $value = str_replace(" ","",$value);
            $value = str_replace("null","",$value);
            $value = str_replace("NULL","",$value);
          }
          
          $arr[$key] = $value;
    
        }
        
    }
    return $arr;
    }

private function testNullValue($arr) {
$x=0;
$i=0;
$sum=0;
foreach ($arr as $key=>$value) {
    	//echo $value." ";
        if ($key[0]=="C") {
        /*  if ($value==null) $x++;
          if ($value=="null") $x++;
          if ($value=="") $x++;
          if ($value==" ") $x++;*/
          if ($value!==null) {
            $value = preg_replace("/\d{4}-\d{2}-\d{2}/", "", $value);
            $value = str_replace(" ","",$value);
            $value = str_replace("null","",$value);
            $value = str_replace("NULL","",$value);
          }
          $sum+=(int) $value;
        //$i++;//po�et sloupc�
        }
        
    }
    //echo "sum:$sum \n----\n";
    if ($sum) return true;
    else
        return false;
}

private function getMeet($meets,$firmID) {

$ret=null;

  for ($i=0;$i<count($meets);$i++) {

    if ($meets[$i]["firm_id"]==$firmID) {
        $ret["Schuzky_".$meets[$i][2]] = $meets[$i][3];

    } 
  	
  }
  return $ret;
}
private function getWS($meets,$firmID) {

$ret=null;

  for ($i=0;$i<count($meets);$i++ ) {

    if ($meets[$i][0]==$firmID) {
        $ret["WS_".$meets[$i][2] ] = $meets[$i][3];

    } 
  	
  }
  return $ret;
}

private function getGift($meets,$firmID) {

$ret=null;
  for ($i=0;$i<count($meets);$i++ ) {

    if ($meets[$i][0]==$firmID) {
        $ret["Dary_".$meets[$i][2] ] = $meets[$i][3];

    } 
  	
  }
  return $ret;
}
private function SchoolYear($prefix = null)
{
    if ($prefix) {
        $prefix .= "_";
    }

    $startYear = 2018;
    $currentYear = (int)date("Y");

    if ((int)date("n") < 9) {
        $currentYear--;
    }

    $years = [];

    for ($y = $currentYear; $y >= $startYear; $y--) {
        $next = $y + 1;
        $years[] = $prefix . "{$y}/{$next}";
    }

    return $years;
}

 
public function getCvCount() {
return $this->dbdrv->selectWhere("cv_count","*", "Order by year DESC"); 
}

public function getFirms() { //get meet group by �koln� rok
$sql = "SELECT * from firm ORDER BY id";
$meets = $this->dbdrv->selectQAssoc($sql);

return $meets;

}

public function getWSs() { //get meet group by �koln� rok
$sql = "SELECT f.id AS firm_id, f.name AS firm_name,m.date as datum, CASE WHEN MONTH(m.date) >= 9 THEN CONCAT(YEAR(m.date), '/', YEAR(m.date) + 1) ELSE CONCAT(YEAR(m.date) - 1, '/', YEAR(m.date)) END AS school_year, COUNT(*) AS count FROM workshops m JOIN firm f ON m.firmID = f.id GROUP BY f.id, f.name, school_year ORDER BY f.id, school_year;";
$meets = $this->dbdrv->selectQAssoc($sql);

return $meets;

}

public function getAllWSs($y=0) { //get meet group by �koln� rok

if ($y) {
    // Rozmezí: [1. 9. ($y-1), 1. 9. $y)
    $from = ($y - 1) . '-09-01';
    $to   = $y . '-09-01';
    $where = "WHERE m.date >= '{$from}' AND m.date < '{$to}'";
}

// Vlastní dotaz
/*$sql = "
SELECT
  f.id   AS firm_id,
  f.name AS firm_name,
  MAX(m.date) as datum, 
  CASE
    WHEN MONTH(m.date) >= 9
      THEN CONCAT(YEAR(m.date), '/', YEAR(m.date) + 1)
    ELSE CONCAT(YEAR(m.date) - 1, '/', YEAR(m.date))
  END AS school_year,
  COUNT(*) AS count
FROM workshops m
JOIN firm f ON m.firmID = f.id
{$where}
GROUP BY f.id, f.name, school_year
ORDER BY f.name;
";*/
$sql="SELECT
  f.id   AS firm_id,
  f.name AS firm_name,
  type as typ,
date as datum

FROM workshops m
JOIN firm f ON m.firmID = f.id
$where
ORDER BY f.name;";

$meets = $this->dbdrv->selectQAssoc($sql);

return $meets;

}

public function getInvitations($withFirmID=0,$y=0) {
if ($withFirmID) $sel = "f.id AS firm_id, ";else $sel = "";
if ($y>0) $where="where YEAR(m.date)= $y "; else $where=""; 
$sql = "SELECT CASE WHEN MONTH(m.date) >= 9 THEN CONCAT(YEAR(m.date), '/', YEAR(m.date) + 1) ELSE CONCAT(YEAR(m.date) - 1, '/', YEAR(m.date)) END AS Rok, $sel f.name AS Firma, A_adres, E_adres, I_adres, A_neadres,E_neadres, I_neadres,vsem,note FROM cv_invitatios m JOIN firm f ON m.firm_ID = f.id $where ORDER BY f.name, date DESC";
$meets = $this->dbdrv->selectQAssoc($sql);
if ($y) {
  $ret = [];
  for ($i=0;$i<count($meets);$i++) {
    $ret[$i]=$meets[$i];
   
      $ret[$i]['count'] = 
          intval($meets[$i]['A_adres'] ?? 0) +
          intval($meets[$i]['E_adres'] ?? 0) + 
          intval($meets[$i]['I_adres'] ?? 0) + 
          intval($meets[$i]['A_neadres'] ?? 0) +
          intval($meets[$i]['E_neadres'] ?? 0) +
          intval($meets[$i]['I_neadres'] ?? 0) +
          intval($meets[$i]['vsem'] ?? 0);
  }
  return $ret;
}
return $meets;

}

public function AllGetInvitations($y=0) { //get invitaion count
$meets = $this->getInvitations(1,$y);
$ret = [];

    for ($i=0;$i<count($meets);$i++) {
      
      $ret[$i]['firm_id'] = $meets[$i]['firm_id'];
      $ret[$i]['rok'] = $meets[$i]['Rok'];
      $ret[$i]['firm_name'] = $meets[$i]['Firma'];

        $ret[$i]['count'] = 
            intval($meets[$i]['A_adres'] ?? 0) +
            intval($meets[$i]['E_adres'] ?? 0) + 
            intval($meets[$i]['I_adres'] ?? 0) + 
            intval($meets[$i]['A_neadres'] ?? 0) +
            intval($meets[$i]['E_neadres'] ?? 0) +
            intval($meets[$i]['I_neadres'] ?? 0) +
            intval($meets[$i]['vsem'] ?? 0);
    }
return $ret;

}

public function getGifts() { //group by �koln� rok
$sql = "SELECT f.id AS firm_id, f.name AS firm_name, CASE WHEN MONTH(m.date) >= 9 THEN CONCAT(YEAR(m.date), '/', YEAR(m.date) + 1) ELSE CONCAT(YEAR(m.date) - 1, '/', YEAR(m.date)) END AS school_year, COUNT(*) AS count FROM gifts m JOIN firm f ON m.firm_id = f.id GROUP BY f.id, f.name, school_year ORDER BY f.id, school_year;";
$meets = $this->dbdrv->selectQAssoc($sql);

return $meets;

}

public function getAllGifts($y) { 
if ($y) {
    // Rozmezí školního roku: [1. 9. ($y-1), 1. 9. $y)
    $from  = ($y - 1) . '-09-01';
    $to    = $y . '-09-01';
    $where = "WHERE m.date >= '{$from}' AND m.date < '{$to}'";
} else {
    $where = "";
}

$sql = "
SELECT
  f.id   AS firm_id,
  f.name AS firm_name,
  date as datum,
COUNT(*) AS count
FROM gifts m
JOIN firm f ON m.firm_id = f.id
{$where}
GROUP BY f.id
ORDER BY f.name
";

$meets = $this->dbdrv->selectQAssoc($sql);

return $meets;

}

public function getMeets() { //get meet group by �koln� rok
$sql = "SELECT f.id AS firm_id, f.name AS firm_name, CASE WHEN MONTH(m.date_time) >= 9 THEN CONCAT(YEAR(m.date_time), '/', YEAR(m.date_time) + 1) ELSE CONCAT(YEAR(m.date_time) - 1, '/', YEAR(m.date_time)) END AS school_year, COUNT(*) AS count FROM meets m JOIN firm f ON m.firm_id = f.id GROUP BY f.id, f.name, school_year ORDER BY f.id, school_year;";
$meets = $this->dbdrv->selectQAssoc($sql);
return $meets;
}
  public  function getAllCVInvitations($y=0) { //první select to třídí i dle ročníků
    
if ($y) $where="where YEAR(`date`) = $y"; else $where="";
    $sql = "
    SELECT 
    firm.name AS firm_name,cv_invitatios.*, 
    SUM( A_adres + E_adres + I_adres + A_neadres + E_neadres + I_neadres + vsem ) AS total_sum, 
    SUM( A_adres + E_adres + I_adres ) AS adres, SUM( A_neadres + E_neadres + I_neadres + vsem ) AS neadres 
       
    FROM `cv_invitatios` JOIN firm ON firm.id = cv_invitatios.firm_id WHERE YEAR(`date`) = 2026 
    GROUP BY cv_invitatios.firm_id, firm.name HAVING total_sum > 0
    order by firm_name
    ";
    $meets = $this->dbdrv->selectQAssoc($sql);
    
    return $meets;


  }
public function getAllMeets($y) {
if ($y) {
    // Rozmezí školního roku: [1. 9. ($y-1), 1. 9. $y)
    $from  = ($y - 1) . '-09-01';
    $to    = $y . '-09-01';
    $where = "WHERE m.date_time >= '{$from}' AND m.date_time < '{$to}'";
} else {
    $where = "";
}

$sql = "
SELECT
  f.id   AS firm_id,
  f.name AS firm_name,
  date_time as datum
FROM meets m
JOIN firm f ON m.firm_id = f.id
{$where}
ORDER BY f.name
";
$meets = $this->dbdrv->selectQAssoc($sql);
return $meets;
}

private function getRowbyFirmId($id,$rows) {
$ret=array();
for ($i=0;$i<count($rows);$i++) {
    if ($rows[$i][0]==$id)  $ret[$rows[$i][1]] = $rows[$i][2];
}
return $ret;
}

public function getAllNotActivity($y) {
if (!$y) return null;  
//$sql = "SELECT f.id AS firm_id, f.name AS firm_name, CASE WHEN MONTH(m.date_time) >= 9 THEN CONCAT(YEAR(m.date_time), '/', YEAR(m.date_time) + 1) ELSE CONCAT(YEAR(m.date_time) - 1, '/', YEAR(m.date_time)) END AS school_year, COUNT(*) AS count FROM meets m JOIN firm f ON m.firm_id = f.id GROUP BY f.id $where ORDER BY f.id";
$sql = "SELECT f.id, f.name FROM firm f WHERE f.active = 1 AND f.id NOT IN ( SELECT DISTINCT firmId FROM workshops WHERE date BETWEEN '".($y-1)."-09-01' AND '".$y."-08-31' ) AND f.id NOT IN ( SELECT DISTINCT firm_id FROM practices WHERE date_time BETWEEN '2024-09-01' AND '2025-08-31' order by f.name);";
$meets = $this->dbdrv->selectQAssoc($sql);
return $meets;
}

public function export() {

  $data = $this->getAll();
 // print_r($data);
  if (empty($data)) { return "-1";}

  $filePath ="exportstat.csv";
  $file = fopen($filePath, 'w');
  $headers = array_keys($data[0]);

  // Add BOM to fix UTF-8 in Excel
 fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

  fputcsv($file, $headers, ';');

  foreach ($data as $row) {
    fputcsv($file, $row, ';');
  }

  fclose($file);
  header("Content-Type: text/plain; charset=Windows-1250");
  header('Content-Description: File Transfer');
  header('Content-Disposition: attachment; filename='.basename($filePath));
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  header('Content-Length: ' . filesize($filePath));
  readfile($filePath);
}

function getTopCompanies($y=0) {
$this->addInvitations=0;  
$data = $this->getStatBySYears();
if ($y) $sy="".($y-1)."/$y";
  
$sections = $this->types;
$totals = [];

foreach ($sections as $section) {
    if (!isset($data[$section])) continue;

    foreach ($data[$section] as $year => $entries) {
      if ($y) if ($year!=$sy) continue;      
        foreach ($entries as $company => $count) {
            if (!isset($totals[$company])) {
                $totals[$company] = ['total' => 0];
                foreach ($sections as $s) {
                    $totals[$company][$s] = 0;
                }
            }
            $totals[$company][$section] += (int)$count;
            $totals[$company]['total'] += (int)$count;
        }
    }
}

// Seřadit podle celkového počtu
uasort($totals, fn($a, $b) => $b['total'] <=> $a['total']);

//return $totals;
// Vzít prvních 15
return $top5 = array_slice($totals, 0, 25, true);



}


} // class
