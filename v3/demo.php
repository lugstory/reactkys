
<?php
//declare(strict_types=1);

mb_internal_encoding('UTF-8');

const TZ = 'Europe/Prague';

/**
 * Spuštění: php demo.php [cesta_k_souboru]
 */
$inFile = $argv[1] ?? __DIR__ . '/email.txt';
if (!is_file($inFile)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => "Soubor nenalezen: {$inFile}"], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit(1);
}

$raw = file_get_contents($inFile);
$messages = parseEmailThread($raw);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);


/**
 * Hlavní parser: najde pozice delimiterů a po segmentech je zpracuje.
 */
function parseEmailThread(string $rawText): array {
    // Dekódování entit + normalizace konců řádků (ponecháme \n, ale parser funguje i bez nich)
    $text = html_entity_decode($rawText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace("/\r\n|\r/", "\n", $text);
    $text = preg_replace('/[ \t]+/u', ' ', $text);
    $text = trim($text);

    $messages = [];

    // 1) Najdi všechny začátky bloků (delimiter: "From:" nebo "On dd.mm.yyyy H:mm, ... wrote:")
    $delimRegex = '/(From:\h*|On\h\d{1,2}\.\d{1,2}\.\d{4}\h\d{1,2}:\d{2},\h.+?\hwrote:\h*)/siu';
    if (!preg_match_all($delimRegex, $text, $all, PREG_OFFSET_CAPTURE)) {
        // žádný delimiter — vrať celý text jako jednu zprávu
        $body = trim(normalize_body($text));
        if ($body !== '') {
            return [[
                'from'    => detect_author_from_signature($body),
                'date'    => '',
                'subject' => '',
                'body'    => $body,
            ]];
        }
        return [];
    }

    // 2) Úvodní volný text PŘED prvním delimiterem
    $firstStart = $all[0][0][1]; // offset prvního nalezeného delimiteru
    if ($firstStart > 0) {
        $leadBody = trim(normalize_body(substr($text, 0, $firstStart)));
        if ($leadBody !== '') {
            $messages[] = [
                'from'    => detect_author_from_signature($leadBody),
                'date'    => '',
                'subject' => '',
                'body'    => $leadBody,
            ];
        }
    }

    // 3) Vyrob segmenty: [start_i, start_{i+1}) a každý zpracuj
    $starts = [];
    foreach ($all[0] as $m) {
        $starts[] = $m[1];
    }
    $starts[] = strlen($text); // konec textu jako hranice posledního segmentu

    for ($i = 0; $i < count($starts) - 1; $i++) {
        $segStart = $starts[$i];
        $segEnd   = $starts[$i + 1];
        $segment  = substr($text, $segStart, $segEnd - $segStart);

        // rozhodni typ podle začátku segmentu
        if (preg_match('/^From:/iu', $segment)) {
            $parsed = parseHeadersBlock($segment);
            if ($parsed) $messages[] = $parsed;
        } elseif (preg_match('/^On\h\d{1,2}\.\d{1,2}\.\d{4}\h\d{1,2}:\d{2},/iu', $segment)) {
            $parsed = parseOnWroteBlock($segment);
            if ($parsed) $messages[] = $parsed;
        }
    }

    // 4) Seřazení: novější napřed; prázdné datumy nakonec
    usort($messages, function ($a, $b) {
        $ad = $a['date'] ?? '';
        $bd = $b['date'] ?? '';
        if ($ad === '' && $bd === '') return 0;
        if ($ad === '') return 1;
        if ($bd === '') return -1;
        return strcmp($bd, $ad);
    });

    return $messages;
}

/**
 * Parsuje blok s hlavičkami From / Sent / To [/Cc] / Subject + BODY.
 * Nestriktní: toleruje slepené části (např. "9:15 AMTo:" bez mezery/nového řádku).
 */
function parseHeadersBlock(string $block): ?array {
    // 1) Odchytni hlavičky (Cc volitelně), použij lazy kvalifikátory
    $pattern = '/
        From:\h*(?P<from>.+?)
        \h*Sent:\h*(?P<sent>.+?)
        \h*To:\h*(?P<to>.+?)
        (?:\h*Cc:\h*(?P<cc>.+?))?
        \h*Subject:\h*(?P<subject>.+?)
        (?P<body>.*)
    /siu';

    if (!preg_match($pattern, $block, $m)) {
        return null;
    }

    // 2) Tělo: uřízni na první další delimiter
    $body = preg_split('/(?=From:\h*|On\h\d{1,2}\.\d{1,2}\.\d{4}\h\d{1,2}:\d{2},\h.+?\hwrote:\h*)/siu', $m['body'], 2)[0];

    // 3) Datum → ISO 8601 (AM/PM i český formát)
    $iso = parse_date_fuzzy($m['sent']);

    return [
        'from'    => trim($m['from']),
        'date'    => $iso,
        'subject' => trim($m['subject']),
        'body'    => trim(normalize_body($body)),
    ];
}

/**
 * Parsuje blok "On dd.mm.yyyy H:mm, Autor wrote:" + BODY.
 */
function parseOnWroteBlock(string $block): ?array {
    $pattern = '/
        ^On\h
        (?P<day>\d{1,2})\.(?P<month>\d{1,2})\.(?P<year>\d{4})\h
        (?P<hour>\d{1,2}):(?P<minute>\d{2}),
        \h
        (?P<author>.+?)
        \h+wrote:\h*
        (?P<body>.*)
    /siu';

    if (!preg_match($pattern, $block, $m)) {
        return null;
    }

    // Tělo ukončit před dalším delimiterem
    $body = preg_split('/(?=From:\h*|On\h\d{1,2}\.\d{1,2}\.\d{4}\h\d{1,2}:\d{2},\h.+?\hwrote:\h*)/siu', $m['body'], 2)[0];

    $iso = sprintf('%04d-%02d-%02dT%02d:%02d:00',
        (int)$m['year'], (int)$m['month'], (int)$m['day'], (int)$m['hour'], (int)$m['minute']
    );

    return [
        'from'    => trim($m['author']),
        'date'    => $iso,
        'subject' => '', // reply řádek subject neobsahuje
        'body'    => trim(normalize_body($body)),
    ];
}

/**
 * Očistí tělo: strip tags, decode HTML entity, NBSP→mezera, spojí mezery, zachová \n, odřízne podpisy.
 */
function normalize_body(string $text): string {
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace("\xc2\xa0", ' ', $text); // NBSP
    // Odříznout typické podpisy začínající dvojpomlčkou
    $text = preg_replace('/(^|\n)--\s.*$/siu', '', $text);
    // sloučení mezer, zachovat nové řádky
    $text = preg_replace('/[ \t]+/u', ' ', $text);
    $text = preg_replace("/\n{3,}/u", "\n\n", $text);
    return trim($text);
}

/**
 * Převod různých formátů dat na ISO 8601 (Outlook AM/PM i české dd.mm.yyyy H:mm).
 */
function parse_date_fuzzy(string $str): string {
    $str = trim($str);
    try {
        $dt = new DateTime($str, new DateTimeZone(TZ));
        return $dt->format('c');
    } catch (Exception $e) {
        if (preg_match('/(?P<d>\d{1,2})\.(?P<m>\d{1,2})\.(?P<y>\d{4})\h(?P<h>\d{1,2}):(?P<min>\d{2})/u', $str, $m)) {
            $dt = new DateTime(sprintf('%04d-%02d-%02d %02d:%02d:00',
                (int)$m['y'], (int)$m['m'], (int)$m['d'], (int)$m['h'], (int)$m['min']
            ), new DateTimeZone(TZ));
            return $dt->format('c');
        }
        return ''; // neznámé
    }
}

/**
 * Odhad autora z podpisu/úvodního bloku (e-mail + jméno s diakritikou).
 */
function detect_author_from_signature(string $body): string {
    $email = null;
    if (preg_match('/([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})/iu', $body, $m)) {
        $email = $m[1];
    }
    $name = null;
    if (preg_match('/([A-ZÁČĎÉĚÍŇÓŘŠŤÚŮÝŽ][a-zá-ž]+(?:\s+[A-ZÁČĎÉĚÍŇÓŘŠŤÚŮÝŽ][a-zá-ž]+)+)/u', $body, $n)) {
        $name = trim($n[1]);
    }
    if ($name && $email) return $name . ' <' . $email . '>';
    if ($name) return $name;
    if ($email) return $email;
    return '';
}
