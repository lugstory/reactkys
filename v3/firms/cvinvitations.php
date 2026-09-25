<?php

require_once "dbdriver.php";

class cvInvitations
{
    private $dbdrv;
    private $tab = "cv_invitatios";
    private $conn;

    public function __construct($conn)
    {

        $this->dbdrv = new dbdriver($conn);
        $this->conn = $conn;

    }


    public function getcvIvnvitatios($id)
    {

        $id = (int) $id;
        $ret = array();

        if ($id != 0) {
            $where = " where firm_id=$id ";
        } else {
            $where = "";
        }
        $sql = "select firm.name as name,{$this->tab}.`date`, `A_adres`, `E_adres`, `I_adres`, `A_neadres`, `E_neadres`, `I_neadres`, `vsem`, `pozn`, firm.id
    as firm_id,{$this->tab}.id as id from firm inner join {$this->tab} on firm.id={$this->tab}.firm_id $where ORDER BY firm.name ASC, cv_invitatios.date DESC;";

        $result = $this->conn->query($sql);
        $x = 0;
        while ($row = $result->fetch_assoc()) {

            $ret[] = $row;
            //print_r($ret);
            $x++;

        }

        if (count($ret) == 0) {//generuji prázdnou
            $sql = "select firm.name as name, firm.id as firm_id from firm where id=$id ORDER by name DESC;";
            $row = $this->dbdrv->SelectQAssoc($sql);
            if (count($row) > 0) {

                $ret[0]["id"] = 0;
                $ret[0]["firm_id"] = $row[0]["firm_id"];
                $ret[0]["date"] = null;
                $ret[0]["name"] = $row[0]["name"];
                $ret[0]["A_adres"] = 0;
                $ret[0]["E_adres"] = 0;
                $ret[0]["I_adres"] = 0;
                $ret[0]["A_neadres"] = 0;
                $ret[0]["E_neadres"] = 0;
                $ret[0]["I_neadres"] = 0;
                $ret[0]["vsem"] = 0;
                $ret[0]["pozn"] = "";
                $ret[0]["insert"] = 1;
            }

        }
        return $ret;


        // return $this->dbdrv->SelectQAssoc($sql);

    }

    public function save($input)
    {
        $result = [
            "inserted" => 0,
            "updated" => 0,
            "deleted" => 0
        ];

        // ✅ INSERT
        if (!empty($input["insert"])) {
            foreach ($input["insert"] as $row) {
                unset($row["id"]);
                unset($row["insert"]);
                unset($row["name"]);

                $res = $this->dbdrv->insert($this->tab, $row);
                if ($res != -1) {
                    $result["inserted"]++;
                }
            }
        }

        // ✅ UPDATE
        if (!empty($input["update"])) {
            foreach ($input["update"] as $row) {
                $id = (int) $row["id"];
                unset($row["id"]); // id nesmí být v SET
                unset($row["name"]);
                unset($row["updated"]);

                $fields = [];
                foreach ($row as $column => $value) {
                    if ($value === null)
                        $value = "";
                    $fields[] = "$column = '" . addslashes($value) . "'";
                }

                $fieldsSql = implode(', ', $fields);
                $query = "UPDATE {$this->tab} SET $fieldsSql WHERE id = $id";

                $res = $this->dbdrv->query($query);
                if ($res) {
                    $result["updated"]++;
                }
            }
        }

        // ✅ DELETE
        if (!empty($input["delete"])) {
            foreach ($input["delete"] as $id) {
                $id = (int) $id;

                $res = $this->dbdrv->delete($this->tab, "where id=$id", "limit 1");
                if ($res) {
                    $result["deleted"]++;
                }
            }
        }
        
        $r = 0;
        if (count($input["insert"]))
            $r += $result["inserted"];
        if (count($input["update"]))
            $r += $result["updated"];
        if (count($input["delete"]))
            $r += $result["deleted"];
        return (bool) $r;
    }
/*
    public function insert($input)
    {
        unset($input[0]["name"]);
        //print_r($input[0]);

        $r = false;

        $res = $this->dbdrv->insert($this->tab, $input[0]);
        if ($res != -1)
            $r = true;
        //return $this->dbdrv->insertedId();



        return $r;

    }

    public function update($input)
    {

        if (!$input)
            return 0;
        $firm_id = (int) $input["firm_id"];
        $id = (int) $input["id"];

        $fields = [];
        foreach ($input as $column => $value) {
            if ($value == null)
                $value = "";
            $fields[] = "$column = '" . addslashes($value) . "'";
        }
        $fields = implode(', ', $fields);
        $query = "UPDATE $this->tab SET $fields WHERE firm_id = $firm_id AND id=$id";

        return $this->dbdrv->query($query);



    }

    public function delete($id)
    {
        if ($id)
            return $this->dbdrv->delete($this->tab, "where id=$id", "limit 1");
        else
            -1;

    }
*/
} // class

?>
