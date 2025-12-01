<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


define('SESSION_TIMEOUT_MINUTES', 5);
define('SESSION_TIMEOUT_SECONDS', SESSION_TIMEOUT_MINUTES * 60);

function check_session() {
    if (isset($_SESSION['uid']) && isset($_SESSION['expire'])) {
        $now = time();

        if ($now > $_SESSION['expire']) {

            session_unset();
            session_destroy();

            $_SESSION['timeout_message'] = "Twoja sesja wygasła z powodu 
            braku aktywności (" . SESSION_TIMEOUT_MINUTES . " minut). Zaloguj się ponownie.";
            header("Location: access_control.php");
            exit;
        } else {
            $_SESSION['expire'] = $now + SESSION_TIMEOUT_SECONDS;
        }
    }
}

check_session();
?>