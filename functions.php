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
?>