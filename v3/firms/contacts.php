<?php
require_once "dbdriver.php";
require __DIR__ . '/../vendor/autoload.php';

Class contacts {
private $dbdrv;
private $tab="firm_contacts";
private $conn;
private $vocative;

public function __construct($conn) {
    
    $this->dbdrv=new dbdriver($conn);
    $this->conn = $conn;
    $this->vocative = new Granam\CzechVocative\CzechName();
    // Základní nastavení NameCase které používám já, více v dokumentaci
    Tamtamchik\NameCase\Formatter::setOptions([ 'Czech' => false, 'lazy' => false ]);
    
}

public function deleteContact($id) {
    if ($id) return $this->dbdrv->delete ($this->tab, "where id=$id","limit 1");
    else -1;
    
}

public function getFirmContacts($id)
{
    $id = (int) $id;

    $conn=$this->dbdrv->getConn();
    
    $i=0;
    $arr=array();
    
   $sql = "SELECT * FROM $this->tab where firm_id=$id order by main DESC,active_c DESC";
   $result = $conn->query($sql);
   if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        
    $arr[$i]["id"]=$row["id"];
    $arr[$i]["firm_id"]=$row["firm_id"];
    $arr[$i]["main"]=$row["main"];        
    $arr[$i]["active_c"]=$row["active_c"];
    $arr[$i]["surname"]=$row["surname"];
    $arr[$i]["linkedin"]=$row["linkedin"];
    $arr[$i]["gender"]=$row["gender"];

    $subject = urlencode("");
    if ($row["surname"])    {

    $name=explode(" ",$this->vocative->vocative($row["surname"]));

    if (isset($name[1])) $surname=$name[1];else $surname=$name[0];

    if ($this->vocative->isMale($surname)) $salut="Vážený pane";else $salut="Vážená paní";
    if($row["gender"]=="žena") $salut="Vážená paní";

    $body = urlencode($salut." ".$surname.",\n");
    $arr[$i]["mailto"]="mailto:{$row["email"]}?subject=$subject&body=$body";
    } else {
        $arr[$i]["mailto"]="";

    }

    $arr[$i]["email"]=$row["email"];
        $arr[$i]["phone"]=phoneFormat($row["phone"]);
    $arr[$i]["main"]=$row["main"];
    $arr[$i]["img"]=$row["img"];
    
    $i++;       
     }
    }
   
    return $arr;

}

public function insertContacts($input)
{
    $r=false;
    if (isset($input["main"]) && $input["main"]=="1")
        $this->setContactsMain($input["firm_id"]);
      
    if ($this->dbdrv->insert($this->tab,$input) != -1)
        $r = true; 
    
           
        
return $r;
   
}
public function setContactsMain($id) { // v�echny main =0
$id = (int) $id;
if ($id==0) return 0;

  $query = "UPDATE $this->tab SET main=0 WHERE firm_id = " . intval($id);
    
      return $this->dbdrv->query($query);
}

public function updateContacts($input) {
    
    if (!$input) return 0;
    $id = (int) $input["id"];
    if ($id==0) return 0;
    
    if ($input["main"]=="1")
        $this->setContactsMain($input["id"]);
    
   $fields = [];
    foreach ($input as $column => $value) {
        if ($value==null) $value="";
        if ($column=="mailto") continue;
        if ($column=="id") continue;
        if ($column=="phone") {
            $value = phoneClear($value);
            }
        if ($column=="main" || $column=="active_c" ) {
            if ($value == '')
                $value = 0;
            }
        if ($column=="gender" && $value=='') continue;


    $fields[] = "$column = '" . addslashes($value) . "'";
    }
    $fields = implode(', ', $fields);
    $query = "UPDATE $this->tab SET $fields WHERE id = " . intval($id);
    
      return $this->dbdrv->query($query);
     
    
      
     }
    public function search($search) {
        $conn=$this->dbdrv->getConn();
    if ($search==null) return array();
    if (strlen($search)<2) return array();

    $search = $conn->real_escape_string($search);
    $sql = 'SELECT firm_id FROM `firm_contacts` WHERE surname LIKE "%'.$search.'%" OR email LIKE "%'.$search.'%" OR phone LIKE "%'.$search.'%"';

      return $this->dbdrv->query($sql);


    }
    



}//class
