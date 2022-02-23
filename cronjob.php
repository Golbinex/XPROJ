<?php
    include('functions.php');
    // PŘEVEDENÍ DAT Z API EDOOKITU DO SQL DATABÁZE
    if(file_exists('config.php')) {
        $config = include('config.php');
        // Příjem dat z API Edookitu
        $result = queryEdookitAPI($config['edookit_host'], $config['edookit_username'], $config['edookit_password']);
        if(!$result) {
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
                $stmt = $pdo->prepare("INSERT INTO edookit_zmeny (
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
                                        udalost )
                                        VALUES (
                                            :puvodni_datum_od,
                                            :puvodni_datum_do,
                                            :nove_datum_od,
                                            :nove_datum_do,
                                            :trida,
                                            :puvodni_ucitel,
                                            :novy_ucitel,
                                            :puvodni_kurz,
                                            :novy_kurz,
                                            :puvodni_mistnost,
                                            :nova_mistnost,
                                            :udalost 
                                        );");
                $stmt->bindParam(':puvodni_datum_od', $puvodni_datum_od);
                $stmt->bindParam(':puvodni_datum_do', $puvodni_datum_do);
                $stmt->bindParam(':nove_datum_od', $nove_datum_od);
                $stmt->bindParam(':nove_datum_do', $nove_datum_do);
                $stmt->bindParam(':trida', $trida);
                $stmt->bindParam(':puvodni_ucitel', $puvodni_ucitel);
                $stmt->bindParam(':novy_ucitel', $novy_ucitel);
                $stmt->bindParam(':puvodni_kurz', $puvodni_kurz);
                $stmt->bindParam(':novy_kurz', $novy_kurz);
                $stmt->bindParam(':puvodni_mistnost', $puvodni_mistnost);
                $stmt->bindParam(':nova_mistnost', $nova_mistnost);
                $stmt->bindParam(':udalost', $udalost);
                for($i = 0; $i < count($result['change']); $i++) {
                    $puvodni_datum_od = $result['change'][$i]['scheduled']['timerange']['from'];
                    $puvodni_datum_do = $result['change'][$i]['scheduled']['timerange']['to'];
                    $nove_datum_od = $result['change'][$i]['actual']['timerange']['from'];
                    $nove_datum_do = $result['change'][$i]['actual']['timerange']['to'];
                    $trida = $result['change'][$i]['scheduled']['students'][0];
                    $puvodni_ucitel = $result['change'][$i]['scheduled']['teachers'][0];
                    $novy_ucitel = $result['change'][$i]['actual']['teachers'][0];
                    $puvodni_kurz = $result['change'][$i]['scheduled']['courses'][0];
                    $novy_kurz = $result['change'][$i]['actual']['courses'][0];
                    $puvodni_mistnost = $result['change'][$i]['scheduled']['rooms'][0];
                    $nova_mistnost = $result['change'][$i]['actual']['rooms'][0];
                    $udalost = $result['change'][$i]['actual']['event'];
                    $stmt->execute();
                }
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