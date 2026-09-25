<?php

require_once "dbdriver.php";

Class workshops {
private $dbdrv;
private $tab="workshops";
private $conn;


public function __construct($conn) {
    
    $this->dbdrv=new dbdriver($conn);
    $this->conn = $conn;
    
}

public function getworkshops($id)
{
   $id = (int) $id;
    if ($id==0) return -1;
 
return $this->dbdrv->selectQAssoc("SELECT $this->tab.id, $this->tab.date,$this->tab.notes, types_of_workshops.typ as type FROM workshops LEFT JOIN types_of_workshops ON workshops.type = types_of_workshops.id where firmId='".$id."' order by workshops.date DESC");
// return $this->dbdrv->selectQAssoc("SELECT $this->tab.id, $this->tab.date,$this->tab.notes, type FROM workshops where firmId='".$id."'");

}

public function insert($input)
{
    $r=false;
          
    if ($this->dbdrv->insert($this->tab,$input) != -1)
        $r = true; 
    
           
        
return $r;
   
}

public function delete($id) {
    if ($id) return $this->dbdrv->delete ($this->tab, "where id=$id","limit 1");
    else -1;
    
}

public function update($input) {
    
    if (!$input) return 0;
    $id = (int) $input["id"];
    if ($id==0) return 0;
  
  
   $fields = [];
    foreach ($input as $column => $value) {
    if ($value==null) continue;
        $fields[] = "$column = '" . addslashes($value) . "'";
    }
    $fields = implode(', ', $fields);
    $query = "UPDATE $this->tab SET $fields WHERE id = " . intval($id);
    
      return $this->dbdrv->query($query);
     
    
      
     }

}
