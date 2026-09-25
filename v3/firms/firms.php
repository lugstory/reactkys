<?php

require_once "dbdriver.php";

Class firms {
private $dbdrv;
private $tab_firm="firm";
private $tab="firm";
private $conn;
private $tab_firm_contacts;
private $pseudoFilterItems;

public function __construct($conn) {
    
    $this->dbdrv=new dbdriver($conn);
    $this->conn = $conn;
    
}

public function getFirm($id) { 

$id= intval($id);
if ($id==0)  return "";
return $this->dbdrv->selectWhere($this->tab_firm,"*"," where id=$id");   
}

public function showcolumns($name) { 
if (strlen($name)<2) return -1;

return $this->dbdrv->selectQ("delete where name='".$name."' limit 1");   
}

public function hidecolumns($name) { 
if (strlen($name)<2) return -1;

return $this->dbdrv->selectQ("insert into set name='".$name."'");   
}

private function createWhereFilter($filter) {
$where="   ";
foreach ($filter as $key=>$value) {

  if ($value=="false")  continue;
  if ($value=="true")  continue;
  if (trim($value)=="")  continue;
  if (trim($value)==null)  continue;
  if ($key == "kontakty")   continue;// přeskočíme psedudopoložku z filtru
  $columns = $this->dbdrv->selectQ("SELECT * FROM columns");
  if ($key != "name" && $key != "subject_id")
    $key = "c".$this->getColmID($key,$columns);
  
    $where.=" `firm`.`$key` like '%".trim($value)."%' && ";
}

return $where;
}

public function getFirmsNotCont() {
    return $this->getFirms(null, 1);
}

public function getFirmsFilter($filter) {
    return $this->getFirms($filter);
}

public function getFirms($filter=null,$notkont=0) {
  $where=" active=1";

/*
  if ($filter!=null) {
    if (isset($filter["kontakty"])) 
        $this->pseudoFilterItems["kontakty"] = $filter["kontakty"];
    $where = $this->createWhereFilter($filter);
  }  */

 if (isset($filter["show_inactive"]) && $filter["show_inactive"] == "true") {    
   $where = "1";// active může být 0 i 1 
} 

$firm_columns = $this->dbdrv->selectQ("SHOW COLUMNS FROM firm");

$hidden_columns_2d = $this->dbdrv->selectQ("SELECT name FROM hidden_columns");
$hidden_columns = array();
foreach ($hidden_columns_2d as $sub_array) {
    $hidden_columns[] = $sub_array[0];
}
$columns = $this->dbdrv->selectQ("SELECT * FROM columns");
$q = "SELECT ";

for($i=0;$i<count($firm_columns);$i++) {

if (!in_array($firm_columns[$i][0], $hidden_columns)) {

    if ($firm_columns[$i][0]=="subject_id")
        $q .="subject.name as obor, ";
        
    else
        $q .="firm.".$firm_columns[$i][0]." as '".$this->getColmName($firm_columns[$i][0],$columns)."', ";

}

/*if ($firm_columns[$i][0][0]=="c")
{
    $col = $this->getColmName($firm_columns[$i][0],$columns);
    if ($col) $q.= " as `".$col."`".", ";
}
*/
}

$q = substr($q, 0, -2); 
$q .=" FROM `firm` LEFT JOIN subject ON firm.subject_id = subject.id LEFT JOIN columns ON subject.id = columns.id WHERE $where order by firm.name";

$count_contacs = $this->dbdrv->selectQ("SELECT firm_id, COUNT(*) AS contact_count,surname,email FROM `firm_contacts` where active_c=1 AND email like '%@%' GROUP BY firm_id;");
//$count_contacs = $this->dbdrv->selectQ("SELECT firm_id, surname, email FROM `firm_contacts` where active_c=1 AND email like '%@%' GROUP BY firm_id;");
$contacs = $this->dbdrv->selectQ("SELECT firm_id as id, surname,email FROM `firm_contacts` where email like '%@%' order BY firm_id;");
$contacs_arr =$this->joinContacts($contacs); //spojí kontakty od jedné firmy

$arr=array();
 $j=0;
 $result = $this->conn->query($q);
   if ($result->num_rows > 0) {
     while($row = $result->fetch_assoc()) {
     
     $arr[$j] = $this->replaceDateStr($row);
     
     $findContactCount = $this->findContactCount ($count_contacs,$row["id"]);
     $arr[$j]["Kontakty"]= $findContactCount;
     if (!$notkont) $arr[$j]["name"] .= "/(kont)".$this->findContacts ($contacs_arr,$row["id"]);
       
     $j++;
     
    
 }
 }
 return $arr;
}



private function findContactCount ($count_contacs,$firmId) {
//echo $firmId." ";
for ($i=0;$i<count($count_contacs);$i++) {
$firmId = (int) $firmId;
if ($count_contacs[$i][0]==$firmId) return $count_contacs[$i][1];

}
return 0;
}
private function findContacts ($contacs,$firmId) {
  if (isset($contacs[$firmId]))
    return $contacs[$firmId];
  else "";
}
private function joinContacts ($count_contacs) {
  //echo $firmId." ";
  //$." ";
  $ret=[];
  for ($i=0;$i<count($count_contacs);$i++) {


  $value = $count_contacs[$i][1] . " " . $count_contacs[$i][2];

    if (!isset($ret[$count_contacs[$i][0]])) {
    $ret[$count_contacs[$i][0]] = "";
    }

    $ret[$count_contacs[$i][0]] .= $value;

  }
  return $ret;
  }

public function checkIfFirmExist($name)
{
$name = trim($name);
    return count ($this->dbdrv->selectWhere($this->tab_firm,"*"," where name='".$name."'"));  
    
}

public function insert($input) {
  $input["id"]=0;
  if ($this->checkIfFirmExist($input["name"])) return -1;
   else    
      return $this->dbdrv->insert($this->tab_firm,$input);
        
       

}

public function updateFirm($input) {
    
    $id = $input["id"];
    $updateParts = [];

    foreach ($input as $key => $value) {
    if ($key == "id") continue;
        if ($key == "phone") {
            $value = str_replace("+420", "", $value);
            $value = str_replace("+", "", $value);
            $value = str_replace(" ", "", $value);
        }
        $updateParts[] = "$key='" . trim($value) . "'";
    }
    if (count($updateParts)<1) return false;
    $updateString = implode(", ", $updateParts);
    $sql = "UPDATE firm SET $updateString WHERE id=$id";     
      //echo $sql;
      return $this->dbdrv->query($sql);
     
    
      
     }
    



public function getFirmForm() {
$firms_ret = array();
$firms = $this->dbdrv->selectQ("SHOW COLUMNS FROM firm");
$hidden_columns = $this->dbdrv->selectQ("SELECT * FROM hidden_columns");
$columns = $this->dbdrv->selectQ("SELECT * FROM columns");
$type="";
$name="";

for ($i=0;$i<count($firms);$i++) {

  if (strpos($firms[$i][1], "int") !== false) 
  {$type="number";}
  else
  if (strpos($firms[$i][1], "varchar") !== false) 
  {$type="string";}
  else
  {$type=$firms[$i][1];}

  if ($firms[$i][0][0]=="c") {
  $name=$this->getColmName($firms[$i][0],$columns);
  }else 
  if ($firms[$i][0]=="subject_id") {$name="Obor";$type="select";}
  else {$name=$this->getColmBasicName($firms[$i][0]);}

  $firms_ret[$i] = array($firms[$i][0],$type,$name,(int) $this->isHidColmName($firms[$i][0],$hidden_columns));

  }
$firms_ret[0][3]=1;//hide ID

  //print_r($hidden_columns);
 // echo "---------";
  //print_r($firms_ret);


return $firms_ret;
}

public function getFirmAndForm($id) {
$id= intval($id);
if ($id==0)  return "invalid id";

$form = $this->getFirmForm();
$firm = $this->getFirm($id);
if (!$firm) return null;

$firm = $firm[0];



for ($i=0;$i<count($form);$i++) {
$key = $form[$i][0];
$form[$i]  =array($form[$i][0],$form[$i][1],$form[$i][2],$form[$i][3],$firm[$key]) ;

}
//print_r($form);
return $form;

}


private function getColmBasicName($jm) {

switch($jm) {
case "name": return "Firma";
case "source": return "Zdroj";
case "active":  return "Aktivní";
default: return $jm; 
}

}

private function getColmName($jm,$columns) {
  if (strlen($jm)==2) return $jm; //přeskauji "cv"
$jm=str_replace("c","",$jm);
for ($i=0;$i<count($columns);$i++) {
if ($columns[$i][0]==$jm) return $columns[$i][1]; 
}
return $jm;
}

private function isHidColmName($jm,$columns) {

for ($i=0;$i<count($columns);$i++) {
if ($columns[$i][1]==$jm) return true;

}

return false;
}

private function replaceDateStr($row) {

foreach ($row as $key=>$value) {

$row[$key] =  $this->convertDatetoCzech($value);
	
}
return $row;
}


private function convertDatetoCzech($date) {
if ($date==null) return "";
//if ($date=="null") return "";
$pattern = "/(\d{4})-(\d{2})-(\d{2})/";

if (preg_match($pattern, $date, $matches)) {
    // Convert to Czech date format DD.MM.YYYY
    $czech_date = $matches[3] . '.' . $matches[2] . '.' . $matches[1];
    return  $czech_date; // Outputs: 22.09.2024
} else {
        return $date;
}


}

public function delete($id) {
    if ($id) return $this->dbdrv->delete ($this->tab, "where id=$id","limit 1");
    else -1;
    
}
public function getColmVisibilityFilter() {

  $arr["name"]=" ";
  $arr["subject_id"]=" ";
  $arr["active"]=1;
  // $arr["kontakty"]=" ";
  return array_merge($arr,$this->getColmVisibility() );
}

public function getColmVisibility() {

$columns = $this->dbdrv->selectQ("SELECT * FROM columns");
$hidden_columns = $this->dbdrv->selectQ("SELECT * FROM hidden_columns");
$arr = array();

//print_r($columns);
//print_r($hidden_columns);

for ($i=0;$i<count($columns);$i++) {
/*
echo $columns[$i][1].": ";
    if ($this->isHidColmName("c".$columns[$i][0],$hidden_columns)) echo "0";else echo "1";
echo "\n";
*/
$arr[$columns[$i][1]] = $this->isHidColmName("c".$columns[$i][0],$hidden_columns);
     

}

 return $arr;


}
public function getColms(){
  $sql = "SELECT * FROM columns order by name";
  return $this->dbdrv->SelectQAssoc($sql);

}

private function getColmID($jm,$columns) {
for ($i=0;$i<count($columns);$i++) {
if ($columns[$i][1]==$jm) return $columns[$i][0]; 
}
return $jm;
}

public function updateColmn($input) {

  if (!$input) return 0;
  $id = (int) $input["id"];
  if ($id==0) return 0;

  $type = (int) $input["type"];
  if ($type==1) $type="varchar(200)";
  if ($type==2) $type="date";
  if ($type==3) $type="int(11)";

 $fields = [];
  foreach ($input as $column => $value) {
      $fields[] = "$column = '" . addslashes($value) . "'";
  }
  $fields = implode(', ', $fields);
  $query = "UPDATE columns SET $fields WHERE id = " . intval($id);

    $this->dbdrv->query($query);

    $alter = "alter table firm MODIFY Column c$id $type;";

    if ($this->dbdrv->query($alter) === TRUE) {
      return true;
     }
     return false;
   }

public function addColm($name,$type) {
  if (!$name) return -1;
  if (!$type) return -1;
  $sql = "INSERT INTO columns (name,type)
    VALUES ('$name','$type')";

  if ($this->dbdrv->query($sql) === TRUE) {
      $last_id = $this->dbdrv->insertedId();
      if ($type==1) $type="varchar(200)";
  if ($type==2) $type="date";
  if ($type==3) $type="int(11)";

      if ($last_id<1) return -1;
      $alter = "alter table firm add c$last_id $type;";

    if ($this->dbdrv->query($alter) === TRUE) {
     return true;
    } else {
      return false;
    }

    } else {
      return false;
    }

    return true;
}

public function deleteColmn($id) {
  if (!$id) return -1;

  $sql = "delete from columns where id = $id";

    if ($this->dbdrv->query($sql) === TRUE) {
      $alter = "alter table firm drop c$id;";
    }
   else {
    return false;
  }

   if ($this->dbdrv->query($alter) === TRUE) {
    return true;
    } else {
      return false;
    }

    return true;

}

public function saveColmVisibility($input) {
  $columns = $this->dbdrv->selectQ("SELECT * FROM columns");



  foreach ($input as $key=>$value) {
  //echo "$key=>$value \n";
  	if ($value==0) {
          // odebrat hidden_columns
          $real_name = "c".$this->getColmID($key,$columns);
          $sql = "DELETE FROM hidden_columns where name = '$real_name' limit 1";
          $this->conn->query($sql);
      }
      else { // ty co mají 0, čili mají se skrýt, pokud tam nejsou.
      
          $real_name = "c".$this->getColmID($key,$columns);
           
          if (($this->dbdrv->selectQ("select count(*) as count FROM hidden_columns where name like '$key'")[0][0]
          +$this->dbdrv->selectQ("select count(*) as count FROM hidden_columns where name like '$real_name'")[0][0])==0)
         {
            $sql = "INSERT INTO hidden_columns (name) VALUES ('$real_name')";
            $this->conn->query($sql);
            
          }
          
      
      }
      
  }
return 1;
}
public function contactsList()
{
$sql = "
        SELECT 
            fc.surname,            f.id AS firm_id,
            fc.email,
            fc.phone,
            fc.linkedin,
            fc.main,
            fc.img,
            fc.active_c,
            f.name as firm_name
        FROM firm f
        LEFT JOIN firm_contacts fc 
            ON f.id = fc.firm_id
        WHERE f.active = 1
        ORDER BY f.name, fc.main DESC, fc.surname
    ";

    return $this->dbdrv->SelectQAssoc($sql);


}


/*
function getColumns() {
$columns = $this->dbdrv->selectQ("SELECT * FROM columns");
$hidden_columns = $this->dbdrv->selectQ("SELECT * FROM hidden_columns");
$ret=array();

for ($i=0;$i(count($columns) ; ) {
    if ($this->isHidColmName($columns[$i][0],$hidden_columns))
        $ret[$columns[$i][0]] = $columns[$i][1];	
}

return $ret;

}
*/

}// class

?>
