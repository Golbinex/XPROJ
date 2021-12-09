<?php
    // PŘEVEDENÍ DAT Z API EDOOKITU DO SQL DATABÁZE
    if(file_exists('config.php')) {
        $config = include('config.php');
        // Test připojení k API
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $config['edookit_host']);
		curl_setopt($ch, CURLOPT_USERPWD, $config['edookit_username'].":".$config['edookit_password']);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		$result = curl_exec($ch);
		curl_close($ch);
        $result = json_decode($result, true);
        if(!isset($result['change'])) {
            echo "Problém s připojením k API edookitu";
            exit;
        }
        // Test připojení k SQL serveru
        try {
            $pdo = new PDO("mysql:host=".$config['sql_host'].";dbname=".$config['sql_database'], $config['sql_username'], $config['sql_password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
            // set the PDO error mode to exception
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Zápis údajů API do SQL databáze
            try {
                $sql="";
                for($i = 0; $i < count($result['change']); $i++) {
                    $sql = $sql."INSERT INTO edookit_zmeny (
                        puvodni_datum_od,
                        puvodni_datum_do,
                        nove_datum_od,
                        nove_datum_do,
                        trida,
                        puvodni_ucitel,
                        novy_ucitel,
                        puvodni_kurz,
                        novy_kurz,
                        puvodni_mistnost,
                        nova_mistnost,
                        udalost)
                        VALUES (
                        '".$result['change'][$i]['scheduled']['timerange']['from']."',
                        '".$result['change'][$i]['scheduled']['timerange']['to']."',
                        '".$result['change'][$i]['actual']['timerange']['from']."',
                        '".$result['change'][$i]['actual']['timerange']['to']."',
                        '".$result['change'][$i]['scheduled']['students'][0]."',
                        '".$result['change'][$i]['scheduled']['teachers'][0]."',
                        '".$result['change'][$i]['actual']['teachers'][0]."',
                        '".$result['change'][$i]['scheduled']['courses'][0]."',
                        '".$result['change'][$i]['actual']['courses'][0]."',
                        '".$result['change'][$i]['scheduled']['rooms'][0]."',
                        '".$result['change'][$i]['actual']['rooms'][0]."',
                        '".$result['change'][$i]['actual']['event']."'
                        );";
                }
                $pdo->exec($sql);
            } catch (Exception $e) {
                echo $sql . "<br>" . $e->getMessage();
            }
        } catch(PDOException $e) {
            echo "Problém s připojením k SQL serveru";
            exit;
        }
    } else {
        echo "Chybí config.php";
    }
?>