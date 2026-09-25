<?php
require_once "conn.php";
class dbdriver{
private $cn;



function __construct ($conn) {

if ($conn==null) die; 

    $this->cn=$conn;
    

}

public function getConn () {
return  $this->cn;
}

function query ($sql) {


$result = $this->cn ->query($sql);

if (is_object($result)) {
    $row = $result->fetch_assoc();
} else {$row =$result; }


return $row;
}

function delete ($tab, $where,$limit) {

$sql="delete from $tab $where $limit";
return $this->cn ->query($sql);

}


function selectWhere ($tab,$colm,$where) {
$List="*";
$res=array();

if (is_array($colm)) 
    $List = implode(', ', $colm);
    else $List=$colm; 
        
    $sql="select $List from $tab $where";

$result =$this->cn->query($sql);


$i=0;
if ($result = $this->cn -> query($sql)) {
  while ($row = $result -> fetch_assoc()) {
     
     $res[$i]=$row;
     $i++;
  }
}

return $res;

}

function select ($tab,$colm) {
return $this->selectWhere ($tab,$colm,"");
}

public function selectQAssoc ($sql) {
  $res=array();
    
  $i=0;
    if ($result = $this->cn -> query($sql)) {
      while ($row = $result -> fetch_assoc()) {
         
         $res[$i]=$row;
         $i++;
      }
    }
  
  return $res;
  
  }
   
public function selectQ ($sql) {
  $res=array();
    
  $i=0;
    if ($result = $this->cn -> query($sql)) {
      while ($row = $result -> fetch_row()) {
         
         $res[$i]=$row;
         $i++;
      }
    }
  
  return $res;
  
  }
  
public function insert($tab,$input) {  
    $attrs = "";
 
    foreach ($input as $key => $value) {
    if ($key=="id") continue;
    if ($key=="phone") {
    $value= str_replace("+420","",$value);
    $value= str_replace("+","",$value);
    $value= str_replace(" ","",$value);
    
    }
    
    if ($value==null) $value="";
        
        if(strlen($value) != 0){   
        
        $attrs .="$key='$value',";
                
        }  
    }
    $attrs = substr($attrs,0,-1);
    

      $sql = "INSERT INTO $tab set $attrs";
        
        if ($this->cn->query($sql))
        return  array ("id"=>mysqli_insert_id($this->cn));
        
        else return -1;
   
}  


/**
     * Hromadné vložení více řádků.
     *
     * @param string $tab    Název tabulky.
     * @param array  $rows   Pole asociativních polí. Každé pole reprezentuje jeden řádek.
     * @param bool   $ignore true => INSERT IGNORE (volitelné).
     *
     * @return array ['affected'=>int, 'first_id'=>int|null, 'last_id'=>int|null, 'errors'=>array]
     */
    public function insertMany(string $tab, array $rows, bool $ignore = false): array
    {
        $resultInfo = ['affected'=>0, 'first_id'=>null, 'last_id'=>null, 'errors'=>[]];

        if (empty($rows)) {
            return $resultInfo;
        }

        // 1) Sjednocení sloupců napříč všemi řádky (kromě 'id')
        $columns = [];
        foreach ($rows as $row) {
            foreach ($row as $k => $_) {
                if ($k === 'id') continue;
                if (!in_array($k, $columns, true)) {
                    $columns[] = $k;
                }
            }
        }
        if (empty($columns)) {
            return $resultInfo;
        }

        // 2) Sestavení VALUES (...), (...), ...
        $valuesSqlParts = [];
        foreach ($rows as $rIdx => $row) {
            $vals = [];
            foreach ($columns as $col) {
                $value = $row[$col] ?? '';
                // stejné čištění pro 'phone' jako v insert()
                if ($col === 'phone' && $value !== null) {
                    $value = str_replace("+420","",$value);
                    $value = str_replace("+","",$value);
                    $value = str_replace(" ","",$value);
                }
                if ($value === null) $value = '';
                // escapování
                $value = $this->cn->real_escape_string($value);
                $vals[] = "'{$value}'";
            }
            $valuesSqlParts[] = '(' . implode(', ', $vals) . ')';
        }

        $colsSql = implode(', ', $columns);
        $verb   = $ignore ? 'INSERT IGNORE' : 'INSERT';
        $sql    = "{$verb} INTO {$tab} ({$colsSql}) VALUES " . implode(', ', $valuesSqlParts);

        // 3) Transakce pro jistotu (pokud není autocommit vypnutý, mysqli ji zvládá)
        $this->cn->begin_transaction();
        try {
            if (!$this->cn->query($sql)) {
                throw new \Exception($this->cn->error);
            }
            $affected = $this->cn->affected_rows;
            $firstId  = $this->cn->insert_id ?: null;
            $lastId   = ($firstId !== null && $affected > 0) ? ($firstId + $affected - 1) : null;

            $resultInfo['affected'] = $affected;
            $resultInfo['first_id'] = $firstId;
            $resultInfo['last_id']  = $lastId;

            $this->cn->commit();
        } catch (\Throwable $e) {
            $this->cn->rollback();
            $resultInfo['errors'][] = $e->getMessage();
        }

        return $resultInfo;
    }


public function insertedId() {
  return  mysqli_insert_id($this->cn);

}
public function update(string $tab, array $data, string $where): bool
{
    if (empty($tab) || empty($data) || empty($where)) {
        return false;
    }

    $sets = [];
    foreach ($data as $k => $v) {
        // jednoduché escapování – držíme se stylu tvého driveru
        $val = $this->cn->real_escape_string((string)$v);
        $sets[] = "{$k}='{$val}'";
    }
    $setSql = implode(', ', $sets);

    $sql = "UPDATE {$tab} SET {$setSql} {$where}";
    return (bool)$this->cn->query($sql);
}
}//class






?>
