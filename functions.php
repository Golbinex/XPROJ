<?php
// FUNKCE
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
?>