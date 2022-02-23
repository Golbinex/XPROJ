<?php
	// Pokud neexistuje konfigurace, přesměrovat na administraci
	if(!file_exists('config.php')) {
		header('Location: admin.php');
		exit;
	}
	session_start();
	include('functions.php');
?>
<!DOCTYPE html>
<html>
	<head>
		<link rel="stylesheet" href="style.css">
		<script src="scripts.js"></script>
		<?php
			// Pokud se stisklo tlačítko "Odhlásit"
			if (isset($_POST['button_logout'])) {
				setAuthenticated(false);
				$_SESSION['date_from'] = date("Y-m-d");
				$_SESSION['date_to'] = date("Y-m-d");
				$_SESSION['sort'] = 6;
				$_SESSION['reverse'] = false;
			}
			// Pokud je uživatel přihlášen, zobrazovat jen jeden sloupec, jinak zobrazovat dva
			if(isAuthenticated()) {
				echo "
				<style>
					div {
						column-count:1;
						-moz-column-count:1;
						-webkit-column-count:1;
					}
				</style>";
			} else {
				echo "
				<style>
					div {
						column-count:2;
						-moz-column-count:2;
						-webkit-column-count:2;
					}
				</style>";
			}
		?>
	</head>
	<body onload="startTime()">
		<div class="buttons">
			<?php
				// Tlačítkové menu
				if(isAuthenticated()) {
					echo "
					<form action='index.php' method='post'>
						<input type='button' value='Administrace' onclick=\"window.location.href='admin.php';\">
						<input type='submit' name='button_export' value='Exportovat tabulku do CSV'>
						<input type='submit' name='button_logout' value='Odhlásit'>
					</form>";
				} else {
					echo "<input type='button' value='Přihlásit' onclick=\"window.location.href='admin.php';\">";
				}
			?>
		</div>
		<!-- HODINY -->
		<h1 id="txt" style="font-size: 48px"></h1>
		<h1>Změnový rozvrh -  
		<?php
			function cesky_den($den) {
				static $nazvy = array('Neděle', 'Pondělí', ' Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota');
				return $nazvy[$den];
			}
			echo cesky_den(date("w"))." ";
			echo date("d. m. Y");
		?>
		</h1>
		<div>
		<table>
			<?php
				if(!isset($_SESSION['sort'])) $_SESSION['sort'] = 6;
				if(isAuthenticated()) {
					if(isset($_GET['sort'])) $_SESSION['sort'] = $_GET['sort'];
					if(isset($_GET['reverse'])) $_SESSION['reverse'] = filter_var($_GET['reverse'], FILTER_VALIDATE_BOOLEAN);
					// Funkce pro zobrazení třídícího tlačítka
					function sortButton($text, $column_number, $colspan = 1, $color = "black") {
						echo "<td colspan='".$colspan."' style='color: ".$color."'><b><a href='index.php?sort=".$column_number;
						if($_SESSION['sort'] == $column_number and $_SESSION['reverse'] == false) {
							echo "&reverse=true";
						} else {
							echo "&reverse=false";
						}
						echo "'>".$text;
						if($_SESSION['sort'] == $column_number) {
							if($_SESSION['reverse'] == true) {
								echo " ↑";
							} else {
								echo " ↓";
							}
						}
						echo "</b></td></a>";
					}
					echo "<tr class='headtr'>";
					sortButton("Třída", 6);
					echo "
						<td><b>Datum</b></td>
						<td colspan='2'><b>Hodina</b></td>
						<td colspan='2'><b>Učitel</b></td>
						<td colspan='2'><b>Kurz</b></td>
						<td colspan='2'><b>Místnost</b></td>";
					sortButton("Událost", 13, 2);
					// Pokud není nastavený rozsah, nastavit na dnešní datum
					if(!isset($_SESSION['date_from']) or !isset($_SESSION['date_to'])) {
						$_SESSION['date_from'] = date("Y-m-d");
						$_SESSION['date_to'] = date("Y-m-d");
					} else if(isset($_POST['date_from']) and isset($_POST['date_to'])) {
						// Pokud "datum od" je dřívější datum než "datum do", nastavit proměnné, jinak vypsat chybu
						if(strtotime($_POST['date_from']) <= strtotime($_POST['date_to'])) {
							$_SESSION['date_from'] = $_POST['date_from'];
							$_SESSION['date_to'] = $_POST['date_to'];
						} else {
							$error = true;
						}
					}
					echo "</tr>
					<tr class='headtr'>
						<td></td>
						<td><form action='index.php' method='post'>
							Od: <input type='date' name='date_from' value='".$_SESSION['date_from']."' max='".date("Y-m-d")."'><br>
							Do: <input type='date' name='date_to' value='".$_SESSION['date_to']."' max='".date("Y-m-d")."'><br>
							<input type='submit' name='date_update' value='Aktualizovat'>";
					if($error) echo "<br><b style='color:red'>Nesprávný rozsah</b>";
					echo "</form></td>";
					sortButton("✘", 2, 1, "red");
					sortButton("✔", 4, 1, "green");
					sortButton("✘", 7, 1, "red");
					sortButton("✔", 8, 1, "green");
					sortButton("✘", 9, 1, "red");
					sortButton("✔", 10, 1, "green");
					sortButton("✘", 11, 1, "red");
					sortButton("✔", 12, 1, "green");
					echo "<td></td></tr>";
				} else {
					echo "
					<tr class='headtr'>
						<td><b>Třída</b></td>
						<td colspan='2'><b>Hodina</b></td>
						<td colspan='2'><b>Učitel</b></td>
						<td colspan='2'><b>Kurz</b></td>
						<td colspan='2'><b>Místnost</b></td>
						<td><b>Událost</b></td>
					</tr>
					<tr class='headtr'>
						<td></td>
						<td style='color: red'>✘</td>
						<td style='color: green'>✔</td>
						<td style='color: red'>✘</td>
						<td style='color: green'>✔</td>
						<td style='color: red'>✘</td>
						<td style='color: green'>✔</td>
						<td style='color: red'>✘</td>
						<td style='color: green'>✔</td>
						<td></td>
					</tr>"; 
				}
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
					if (isset($_POST['button_export'])) {
						// https://stackoverflow.com/questions/16251625/how-to-create-and-download-a-csv-file-from-php-script
						function array_to_csv_download($array, $filename = "export.csv", $delimiter=";") {
							// open raw memory as file so no temp files needed, you might run out of memory though
							$f = fopen($filename, 'w'); 
							// loop over the input array
							foreach ($array as $line) { 
								// generate csv lines from the inner arrays
								fputcsv($f, $line, $delimiter); 
							}
							// reset the file pointer to the start of the file
							fseek($f, 0);
							// make php send the generated csv lines to the browser
							fpassthru($f);
							echo "<input type='button' value='Stáhnout CSV soubor' onclick=\"window.location.href='export.csv';\">";
						}
						array_to_csv_download($result);
					} else if (file_exists("export.csv")) unlink("export.csv");
				} catch(PDOException $e) {
					//echo "Problém s připojením k SQL serveru";
					echo "Error: " . $e->getMessage();
					exit;
				}
				// Vlozeni dat do tabulky
				for($i = 0; $i < count($result); $i++) {
					// Speciální událost
					if(!empty($result[$i]['udalost'])) {
						zobrazitZmenu(
							'bluetr',
							$result[$i]['trida'],
							$result[$i]['puvodni_datum_od'],
							$result[$i]['puvodni_ucitel'],
							$result[$i]['puvodni_kurz'],
							$result[$i]['puvodni_mistnost'],
							$result[$i]['nove_datum_od'],
							$result[$i]['novy_ucitel'],
							$result[$i]['novy_kurz'],
							$result[$i]['nova_mistnost'],
							$result[$i]['udalost'],
							$result[$i]['nove_datum_do']);
					// Zrušení hodiny		
					} else if(strtotime($result[$i]['nove_datum_od']) < 0) {
						zobrazitZmenu(
							'redtr',
							$result[$i]['trida'],
							$result[$i]['puvodni_datum_od'],
							$result[$i]['puvodni_ucitel'],
							$result[$i]['puvodni_kurz'],
							$result[$i]['puvodni_mistnost'],
							null,
							null,
							null,
							null,
							'ZRUŠENO');
					// Posun hodiny
					} else if($result[$i]['puvodni_datum_od'] != $result[$i]['nove_datum_od']) {
						zobrazitZmenu(
							'greentr',
							$result[$i]['trida'],
							$result[$i]['puvodni_datum_od'],
							$result[$i]['puvodni_ucitel'],
							$result[$i]['puvodni_kurz'],
							$result[$i]['puvodni_mistnost'],
							$result[$i]['nove_datum_od'],
							$result[$i]['novy_ucitel'],
							$result[$i]['novy_kurz'],
							$result[$i]['nova_mistnost'],
							'POSUN HODINY');
					// Suplování
					} else if($result[$i]['puvodni_ucitel']!=$result[$i]['novy_ucitel']) {
						zobrazitZmenu(
							'purpletr',
							$result[$i]['trida'],
							$result[$i]['puvodni_datum_od'],
							$result[$i]['puvodni_ucitel'],
							$result[$i]['puvodni_kurz'],
							$result[$i]['puvodni_mistnost'],
							$result[$i]['nove_datum_od'],
							$result[$i]['novy_ucitel'],
							$result[$i]['novy_kurz'],
							$result[$i]['nova_mistnost'],
							'SUPLOVÁNÍ');
					} else {
						zobrazitZmenu(
							'',
							$result[$i]['trida'],
							$result[$i]['puvodni_datum_od'],
							$result[$i]['puvodni_ucitel'],
							$result[$i]['puvodni_kurz'],
							$result[$i]['puvodni_mistnost'],
							$result[$i]['nove_datum_od'],
							$result[$i]['novy_ucitel'],
							$result[$i]['novy_kurz'],
							$result[$i]['nova_mistnost'],
							'');
					}
				}
				function zobrazitZmenu($styl, $trida, $puvodni_hodina, $puvodni_ucitel, $puvodni_kurz, $puvodni_mistnost, $nova_hodina, $novy_ucitel, $novy_kurz, $nova_mistnost, $udalost, $nova_hodina_do = null) {
					echo "<tr class='".$styl."'>";
					echo "<td>".$trida."</td>";
					// Při přihlášení zobrazovat datum
					if(isAuthenticated()) {
						echo "<td>".date("d/m/Y", strtotime($puvodni_hodina))."</td>";
					}
					// Hodina
					if($puvodni_hodina == $nova_hodina) {
						echo "<td colspan='2'>";
						echo zjistitHodinu($nova_hodina, $nova_hodina_do)."</td>";
					} else if ($nova_hodina == null) {
						echo "<td colspan='2'><s>";
						echo zjistitHodinu($puvodni_hodina)."</s></td>";
					} else {
						echo "<td class='scheduled_td'>";
						echo "<s>".zjistitHodinu($puvodni_hodina)."</s>";
						echo "</td><td class='actual_td'>";
						echo zjistitHodinu($nova_hodina, $nova_hodina_do)."</td>";
					}
					// Učitel
					if($puvodni_ucitel == $novy_ucitel) {
						echo "<td colspan='2'>";
						echo $novy_ucitel."</td>";
					} else if ($novy_ucitel == null) {
						echo "<td colspan='2'><s>";
						echo $puvodni_ucitel."</s></td>";
					} else {
						echo "<td class='scheduled_td'>";
						echo "<s>".$puvodni_ucitel."</s>";
						echo "</td><td class='actual_td'>";
						echo $novy_ucitel."</td>";
					}
					// Kurz
					if($puvodni_kurz == $novy_kurz) {
						echo "<td colspan='2'>";
						echo $novy_kurz."</td>";
					} else if ($novy_kurz == null) {
						echo "<td colspan='2'><s>";
						echo $puvodni_kurz."</s></td>";
					} else {
						echo "<td class='scheduled_td'>";
						echo "<s>".$puvodni_kurz."</s>";
						echo "</td><td class='actual_td'>";
						echo $novy_kurz."</td>";
					}
					// Místnost
					if($puvodni_mistnost == $nova_mistnost) {
						echo "<td colspan='2'>";
						echo $nova_mistnost."</td>";
					} else if ($nova_mistnost == null) {
						echo "<td colspan='2'><s>";
						echo $puvodni_mistnost."</s></td>";
					} else {
						echo "<td class='scheduled_td'>";
						echo "<s>".$puvodni_mistnost."</s>";
						echo "</td><td class='actual_td'>";
						echo $nova_mistnost."</td>";
					}
					// Událost
					echo "<td><b>".$udalost."</b></td>";
					echo "</tr>";
				}
				// Funkce pro zjištění čísla hodiny z času
				function zjistitHodinu($cas_od, $cas_do = null) {
					if (empty($cas_od)) return null;
					if (!empty($cas_do)) {
						$vystup = date("H:i", strtotime($cas_od))." - ".date("H:i", strtotime($cas_do));
					} else {
						$hodina = array("0:00", "7:00", "7:50", "8:45", "9:45", "10:35", "11:30", "12:25", "13:15", "14:05");
						$vystup = array_search(date("G:i", strtotime($cas_od)), $hodina);
					}
					return $vystup;
				}
			?>
		</table>
		</div>
	</body>
</html> 