<?php

class ContactVcfExporter
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Hlavní veřejná metoda
     * $input = ["contact_ids" => [...]]
     */
    public function export($input)
    {
        $whereIds = "";

        if (is_array($input) && isset($input['contact_ids']) && is_array($input['contact_ids']) && count($input['contact_ids']) > 0) {
            $ids = array_map('intval', $input['contact_ids']);
            $whereIds = " AND fc.id IN (" . implode(",", $ids) . ")";
        }

        // SQL pro export všech AKTIVNÍCH kontaktů
        $sql = "
            SELECT
                fc.id,
                fc.surname,
                fc.email,
                fc.phone,
                fc.linkedin,
                fc.img,
                f.name AS firm_name
            FROM firm_contacts fc
            LEFT JOIN firm f ON f.id = fc.firm_id
            WHERE fc.active_c = 1
            $whereIds
            ORDER BY f.name, fc.surname
        ";

        $result = $this->conn->query($sql);

        if (!$result) {
            header("Content-Type: application/json; charset=UTF-8");
            echo json_encode(["msg" => "SQL error", "error" => $this->conn->error]);
            return;
        }

        // HTTP hlavičky pro download
        header('Content-Type: text/vcard; charset=UTF-8');
        header('Content-Disposition: attachment; filename="contacts.vcf"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        // BOM pro lepší kompatibilitu UTF-8
        echo "\xEF\xBB\xBF";

        while ($row = $result->fetch_assoc()) {
            $fullName = trim((string)($row['surname'] ?? ''));
            if ($fullName === '') continue;

            [$last, $first] = $this->splitName($fullName);

            $email    = trim((string)($row['email'] ?? ''));
            $phone    = trim((string)($row['phone'] ?? ''));
            $linkedin = trim((string)($row['linkedin'] ?? ''));
            $img      = trim((string)($row['img'] ?? ''));
            $firmName = trim((string)($row['firm_name'] ?? ''));

            $lines = [];
            $lines[] = "BEGIN:VCARD";
            $lines[] = "VERSION:3.0";
            $lines[] = "FN:" .$this->vcardEscape($last) . ";" . $this->vcardEscape($first);
            $lines[] = "N:" . $this->vcardEscape($last) . "($firmName);" . $this->vcardEscape($first) . ";;;";

            if ($firmName !== '') {
                $lines[] = "ORG:" . $this->vcardEscape($firmName);
            }
            if ($email !== '') {
                $lines[] = "EMAIL;TYPE=INTERNET:" . $this->vcardEscape($email);
            }
            if ($phone !== '') {
                $lines[] = "TEL;TYPE=CELL:" . $this->vcardEscape($phone);
            }
            if ($linkedin !== '') {
                $lines[] = "URL:" . $this->vcardEscape($linkedin);
                $lines[] = "NOTE:" . $this->vcardEscape("LinkedIn: " . $linkedin);
            }

            // FOTO
            if ($img !== '') {
                $b64 = $this->stripDataUrlPrefix($img);
                if (strlen($b64) > 50) {
                    $photoLine = "PHOTO;ENCODING=b;TYPE=JPEG:" . $b64;
                    $lines[] = $this->foldLine($photoLine);
                }
            }

            $lines[] = "END:VCARD";

            foreach ($lines as $line) {
                echo $line . "\r\n";
            }
        }

        $result->free();
    }

    /* =============== HELPERS =============== */

    private function vcardEscape($value)
    {
        $value = (string)$value;
        $value = str_replace("\\", "\\\\", $value);
        $value = str_replace(";", "\;", $value);
        $value = str_replace(",", "\,", $value);
        $value = preg_replace("/\r\n|\r|\n/", "\\n", $value);
        return $value;
    }

    private function splitName($full)
    {
        $full = trim($full);

        if ($full === '') return ['', ''];

        // Formát "Příjmení, Jméno"
        if (strpos($full, ',') !== false) {
            $parts = explode(',', $full, 2);
            return [trim($parts[0]), trim($parts[1] ?? '')];
        }

        $parts = preg_split('/\s+/', $full);
        if (!$parts || count($parts) === 1) {
            return [$full, ''];
        }

        $last = array_pop($parts);
        $first = implode(' ', $parts);

        return [$last, $first];
    }

    private function stripDataUrlPrefix($dataUrl)
    {
        if (stripos($dataUrl, 'data:image/') === 0) {
            return preg_replace('~^data:image/[^;]+;base64,~i', '', $dataUrl);
        }
        return $dataUrl;
    }

    /**
     * Foldování řádků vCard (75 znaků + CRLF + mezera)
     */
    private function foldLine($line)
    {
        $out = '';
        $chunk = '';
        $count = 0;
        $len = strlen($line);

        for ($i = 0; $i < $len; $i++) {
            $chunk .= $line[$i];
            $count++;

            if ($count >= 75) {
                $out .= $chunk . "\r\n" . ' ';
                $chunk = '';
                $count = 0;
            }
        }

        return $out . $chunk;
    }
}
