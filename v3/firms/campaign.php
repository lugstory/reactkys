<?php
require_once __DIR__ . "/../dbdriver.php";
require __DIR__ . '/../vendor/autoload.php';

class Campaigns
{
    private $dbdrv;
    private $tab = "email_campaign";
    private $tab_sending = "email_campaign_sending";
    private $tab_contacts = "firm_contacts";
    private $conn;

    public function __construct($conn)
    {
        $this->dbdrv = new dbdriver($conn);
        $this->conn = $conn;
    }
    public function getCampaignExport($id)
    {
        $campaign_sending_id = (int) $id;
        $conn = $this->conn;
        require_once "exportFirmsmailingv3.php";
        return $csv;

    }
    public function getCampaigns()
    {
        $sql = "SELECT    id,
    name,
    created_date,
    sent_date_time,
    end_date,
    send_main_contact,
    send_other_contact,
    secondary_recipient_ids,
    recipient_count,
    undelivered_count,
    confirmed_received_count,
    replied_count,
    note FROM $this->tab order by id DESC";
        $arr = $this->dbdrv->SelectQAssoc($sql);
        return $arr;
    }
    public function getAttachment($id)
    {
        $id = (int) $id;
        $sql = "SELECT attachment, attachment_name FROM email_campaign WHERE id = $id";
        $res = $this->dbdrv->SelectQAssoc($sql);

        if (!$res || !isset($res[0])) {
            return null;
        }

        return $res[0];
    }
    public function getCampaign($id)
    {
        $id = (int) $id;
        $sql = "SELECT    id,
    name,
    created_date,
    sent_date_time,
    end_date,
    send_main_contact,
    send_other_contact,
    secondary_recipient_ids,
    recipient_count,
    undelivered_count,
    confirmed_received_count,
    replied_count,
    note, attachment_name FROM $this->tab WHERE id = $id";
        $arr = $this->dbdrv->SelectQAssoc($sql)[0];

        // vytahuji firm ID pro danou kampan
        $sql = "
            SELECT DISTINCT fc.firm_id
            FROM firm_contacts fc
            JOIN email_campaign_sending ecs ON ecs.contact_id = fc.id
            JOIN email_campaign ec ON ec.id = ecs.email_campaign_id
            WHERE ec.id = $id";
        //        if ((int) $arr["send_main_contact"] == 1)
        $arr["main_recipient_id"] = array_merge(...$this->dbdrv->SelectQ($sql));
        $arr["recipient_count"] = count($arr["main_recipient_id"]);
        return $arr;
    }


    public function getCampaignSeindingExport($id, $contactIds = [])
    {
        $sql = "
        SELECT
            firm.name AS name,
            fc.id AS contact_id,
            fc.surname,
            fc.email,
            fc.phone,
            ecs.sent,
            ecs.status,
            ecs.status_from_cron,
            ecs.datum_aktualizace
        FROM firm_contacts fc
        JOIN email_campaign_sending ecs ON fc.id = ecs.contact_id
        JOIN firm ON fc.firm_id = firm.id
        WHERE fc.active_c = 1
          AND ecs.email_campaign_id = $id
    ";

        // pokud jsou předána konkrétní ID kontaktů → filtruj
        if (!empty($contactIds)) {
            // zabezpečení: převod na integer
            $contactIds = array_map('intval', $contactIds["contact_ids"]);
            $sql .= " AND fc.id IN (" . implode(",", $contactIds) . ") group by fc.id order by name";
        }



        return $this->dbdrv->SelectQAssoc($sql);
    }


    public function updateCampaign($input)
    {
        $id = (int) $input["id"];
        if ($id == 0)
            return 0;


        $fields = [];
        foreach ($input as $column => $value) {
            if (is_array($value))
                continue;
            if ($column === 'created_date')
                continue;
            //if ($column === 'sent_date_time')  continue;
            /*
            if ($column === 'created_date' && $value=="") {
                $values[] = "NULL";
                $columns[] = $column;
             }
            else if ($column === 'sent_date_time' && $value=="") {
                $values[] = "NULL";
                $columns[] = $column;
            }
                */
            if ($value == null)
                $value = "";
            $fields[] = "$column = '" . addslashes($value) . "'";
        }
        if (isset($input["attachment"]) && $input["attachment"] !== "") {
            $bin = base64_decode($input["attachment"]);
            $bin = addslashes($bin);

            $fields[] = "attachment = '$bin'";
            $fields[] = "attachment_name = '" . addslashes($input["attachment_name"]) . "'";
        }
        $fields = implode(', ', $fields);
        $query = "UPDATE $this->tab SET $fields WHERE id = " . intval($id);

        return $this->dbdrv->query($query);
    }

    public function deleteCampaign($id)
    {
        if ($id)
            return $this->dbdrv->delete($this->tab, "WHERE id=$id", "LIMIT 1");
        else
            return -1;
    }

    public function getCampaignContacts($campaign_id)
    {
        $campaign_id = (int) $campaign_id;
        if (!$campaign_id)
            return;
        $sql = "SELECT firm.name as name, fc.id AS contact_id, fc.surname, fc.email,
          ecs.sent,ecs.status,ecs.status_from_cron, ecs.datum_aktualizace FROM firm_contacts fc
         JOIN email_campaign_sending ecs ON fc.id = ecs.contact_id
         JOIN firm on fc.firm_id = firm.id
         WHERE fc.active_c = 1 AND email_campaign_id=$campaign_id group by contact_id order by firm.name";
        return $this->dbdrv->SelectQAssoc($sql);
    }

    public function insert($input)
    {
        $columns = [];
        $values = [];

        foreach ($input as $column => $value) {
            if ($column === 'main_recipient_id')
                continue;
            if ($column === 'created_date') {
                $values[] = "NOW()";
                $columns[] = $column;
            }
            /*else if ($column === 'sent_date_time') {
              $values[] = "NULL";
              $columns[] = $column;
            }*/ else
                if ($column !== 'multi-select') {
                    $columns[] = $column;
                    if (is_array($value))
                        $value = "";
                    if ($value === null) {
                        $values[] = "NULL";
                    } else {
                        $values[] = is_numeric($value)
                            ? $value
                            : "'" . addslashes($value) . "'";
                    }

                }
        }

        $columns = implode(', ', $columns);
        $values = implode(', ', $values);

        $sql = "INSERT INTO $this->tab ($columns) VALUES ($values);";
        $campaignId = 0;
        $this->dbdrv->query($sql);

        $campaignId = mysqli_insert_id($this->conn);

        //$mainRecipients = $input["main_recipient_id"]; // seznam firem
        $mainRecipients = $input["multi-select"]; // seznam firem
        //print_r($mainRecipients);
        $sql = $this->saveMainRecipients($campaignId, $mainRecipients);
        if ($sql == null)
            return 0;

        $this->copyContactsToSending($campaignId, $input["multi-select"], $input["send_other_contact"]);

        return $campaignId > 0;


    }
    public function update($input)
    { // vymazat a znovu vložit
        // hlavní kontaky nejde vynechat
        $updates = [];
        $inserts = [];
        $deletes = [];
        $result = 1;
        $id = $input["id"];
        if ($id == 0)
            return -1;


        $main_recipient_id = $input["main_recipient_id"];//staré - preskočím - není nutný update
        $multi_select = $input["multi-select"] ?? [];//změna - přidám jen ty, co nejsou ve staré. Ty přidám
        $recipient_count = $input["recipient_count"];
        $send_other_contact = (int) $input["send_other_contact"];
        $send_main_contact = (int) $input["send_main_contact"];
        $campaign = $this->getCampaign($id);

        if ($send_other_contact == 1 && $campaign["send_other_contact"] == 0) {
            // přidat ostatní kontaky
            $sql = "SELECT id FROM $this->tab_contacts WHERE active_c=1 AND main=0;";
            $others_contacts = $this->dbdrv->selectQAssoc($sql);
            $inserts = $ids = array_column($others_contacts, 'id');
            $result = $this->copyContactsToSending($id, $inserts, $send_other_contact);//kopíruje obojí


        } else
            if ($send_other_contact == 0 && $campaign["send_other_contact"] == 1) {
                // smazat ostatní kontakty
                $sql = "SELECT id FROM $this->tab_contacts WHERE active_c=1 AND main=0;";
                $others_contacts = $this->dbdrv->selectQAssoc($sql);
                $inserts = $ids = array_column($others_contacts, 'id');

                $deletesStr = implode(', ', $inserts);
                $sql = "DELETE FROM $this->tab_sending WHERE email_campaign_id = $id and firm_id IN ($deletesStr)";
                $result *= $this->dbdrv->query($sql);

            } else {//přidat/ubrat hlavní kontaky

                if (count($multi_select))
                    $inserts = array_diff($input["multi-select"], $campaign["main_recipient_id"]);
                //else
                //  $inserts = $campaign["main_recipient_id"];

                if (count($inserts))
                    $result = $this->copyContactsToSending($id, $inserts, 0);//kopíruje obojí

            }

        if (count($multi_select) > 0) {//je změna
            // main_recipient_id - multi-select = odebírám
            $inserts = array_diff($campaign["main_recipient_id"], $input["multi-select"]);
            if ($inserts) {
                $deletesStr = implode(', ', $inserts);
                $sql = "DELETE FROM $this->tab_sending WHERE email_campaign_id = $id and firm_id IN ($deletesStr)";
                $result *= $this->dbdrv->query($sql);
            }

        }


        $result *= $this->updateCampaign($input);

        return $result;


    }

    private function saveMainRecipients($campaignId, $mainRecipients)
    {//zjistí hlavní kontakt firmy
        $values = [];
        if (!is_array($mainRecipients))
            return null;
        foreach ($mainRecipients as $recipientId) {
            $values[] = "($campaignId, $recipientId)";
        }
        $values = implode(', ', $values);
        return "INSERT INTO email_campaign_contacts (campaign_id, firm_id) VALUES $values;";
    }

    // $all=1 znamená hlavní i vedlejší, jinak hlavní
    private function copyContactsToSending($campaign_id, $_firm_ids, $all = 1)
    {
        if ($campaign_id < 1)
            return false;

        if (!is_array($_firm_ids))
            return false;
        if (count($_firm_ids) == 0)
            return false;

        $firm_ids = implode(', ', $_firm_ids);

        $main = "";
        $values = [];

        if (!$all)
            $main = "AND main = 1";

        $sql = "SELECT id,firm_id FROM $this->tab_contacts WHERE active_c=1 AND firm_id IN ($firm_ids) $main";
        $contacts = $this->dbdrv->SelectQ($sql);

        if (count($contacts) < 1)
            return -1;

        foreach ($contacts as $c) {

            $contact_exist = $this->dbdrv->SelectQAssoc("SELECT count(*) as cnt FROM `email_campaign_sending` where contact_id={$c[0]}
        and email_campaign_id ={$campaign_id};");
            if ($contact_exist[0]["cnt"] > 1)
                continue;

            $values[] = "($campaign_id, {$c[0]},{$c[1]})";
        }

        $_values = implode(', ', $values);

        $columns = "email_campaign_id, contact_id,firm_id";

        if (count($values)) {
            $sql = "INSERT INTO $this->tab_sending ($columns) VALUES $_values;";
            return $this->dbdrv->query($sql);
        } else {
            return 1;

        }
    }

    public function campaignContactsUpdate($campaign_id, $input)
    {
        if ($campaign_id < 1)
            return false;
        $contacts_id = implode(', ', $input["contact_ids"]);

        $sql = "update $this->tab_sending set status = '{$input["status"]}', datum_aktualizace=NOW() where contact_id IN ($contacts_id) AND email_campaign_id=$campaign_id	";

        return $this->dbdrv->query($sql);
    }


    public function delete($campaign_id)
    {
        $campaign_id = (int) $campaign_id;
        if ($campaign_id == 0)
            return -1;

        $sql = "DELETE FROM $this->tab WHERE id = $campaign_id";
        $this->dbdrv->query($sql);
        $sql = "DELETE FROM $this->tab_sending WHERE email_campaign_id = $campaign_id";

        return $this->dbdrv->query($sql);
    }
    public function getCampaignSending($campaign_id = 0)
    {
        if ($campaign_id == 0)
            return false;

        $sql = "SELECT fc.id AS contact_id, fc.surname, fc.email,
          ecs.sent, ecs.datum_aktualizace FROM firm_contacts fc
         JOIN email_campaign_contacts ecc ON fc.firm_id = ecc.firm_id
         JOIN email_campaign_sending ecs ON fc.id = ecs.contact_id
         WHERE fc.active_c = 1 AND campaign_id=$campaign_id group by contact_id;";
        return $this->dbdrv->SelectQAssoc($sql);

    }
    public function deleteCampaignContacts($campaign_id, $contact_ids)
    {
        $campaign_id = (int) $campaign_id;
        if ($campaign_id == 0 || !is_array($contact_ids) || empty($contact_ids))
            return -1;

        $safe_ids = array_map('intval', $contact_ids["contact_ids"]);
        $id_list = implode(',', $safe_ids);

        $sql = "DELETE FROM {$this->tab_sending}
            WHERE email_campaign_id = $campaign_id
            AND contact_id IN ($id_list)";

        return $this->dbdrv->query($sql);
    }

    public function copyCampaign($campaignId)
    {
        $campaignId = (int) $campaignId;

        if ($campaignId < 1) {
            return false;
        }

        $campaign = $this->getCampaign($campaignId);

        if (!$campaign) {
            return false;
        }

        $newCampaign = $campaign;

        unset($newCampaign['id']);
        unset($newCampaign['main_recipient_id']);
        unset($newCampaign['recipient_count']);

        $newCampaign['name'] = 'Kopie ' . $campaign['name'];
        $newCampaign['sent_date_time'] = null;
        $newCampaign['end_date'] = null;
        $newCampaign['undelivered_count'] = 0;
        $newCampaign['confirmed_received_count'] = 0;
        $newCampaign['recipient_count'] = $campaign['recipient_count'];

        $newCampaign['multi-select'] = $campaign['main_recipient_id'];
        $result = $this->insert($newCampaign);

        if (!$result) {
            return false;
        }

        $newCampaignId = mysqli_insert_id($this->conn);

       /*
        $sql = "DELETE FROM {$this->tab_sending}
            WHERE email_campaign_id = {$newCampaignId}";
        $this->dbdrv->query($sql);
*/
        // zkopíruji původní kontakty kampaně
        $sql = "
        INSERT INTO {$this->tab_sending}
        (
            email_campaign_id,
            contact_id,
            firm_id,
            sent,
            status,
            status_from_cron,
            datum_aktualizace
        )
        SELECT
            {$newCampaignId},
            contact_id,
            firm_id,
            0,
            '',
            '',
            NULL
        FROM {$this->tab_sending}
        WHERE email_campaign_id = {$campaignId}
    ";

        $this->dbdrv->query($sql);

        return $newCampaignId;
    }

}

?>
