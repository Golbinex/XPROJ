<?php
// Přihlašování
function isAuthenticated() {
    if(isset($_SESSION['authenticated']) and $_SESSION['authenticated'] == true) {
        return true;
    } else {
        return false;
    }
}
function setAuthenticated($value) {
    if($value) {
        $_SESSION['authenticated'] = true;
    } else {
        $_SESSION['authenticated'] = false;
    }
}

// Příjem dat z API Edookitu
function queryEdookitAPI($edookit_host, $edookit_username, $edookit_password) {
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_URL, $edookit_host);
	curl_setopt($ch, CURLOPT_USERPWD, $edookit_username.":".$edookit_password);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	$edookit_output = curl_exec($ch);
	curl_close($ch);
    $edookit_output = json_decode($edookit_output, true);
    if(isset($edookit_output['change'])) {
        return $edookit_output;
    } else {
        return false;
    }
}
// Příjem dat z SQL databáze
function querySQL() {
    $config = include('config.php');
	// Test připojení k SQL serveru
	try {
		$pdo = new PDO("mysql:host=".$config['sql_host'].";dbname=".$config['sql_database'], $config['sql_username'], $config['sql_password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
		// set the PDO error mode to exception
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		if(!isset($_SESSION['date_from']) or !isset($_SESSION['date_to'])) {
			$_SESSION['date_from'] = date("Y-m-d");
			$_SESSION['date_to'] = date("Y-m-d");
		}
		if($_SESSION['reverse'] == true) {
			$sqlsort = "DESC";
		} else {
			$sqlsort = "ASC";
		}
		// Vybrat všechny záznamy z daného časového rozsahu a seřadit dle daného údaje
		$stmt = $pdo->prepare("SELECT * FROM edookit_zmeny
			WHERE puvodni_datum_od BETWEEN :date_from AND :date_to
			ORDER BY :sort_item ".$sqlsort.";");
		$date_from = $_SESSION['date_from']." 00:00";
		$stmt->bindParam(':date_from', $date_from);
		$date_to = $_SESSION['date_to']." 23:59";
		$stmt->bindParam(':date_to', $date_to);
		$stmt->bindParam(':sort_item', $_SESSION['sort'], PDO::PARAM_INT);
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
	} catch(PDOException $e) {
		//echo "Problém s připojením k SQL serveru";
		echo "Error: " . $e->getMessage();
		exit;
	}
}
?>