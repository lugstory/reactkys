<?php

require_once "dbdriver.php";

class practices
{
    private $dbdrv;
    private $tab = "practices";
    private $conn;

    public function __construct($conn)
    {

        $this->dbdrv = new dbdriver($conn);
        $this->conn = $conn;

    }


    public function getpractices($id)
    {

        $id = (int) $id;
        $ret = array();

        if ($id != 0) {
            $where = " where firm_id=$id ";
        } else
            $where = "";
        $sql = "select firm.name, subject, annual,date_time,notes, firm.id as firm_id, count,practices.id
    as id from firm left join practices on firm.id=firm_id $where ORDER by name ASC, date_time DESC;";
        $result = $this->conn->query($sql);
        $x = 0;
        while ($row = $result->fetch_assoc()) {
            $row["key"] = $x;
            $row["insert"] = !$row["date_time"];
            if ($row["date_time"])
                $row["date_time"] = substr($row["date_time"], 0, 7);
            $ret[] = $row;
            //print_r($ret);
            $x++;

        }
        if (count($ret) == 0) {//generuji prázdnou
            $sql = "select firm.name as name, firm.id as firm_id from firm where id=$id ORDER by name DESC;";
            $row = $this->dbdrv->SelectQAssoc($sql)[0];
            if (count($row) > 0) {


                $ret[0]["name"] = $row["name"] ?? "";
                $ret[0]["subject"] = "";
                $ret[0]["annual"] = "";
                $ret[0]["date_time"] = "";
                $ret[0]["notes"] = "";
                $ret[0]["firm_id"] = "";
                $ret[0]["count"] = "";
                $ret[0]["id"] = "";
                $ret[0]["key"] = 0;
                $ret[0]["insert"] = true;
            }

        }
        return $ret;


        // return $this->dbdrv->SelectQAssoc($sql);

    }

    public function save($input)
    {
        $r = 0;
        $count = 0;

        foreach ($input as $key => $value) {

            if ($value["date_time"] == "")
                return false;
            $count++;
            if ($value["insert"] === true) {
                unset($value["insert"]);
                unset($value["name"]);
                unset($value["invalid"]);
                unset($value["key"]);
                $value["date_time"] .= "-01";
                $r += $this->insert($value);
                // echo "insert $r\n";
            } else {
                $value["date_time"] .= "-01";
                unset($value["insert"]);
                unset($value["name"]);
                unset($value["invalid"]);
                unset($value["key"]);
                $r += $this->update($value);
                // echo "update $r\n";

            }

        }
        return $r == $count;

    }

    public function insert($input)
    {
        //echo $this->tab;print_r($input);

        $r = false;

        $res = $this->dbdrv->insert($this->tab, $input);
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

} // class

?>
