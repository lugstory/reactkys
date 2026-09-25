<?php

require_once "dbdriver.php";

Class gifts {
private $dbdrv;
private $tab="gifts";
private $conn;

public function __construct($conn) {
    
    $this->dbdrv=new dbdriver($conn);
    $this->conn = $conn;
    
}


public function getgifts($id) {

    $id = (int) $id;
    
   $sql = "SELECT * FROM $this->tab where firm_id=$id order by date DESC";
   
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
        $fields[] = "$column = '" . addslashes($value) . "'";
    }
    $fields = implode(', ', $fields);
    $query = "UPDATE $this->tab SET $fields WHERE id = " . intval($id);
    
      return $this->dbdrv->query($query);
     
    
      
     }
     
public function delete($id) {
    if ($id) return $this->dbdrv->delete ($this->tab, "where id=$id","limit 1");
    else -1;
    
}

} // class

?>
