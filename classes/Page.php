<?php
class Page
{
    static function display_header($title)
    { ?>
        <html lang="en-GB">

        <head>
            <title><?php echo $title ?></title>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <!-- <link rel="stylesheet" href="style.css" type="text/css" /> -->
        </head>

        <body>
            <?php
        self::display_user_status();
    }
    static function display_user_status() {
        if (isset($_SESSION['login'])) {
            echo "<p>Status: Zalogowany jako <b>" . htmlspecialchars($_SESSION['login']) . "</b></p>";
        } else {
            echo "<p>Status: <b>Niezalogowany</b></p>";
        }
    }
    static function display_navigation()
    { ?>
            <a href="index.php">index</a><br>
            <a href="messages.php">messages</a><br>
            <a href="message_add.php">add new message</a><br>
            <a href="access_control.php">login</a><br>
            <?php
    }
}
