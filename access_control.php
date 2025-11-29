<?php
include_once "session_handler.php";
include_once "classes/Db.php";
include_once "classes/Page.php";
include_once "classes/Filter.php";

Page::display_header("Access Control");

$db = new Db("localhost", "root", "", "bezpieczenstwo");

/* -------------------------------------------------------
   LOGIN
-------------------------------------------------------- */
if (isset($_POST['login_btn'])) {

    $login = Filter::filterName($_POST['login']);
    $password = $_POST['password'];  // hasła nie filtrujemy

    $user = $db->getUserByLoginAndPassword($login, $password);

    if ($user) {
        $_SESSION['uid'] = $user->id;
        $_SESSION['login'] = $user->login;

        $db->addLog($user->id, "User logged in");
        echo "<p>Logged in as <b>{$user->login}</b></p>";
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
    echo "<p>You have been logged out.</p>";
}

/* -------------------------------------------------------
   CREATE NEW USER
-------------------------------------------------------- */
if (isset($_POST['register_btn'])) {

    $login = Filter::filterName($_POST['login']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if ($password !== $password_confirm) {
        echo "<p style='color:red'>Passwords do not match.</p>";
    } else {
        if ($db->createUser($login, $password)) {
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


?>

<!-- -------------------------------------------------------
     LOGIN FORM (only for guests)
-------------------------------------------------------- -->
<?php if (!isset($_SESSION['uid'])): ?>
    <hr>
    <h3>Login</h3>
    <form method="post">
        Login: <input type="text" required name="login"><br>
        Password: <input type="password" required name="password"><br>
        <input type="submit" name="login_btn" value="Log in">
    </form>

    <hr>
    <h3>Register new user</h3>
    <form method="post">
        Login: <input type="text" required name="login"><br>
        Password: <input type="password" name="password" required minlength="8"><br>
        Confirm: <input type="password" name="password_confirm" required><br>
        <input type="submit" name="register_btn" value="Register">
    </form>

<?php endif; ?>

<!-- -------------------------------------------------------
     LOGOUT FORM (only for logged users)
-------------------------------------------------------- -->
<?php if (isset($_SESSION['uid'])): ?>
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
<?php endif; ?>

<hr>
<h3>Logs</h3>
<ul>
    <?php
    // Pobranie zalogowanego użytkownika (jeśli zalogowany)
    $user = null;
    if (isset($_SESSION['uid'])) {
        $user = $db->getUserById($_SESSION['uid']); // wymaga getUserById() w Db
    }

    if ($user) {
        // Jeśli admin – wszystkie logi, jeśli zwykły użytkownik – tylko własne
        if (strtoupper($user->privilleges) === 'ADMIN') {
            $logs = $db->getLogs(); // admin widzi wszystkie logi
        } else {
            $logs = $db->getLogs($user->id); // zwykły user widzi tylko swoje logi
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

            // TASK 7: whitelist
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
