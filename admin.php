<?php
    session_start();
    include('functions.php');
?>
<!doctype HTML>
<html>
    <head>
        <title>Administrace</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <?php
            // Pokud neexistuje konfigurace, použít základní údaje a pustit uživatele k úpravě bez přihlášení, jinak použít údaje z konfiguračního souboru a vyžadovat přihlášení
            if(!file_exists('config.php')) {
                $config = include('config_default.php');
                echo "<h1>Instalace aplikace</h1>";
                adminform();
                setAuthenticated(true);
                exit;
            } else {
                $config = include('config.php');
            }
            // Pokud je uživatel přihlášen/první instalace a odeslal údaje k formuláři
            if(isAuthenticated() and isset($_POST['button_save'])) {
                // API údaje jsou v pořádku, zapsat do proměnné
                $config['edookit_host'] = $_POST['edookit_host'];
                $config['edookit_username'] = $_POST['edookit_username'];
                $config['edookit_password'] = $_POST['edookit_password'];
                // SQL údaje jsou v pořádku, zapsat do proměnné
                $config['sql_host'] = $_POST['sql_host'];
                $config['sql_username'] = $_POST['sql_username'];
                $config['sql_password'] = $_POST['sql_password'];
                $config['sql_database'] = $_POST['sql_database'];
                $config['admin_password'] = $_POST['admin_password'];
                // Test připojení k API
                if(!queryEdookitAPI($_POST['edookit_host'], $_POST['edookit_username'], $_POST['edookit_password'])) {
                    adminForm("Problém s připojením k API edookitu");
                    exit;
                }
                // Test připojení k SQL serveru
                try {
                    $pdo = new PDO("mysql:host=".$_POST['sql_host'].";dbname=".$_POST['sql_database'], $_POST['sql_username'], $_POST['sql_password']);
                    // set the PDO error mode to exception
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    // Vytvoření tabulky se změnami, pokud tabulka neexistuje
                    try {
                        $sql = "CREATE TABLE edookit_zmeny (
                            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                            puvodni_datum_od DATETIME,
                            puvodni_datum_do DATETIME,
                            nove_datum_od DATETIME,
                            nove_datum_do DATETIME,
                            trida VARCHAR(63),
                            puvodni_ucitel VARCHAR(63),
                            novy_ucitel VARCHAR(63),
                            puvodni_kurz VARCHAR(127),
                            novy_kurz VARCHAR(127),
                            puvodni_mistnost VARCHAR(63),
                            nova_mistnost VARCHAR(63),
                            udalost VARCHAR(127),
                            UNIQUE(puvodni_datum_od, puvodni_datum_do, nove_datum_od, nove_datum_do, trida, puvodni_ucitel, novy_ucitel, puvodni_kurz, novy_kurz, puvodni_mistnost, nova_mistnost, udalost)
                        )";
                        $pdo->exec($sql);
                    } catch (Exception $e) {
                        //echo $sql . "<br>" . $e->getMessage();
                    }
                } catch(PDOException $e) {
                    adminForm("Problém s připojením k SQL serveru");
                    exit;
                }
                // Kontroly proběhly v pořádku, uložit údaje z proměnné config do souboru config.php
                $var_str = var_export($config, true);
                $var_str = "<?php\n\n return $var_str;\n\n";
                file_put_contents('config.php', $var_str);
                $config = include('config.php');
                adminForm("Údaje byly úspěšně uloženy", "green");
                exit();
            } else if (isset($_POST['button_logout'])) {
                setAuthenticated(false);
            }
            // Pokud se shoduje zadané heslo s uloženým heslem, přihlásit uživatele
            if ($_POST['admin_password_login'] == $config['admin_password']) {
                setAuthenticated(true);
            }
            // Pokud uživatel není přihlášen
            if(!isAuthenticated()) {
                
                // Vypsat chybu o nesprávném heslu pokud bylo zadáno, jinak nevypisovat nic
                if (isset($_POST['admin_password_login']) and $_POST['admin_password_login'] != $config['admin_password']) {
                    loginForm("Nesprávné heslo");
                } else {
                    loginForm();
                }
            // Uživatel je přihlášen
            } else {
                adminForm();
            }
            function loginForm($error = null) {
                echo "
                <h1>Přihlášení do administrace</h1>
                <form action='admin.php' method='post'>
                    Heslo:<br><input type='password' name='admin_password_login'><br>
                    <input type='submit' name='button_login' value='Přihlásit'>
                </form>";
                if(isset($error)) {
                    echo "<b style='color: red'>".$error."</b>";
                }
            }
            function adminForm($message = null, $color = "red") {
                global $config;
                echo "
                <h1>Administrace</h1>
                <form action='admin.php' method='post'>
                    Adresa API Edookitu:<br><input type='text' name='edookit_host' value='".$config['edookit_host']."' required='required'><br>
                    Uživatelské jméno k API Edookitu:<br><input type='text' name='edookit_username' value='".$config['edookit_username']."' required='required'><br>
                    Heslo k API Edookitu:<br><input type='password' name='edookit_password' value='".$config['edookit_password']."' required='required'><br>
                    Adresa SQL serveru:<br><input type='text' name='sql_host' value='".$config['sql_host']."' required='required'><br>
                    Uživatelské jméno k SQL serveru:<br><input type='text' name='sql_username' value='".$config['sql_username']."' required='required'><br>
                    Heslo k SQL serveru:<br><input type='password' name='sql_password' value='".$config['sql_password']."' required='required'><br>
                    SQL databáze:<br><input type='text' name='sql_database' value='".$config['sql_database']."' required='required'><br>
                    Heslo k administraci:<br><input type='password' name='admin_password' value='".$config['admin_password']."' required='required'><br><br>
                    <input type='submit' name='button_save' value='Uložit'>
                    <input type='button' value='Zobrazit rozvrh' onclick=\"window.location.href='index.php';\">
                    <input type='submit' name='button_logout' value='Odhlásit'>
                </form>";
                if(isset($message)) {
                    echo "<b style='color: ".$color."'>".$message."</b>";
                }
            }
        ?>
    </body>
</html>