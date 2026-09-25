<?php

class Response
{
    private Services $services;

    public function __construct(Services $services)
    {
        $this->services = $services;
    }

    public function output($str): void
    {
        if (isset($_GET["csvexport"])) {
            $this->csvOutput($str);
            exit;
        }

        if (!is_array($str)) {
            if ($str == "0") {
                $str = "err";
            }
            echo json_encode(["msg" => $str]);
        } else {
            echo json_encode($str);
        }
    }

    public function csvOutput($str): void
    {
        if ($str === null) {
            return;
        }

        $fp = fopen(getcwd() . '/csvexport.csv', 'w');
        if (is_array($str)) {
            $firstRow = reset($str);
            $headers = array_merge([''], array_keys($firstRow));
            fputcsv($fp, $this->convertEncoding($headers), ';', '"', '\\');

            foreach ($str as $key => $row) {
                if (isset($row["name"])) {
                    $row["name"] = preg_replace('/\/\(kont\).*/', '', $row["name"]);
                }
                fputcsv($fp, $this->convertEncoding(array_merge([$key], $row)), ';', '"', '\\');
            }
        } else {
            fputs($fp, $str);
        }

        fclose($fp);
        header("Content-Type: text/plain; charset=Windows-1250");
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="/v3/csvexport.csv"');
        readfile(getcwd() . '/csvexport.csv');
        exit;
    }

    private function convertEncoding(array $array): array
    {
        return array_map(function ($value) {
            if ($value === null) {
                return "";
            }
            return iconv("UTF-8", "Windows-1250//IGNORE", (string) $value);
        }, $array);
    }

    public function downloadAttachment($id): void
    {
        $data = $this->services->campaigns->getAttachment($id);

        if (!$data || empty($data["attachment"])) {
            http_response_code(404);
            echo "Soubor nenalezen";
            exit;
        }

        $filename = $data["attachment_name"];
        $filedata = $data["attachment"];

        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Content-Length: " . strlen($filedata));

        echo $filedata;
        exit;
    }
}

if (!function_exists('fix_encoding')) {
    function fix_encoding($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = fix_encoding($value);
            }
        } elseif (is_string($data)) {
            return iconv('ISO-8859-2', 'UTF-8//IGNORE', $data);
        }
        return $data;
    }
}
