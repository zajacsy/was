<?php
require 'vendor/autoload.php';
include_once "session_handler.php";
include_once "classes/Db.php";
include_once "classes/Page.php";
include_once "classes/Filter.php";
include_once "classes/Mailer.php";

Page::display_header("Access Control");

$db = new Db("localhost", "root", "", "bezpieczenstwo");


if (isset($_SESSION['timeout_message'])) {
    echo "<p style='color:red'>" . htmlspecialchars($_SESSION['timeout_message']) . "</p>";
    unset($_SESSION['timeout_message']);
}


/* -------------------------------------------------------
   2FA CODE VERIFICATION (ETAP 2 LOGOWANIA)
-------------------------------------------------------- */
if (isset($_POST['verify_2fa_btn']) && isset($_SESSION['2fa_pending'])) {
    $entered_code = $_POST['two_factor_code'];
    $user_id = $_SESSION['pending_uid'];

    $user = $db->getUserById($user_id);

    if ($user && $entered_code === $user->temp_2fa_code && strlen($entered_code) === 6) {

        unset($_SESSION['2fa_pending']);
        unset($_SESSION['pending_uid']);

        $_SESSION['uid'] = $user->id;
        $_SESSION['login'] = $user->login;
        $_SESSION['expire'] = time() + SESSION_TIMEOUT_SECONDS;

        $db->update2FACode($user->id, null);

        $db->addLog($user->id, "User logged in successfully with 2FA");

        unset($_SESSION['2fa_pending']);
        unset($_SESSION['pending_uid']);
        header("Location: index.php");
        exit;
    } else {
        echo "<p style='color:red'>Invalid 2FA code or temporary session lost. Please try logging in again.</p>";
    }
}
/* -------------------------------------------------------
   ANULOWANIE 2FA
-------------------------------------------------------- */

if (isset($_POST['cancel_2fa_btn']) && isset($_SESSION['2fa_pending'])) {

    unset($_SESSION['2fa_pending']);
    unset($_SESSION['pending_uid']);
    unset($_SESSION['2fa_start_time']);

    $_SESSION['timeout_message'] = "Anulowano weryfikację dwuetapową. Zaloguj się ponownie.";

    header("Location: access_control.php");
    exit;
}


/* -------------------------------------------------------
   LOGIN (ETAP 1 2FA)
-------------------------------------------------------- */
if (isset($_POST['login_btn'])) {

    $login = Filter::filterName($_POST['login']);
    $password = $_POST['password'];

    $user = $db->getUserByLoginAndPassword($login, $password);

    if ($user) {

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $db->update2FACode($user->id, $code);

        $_SESSION['2fa_pending'] = true;
        $_SESSION['pending_uid'] = $user->id;
        $_SESSION['2fa_start_time'] = time();

        //echo "<p style='color:blue'>Login successful. A 6-digit code has been 'sent' to your email (<b>{$user->email}</b>). W celach demonstracyjnych, kod to: <b>$code</b>.</p>";
        //echo "<p>Proszę wprowadzić kod poniżej.</p>";

        $email_sent = Mailer::send2FaCode($user->email, $code);

        if ($email_sent) {
            echo "<p style='color:blue'>Login successful. Kod 2FA został wysłany na adres: <b>{$user->email}</b>. Proszę go wprowadzić poniżej.</p>";
        } else {
            echo "<p style='color:red'>Login successful, ale WYSYŁKA MAILOWA NIE POWIODŁA SIĘ. (Sprawdź konfigurację serwera SMTP)</p>";
            echo "<p>W celach demonstracyjnych, kod to: <b>$code</b>. Proszę go wprowadzić poniżej.</p>";
        }


    } else {
        echo "<p style='color:red'>Login failed</p>";
    }
}

/* -------------------------------------------------------
   LOGOUT
-------------------------------------------------------- */
if (isset($_POST['logout_btn'])) {
    if (isset($_SESSION['uid'])) {
        $db->addLog($_SESSION['uid'], "User logged out");
    }
    session_destroy();
    header("Location: access_control.php");
    exit;
}

/* -------------------------------------------------------
   CREATE NEW USER
-------------------------------------------------------- */
if (isset($_POST['register_btn'])) {

    $login = Filter::filterName($_POST['login']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? '';
    $surname = $_POST['surname'] ?? '';

    if ($password !== $password_confirm) {
        echo "<p style='color:red'>Passwords do not match.</p>";
    } else {
        if ($db->createUser($login, $password, $email, $name, $surname)) {
            echo "<p>User <b>$login</b> has been created.</p>";
        } else {
            echo "<p style='color:red'>User creation failed.</p>";
        }
    }
}

/* -------------------------------------------------------
   DELETE MESSAGE (POST)
-------------------------------------------------------- */
if (isset($_POST['delete_message']) && isset($_SESSION['uid'])) {

    $deleteId = intval($_POST['delete_id']);

    if ($db->deleteMessage($deleteId)) {
        echo "<p>Message deleted.</p>";
        $db->addLog($_SESSION['uid'], "Deleted message ID $deleteId");
    } else {
        echo "<p style='color:red'>Failed to delete message.</p>";
    }
}

/* -------------------------------------------------------
    CHANGE PASSWORD (POST)
-------------------------------------------------------- */
if(isset($_POST['change_password_btn']) && isset($_SESSION['uid'])) {
    $new = $_POST['new_password'];
    $confirm = $_POST['new_password_confirm'];
    if($new !== $confirm) {
        echo "<p style='color:red'>Passwords do not match.</p>";
    } else {
        if($db->changePassword($_SESSION['uid'], $new)) {
            echo "<p>Password changed successfully.</p>";
        } else {
            echo "<p style='color:red'>Password change failed.</p>";
        }
    }
}


/* -------------------------------------------------------
   UPDATE USER (POST)
-------------------------------------------------------- */
if (isset($_POST['update_profile_btn']) && isset($_SESSION['uid'])) {

    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? '';
    $surname = $_POST['surname'] ?? '';

    if ($db->updateUserAccount($_SESSION['uid'], $email, $name, $surname)) {
        echo "<p style='color:green'>Dane profilu zaktualizowane pomyślnie!</p>";
        header("Location: access_control.php");
        exit;
    } else {
        echo "<p style='color:red'>Aktualizacja profilu nie powiodła się (Błąd bazy danych).</p>";
    }
}

?>

<?php
if (!isset($_SESSION['uid']) && !isset($_SESSION['2fa_pending'])):
    ?>
    <hr>
    <h3>Login (Krok 1: Login/Hasło)</h3>
    <form method="post">
        Login: <input type="text" required name="login"><br>
        Password: <input type="password" required name="password"><br>
        <input type="submit" name="login_btn" value="Log in">
    </form>

    <hr>
    <h3>Register new user</h3>
    <form method="post">
        Login: <input type="text" required name="login"><br>
        Email: <input type="email" required name="email"><br>
        Name: <input type="text" required name="name"><br>
        Surname: <input type="text" required name="surname"><br>
        Password: <input type="password" name="password" required minlength="8"><br>
        Confirm: <input type="password" name="password_confirm" required><br>
        <input type="submit" name="register_btn" value="Register">
    </form>

<?php
elseif (isset($_SESSION['2fa_pending'])):
    ?>
    <hr>
    <h3>Two-Factor Authentication (Krok 2: Kod 2FA)</h3>
    <form method="post">
        Wprowadź 6-cyfrowy kod: <input type="text" required name="two_factor_code" pattern="\d{6}" title="Wymagany jest 6-cyfrowy kod"><br>
        <input type="submit" name="verify_2fa_btn" value="Verify Code">
    </form>

    <form method="post" style="margin-top: 10px;">
        <input type="submit" name="cancel_2fa_btn" value="Anuluj i wróć do logowania">
    </form>

<?php
elseif (isset($_SESSION['uid'])):
    $user = $db->getUserById($_SESSION['uid']);
    if (!$user) {
        session_destroy();
        header("Location: access_control.php");
        exit;
    }
    ?>
    <hr>
    <h3>Logout</h3>
    <form method="post">
        <input type="submit" name="logout_btn" value="Log out">
    </form>

    <hr>
    <h3>Change password</h3>
    <form method="post">
        New password: <input type="password" name="new_password" required minlength="8"><br>
        Confirm: <input type="password" name="new_password_confirm" required minlength="8"><br>
        <input type="submit" name="change_password_btn" value="Change Password">
    </form>

    <hr>
    <h3>Aktualizacja danych (Email, Imię, Nazwisko)</h3>
    <form method="post">
        Email: <input type="email" name="email" required value="<?php echo htmlspecialchars($user->email); ?>"><br>
        Imię: <input type="text" name="name" required value="<?php echo htmlspecialchars($user->name); ?>"><br>
        Nazwisko: <input type="text" name="surname" required value="<?php echo htmlspecialchars($user->surname); ?>"><br>
        <input type="submit" name="update_profile_btn" value="Zapisz zmiany">
    </form>


<?php endif; ?>

<hr>
<h3>Logs</h3>
<ul>
    <?php

    if (isset($user)) {
        if (strtoupper($user->privilleges) === 'ADMIN') {
            $logs = $db->getLogs();
        } else {
            $logs = $db->getLogs($user->id);
        }

        foreach ($logs as $log) {
            echo "<li>{$log->time} – " . htmlspecialchars($log->communicate) . " (User ID: {$log->id_user})</li>";
        }
    } else {
        echo "<li>Login to see logs</li>";
    }
    ?>
</ul>


<hr>
<h3>Messages</h3>
<form method="get">
    <select name="id">
        <?php
        $messages = $db->getAllMessages();
        foreach ($messages as $msg) {

            if ($msg->type !== "public" && $msg->type !== "private") continue;

            // private messages only for logged users
            if ($msg->type === "private" && !isset($_SESSION['uid'])) continue;

            echo "<option value='{$msg->id}'>" . htmlspecialchars($msg->name) . "</option>";
        }
        ?>
    </select>
    <input type="submit" value="Show message">
</form>

<?php
if (isset($_GET['id'])) {
    $msg = $db->getMessageById(intval($_GET['id']));
    if ($msg) {
        echo "<p><b>Message type:</b> {$msg->type}</p>";
        echo "<div>" . htmlspecialchars($msg->message) . "</div>";

        if (isset($_SESSION['uid'])) {
            echo "<form method='post'>";
            echo "<input type='hidden' name='delete_id' value='{$msg->id}'>";
            echo "<input type='submit' name='delete_message' value='Delete message'>";
            echo "</form>";
        }
    }
}
?>

<hr>
<P>Navigation</P>
<?php Page::display_navigation(); ?>
</body>
</html>
