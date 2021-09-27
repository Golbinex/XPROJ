 <!DOCTYPE html>
<html>
	<head>
		<style>
			body {
				cursor: none;
			}
			h1 {
				text-align:center;
				margin-top: 0px;
				margin-bottom: 0px;
			}
			table {
				border-collapse: collapse;
			}
			td {
				border: 1px solid black;
				font-size: 24px;
			}
			.redtr {
				background: #eea2ad !important;
			}
			.greentr {
				background: lightgreen !important;
			}
			.bluetr {
				background: powderblue !important;
			}
			.purpletr {
				background: plum !important;
			}
			.headtr {
				background: lightgray;
			}
			div {
				align:center;
				column-count:2;
				-moz-column-count:2;
				-webkit-column-count:2;
			}
		</style>
		<script>
			function startTime() {
				var today = new Date();
				var h = today.getHours();
				var m = today.getMinutes();
				var s = today.getSeconds();
				m = checkTime(m);
				s = checkTime(s);
				document.getElementById('txt').innerHTML =
				h + ":" + m + ":" + s;
				var t = setTimeout(startTime, 1000);
			}
			function checkTime(i) {
				if (i < 10) {i = "0" + i};  // add zero in front of numbers < 10
			return i;
			}
		</script>
	</head>
	<body onload="startTime()">
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
			<tr class='headtr'>
				<td><b>Třída</b></td><td><b>Hodina</b></td><td><b>Učitel</b></td><td><b>Kurz</b></td><td><b>Místnost</b></td><td><b>Událost</b></td>
			</tr>
			<?php
				// Stazeni dat pres API maximalne jednou za 15 minut (900 sekund)
				$timefile = fopen("time.txt", "r") or die("Unable to open time.txt");
				$time = fread($timefile, filesize("time.txt"));
				fclose($timefile);
				$time = $time + 900;
				if(time() > $time) {
					// Extrakce rozvrhu z API
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_URL, 'https://kourilkova8-login.edookit.net/api/scheduler/v1/change');
					curl_setopt($ch, CURLOPT_USERPWD, "apiuser1:<SMAZANO>");
					curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
					curl_setopt($ch, CURLOPT_TIMEOUT, 30);
					$result = curl_exec($ch);
					curl_close($ch);
					
					$jsonfile = fopen("json.txt", "w") or die("Unable to open json.txt");
					fwrite($jsonfile, $result);
					fclose($jsonfile);
					$timefile = fopen("time.txt", "w") or die("Unable to open time.txt");
					fwrite($timefile, time());
					fclose($timefile);
				} else {
					$jsonfile = fopen("json.txt", "r") or die("Unable to open json.txt");
					$result = fread($jsonfile, filesize("json.txt"));
					fclose($jsonfile);
				}
				// Vlozeni dat do tabulky
				$json = json_decode($result);
				// Urceni cisla hodiny z casu jejiho zacatku
				$hodina = array("0:00", "7:00", "7:50", "8:45", "9:45", "10:35", "11:30", "12:25", "13:15", "14:05");
				for($i = 0; $i < count($json->change); $i++) {
					if(!empty($json->change[$i]->actual->event)) {
						echo "<tr class='bluetr'>";
						echo "<td>".$json->change[$i]->actual->students[0]."</td>";
						echo "<td>".date("H:i", strtotime($json->change[$i]->actual->timerange->from))." - ".date("H:i", strtotime($json->change[$i]->actual->timerange->to))."</td>";
						echo "<td><s>".$json->change[$i]->scheduled->teachers[0]."</s> ".$json->change[$i]->actual->teachers[0]."</td>";
						echo "<td><s>".$json->change[$i]->scheduled->courses[0]."</s> ".$json->change[$i]->actual->courses[0]."</td>";
						echo "<td><s>".$json->change[$i]->scheduled->rooms[0]."</s> ".$json->change[$i]->actual->rooms[0]."</td>";
						echo "<td>".$json->change[$i]->actual->event."</td>";
					} else if(empty($json->change[$i]->actual->timerange->from)) {
						echo "<tr class='redtr'>";
						echo "<td>".$json->change[$i]->actual->students[0]."</td>";
						echo "<td><s>".array_search(date("G:i", strtotime($json->change[$i]->scheduled->timerange->from)), $hodina)."</s></td>";
						echo "<td><s>".$json->change[$i]->scheduled->teachers[0]."</s></td>";
						echo "<td><s>".$json->change[$i]->scheduled->courses[0]."</s></td>";
						echo "<td><s>".$json->change[$i]->scheduled->rooms[0]."</s></td>";
						echo "<td><b>ZRUŠENO</b></td>";
					} else if($json->change[$i]->scheduled->timerange->from != $json->change[$i]->actual->timerange->from) {
						echo "<tr class='greentr'>";
						echo "<td>".$json->change[$i]->actual->students[0]."</td>";
						//echo "<td>".date("H:i", strtotime($json->change[$i]->actual->timerange->from))." - ".date("H:i", strtotime($json->change[$i]->actual->timerange->to))."</td>";
						echo "<td><s>".array_search(date("G:i", strtotime($json->change[$i]->scheduled->timerange->from)), $hodina)."</s> ".array_search(date("G:i", strtotime($json->change[$i]->actual->timerange->from)), $hodina)."</td>";
						// UCITEL
						echo "<td>";
						if($json->change[$i]->scheduled->teachers[0]!=$json->change[$i]->actual->teachers[0]) echo "<s>".$json->change[$i]->scheduled->teachers[0]."</s> ";
						echo $json->change[$i]->actual->teachers[0]."</td>";
						// KURZ
						echo "<td>";
						if($json->change[$i]->scheduled->courses[0]!=$json->change[$i]->actual->courses[0]) echo "<s>".$json->change[$i]->scheduled->courses[0]."</s> ";
						echo $json->change[$i]->actual->courses[0]."</td>";
						// MISTNOST
						echo "<td>";
						if($json->change[$i]->scheduled->rooms[0]!=$json->change[$i]->actual->rooms[0]) echo "<s>".$json->change[$i]->scheduled->rooms[0]."</s> ";
						echo $json->change[$i]->actual->rooms[0]."</td>";
						echo "<td><b>POSUN HODINY</b></td>";
					} else if($json->change[$i]->scheduled->teachers[0]!=$json->change[$i]->actual->teachers[0]) {
						echo "<tr class='purpletr'>";
						echo "<td>".$json->change[$i]->actual->students[0]."</td>";
						echo "<td>";
						if($json->change[$i]->scheduled->timerange->from != $json->change[$i]->actual->timerange->from) echo "<s>".array_search(date("G:i", strtotime($json->change[$i]->scheduled->timerange->from)), $hodina)."</s> ";
						echo array_search(date("G:i", strtotime($json->change[$i]->actual->timerange->from)), $hodina)."</td>";
						// UCITEL
						echo "<td>";
						if($json->change[$i]->scheduled->teachers[0]!=$json->change[$i]->actual->teachers[0]) echo "<s>".$json->change[$i]->scheduled->teachers[0]."</s> ";
						echo $json->change[$i]->actual->teachers[0]."</td>";
						// KURZ
						echo "<td>";
						if($json->change[$i]->scheduled->courses[0]!=$json->change[$i]->actual->courses[0]) echo "<s>".$json->change[$i]->scheduled->courses[0]."</s> ";
						echo $json->change[$i]->actual->courses[0]."</td>";
						// MISTNOST
						echo "<td>";
						if($json->change[$i]->scheduled->rooms[0]!=$json->change[$i]->actual->rooms[0]) echo "<s>".$json->change[$i]->scheduled->rooms[0]."</s> ";
						echo $json->change[$i]->actual->rooms[0]."</td>";
						echo "<td><b>SUPLOVÁNÍ</b></td>";
					} else {
						echo "<tr>";
						echo "<td>".$json->change[$i]->actual->students[0]."</td>";
						//echo "<td>".date("H:i", strtotime($json->change[$i]->actual->timerange->from))." - ".date("H:i", strtotime($json->change[$i]->actual->timerange->to))."</td>";
						if(!array_search(date("G:i", strtotime($json->change[$i]->scheduled->timerange->from)), $hodina)) {
							echo "<td>".date("H:i", strtotime($json->change[$i]->actual->timerange->from))." - ".date("H:i", strtotime($json->change[$i]->actual->timerange->to))."</td>";
						} else {
							echo "<td>";
							if($json->change[$i]->scheduled->timerange->from!=$json->change[$i]->actual->timerange->from) echo "<s>".array_search(date("G:i", strtotime($json->change[$i]->scheduled->timerange->from)), $hodina)."</s> ";
							echo array_search(date("G:i", strtotime($json->change[$i]->actual->timerange->from)), $hodina)."</td>";
						}
						// UCITEL
						echo "<td>";
						if($json->change[$i]->scheduled->teachers[0]!=$json->change[$i]->actual->teachers[0]) echo "<s>".$json->change[$i]->scheduled->teachers[0]."</s> ";
						echo $json->change[$i]->actual->teachers[0]."</td>";
						// KURZ
						echo "<td>";
						if($json->change[$i]->scheduled->courses[0]!=$json->change[$i]->actual->courses[0]) echo "<s>".$json->change[$i]->scheduled->courses[0]."</s> ";
						echo $json->change[$i]->actual->courses[0]."</td>";
						// MISTNOST
						echo "<td>";
						if($json->change[$i]->scheduled->rooms[0]!=$json->change[$i]->actual->rooms[0]) echo "<s>".$json->change[$i]->scheduled->rooms[0]."</s> ";
						echo $json->change[$i]->actual->rooms[0]."</td>";
						echo "<td></td>";
					}
					echo "</tr>";
				}
			?>
		</table>
		</div>
	</body>
</html> 