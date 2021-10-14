<?php
// KONTROLA API ÚDAJŮ
// Převzato z: https://www.php.net/manual/en/features.http-auth.php
$valid_passwords = array ("apiuser" => "heslo1234");
$valid_users = array_keys($valid_passwords);

$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];

$validated = (in_array($user, $valid_users)) && ($pass == $valid_passwords[$user]);

if (!$validated) {
  header('WWW-Authenticate: Basic realm="My Realm"');
  header('HTTP/1.0 401 Unauthorized');
  die ("Spatne prihlasovaci udaje");
}
// GENERACE NÁHODNÝCH JSON DAT
// Prečtení předdefinovaných údajů z data.json
$jsonfile = fopen("data.json", "r") or die("Unable to open data.json");
$data = fread($jsonfile, filesize("data.json"));
fclose($jsonfile);
$data = json_decode($data);
// Zapsání náhodných 10-20 změn do proměnné $vystup
$vystup = ['change' => []];
for($i = 0; $i < rand(10, 20); $i++) {
    $trida = $data->trida[array_rand($data->trida)];
    $mistnost = $data->mistnost[array_rand($data->mistnost)];
    $kurz = $data->kurz[array_rand($data->kurz)];
    $ucitel = $data->ucitel[array_rand($data->ucitel)];
    $hodina = nahodnaHodina($data);
    $udalost = $data->udalost[array_rand($data->udalost)];
    // Budeme generovat celkem 4 náhodné typy změn - speciální událost, zrušení hodiny, posun hodiny a suplování
    $typZmeny = rand(0, 3);
    switch ($typZmeny) {
        // Speciální událost
        case 0:
            $vystup['change'][$i]=generovatJson($trida, $hodina, $ucitel, $kurz, $mistnost, $udalost, $hodina, $data->ucitel[array_rand($data->ucitel)], null, null);
            break;
        // Zrušení hodiny
        case 1:
            $vystup['change'][$i]=generovatJson($trida, $hodina, $ucitel, $kurz, $mistnost, null, null, null, $kurz, $mistnost);
            break;
        // Posun hodiny
        case 2:
            $vystup['change'][$i]=generovatJson($trida, $hodina, $ucitel, $kurz, $mistnost, null, nahodnaHodina($data), $ucitel, $kurz, $mistnost);
            break;
        // Suplování
        case 3:
            $vystup['change'][$i]=generovatJson($trida, $hodina, $ucitel, $kurz, $mistnost, null, $hodina, $data->ucitel[array_rand($data->ucitel)], $data->kurz[array_rand($data->kurz)], $mistnost);
            break;
    }
}
$vystup['version'] = '1.0';
// Převedení pole do JSON formátu a výstup
$json = json_encode($vystup, JSON_UNESCAPED_UNICODE);
echo $json;
// Generace JSON údaje
function generovatJson($trida, $puvodni_hodina, $puvodni_ucitel, $puvodni_kurz, $puvodni_mistnost, $udalost, $nova_hodina, $novy_ucitel, $novy_kurz, $nova_mistnost) {
    $udaj = [
        'actual' => [
            'courses' => [$novy_kurz, $novy_kurz],
            'rooms' => [$nova_mistnost, $nova_mistnost],
            'students' => [$trida],
            'teachers' => [$novy_ucitel],
            'timerange' => [
                'from' => $nova_hodina,
                'to' => konecHodiny($nova_hodina)
            ]
        ],
        'scheduled' => [
            'courses' => [$puvodni_kurz, $puvodni_kurz],
            'rooms' => [$puvodni_mistnost, $puvodni_mistnost],
            'students' => [$trida],
            'teachers' => [$puvodni_ucitel],
            'timerange' => [
                'from' => $puvodni_hodina,
                'to' => konecHodiny($puvodni_hodina)
            ]
        ]
    ];
    // Pokud se zadala událost
    if (!empty($udalost)) {
        $udaj['actual']['event'] = $udalost;
    }
    // Pokud se nezadala nová hodina
    if (empty($nova_hodina)) {
        $udaj['actual']['timerange'] = null;
        $udaj['actual']['teachers'] = [];
    }
    return $udaj;
}
// Generování náhodné hodiny
function nahodnaHodina($data) {
    return date('Y-m-d H:i:s', strtotime($data->hodina[array_rand($data->hodina)] . date('Y-m-d')));
}
// Přidání 45 minut od začátku hodiny
function konecHodiny($cas) {
    $cas = strtotime($cas . '+ 45 minute');
    return date('Y-m-d H:i:s', $cas);
}
?>