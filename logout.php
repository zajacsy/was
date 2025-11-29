
<html>
    <head>
        <title>Przykładowy formularz HTML:</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="style.css" type="text/css" />
    </head>
    <body>
        <?php
session_start();
if (isset($_REQUEST['wyloguj'])) {
    session_destroy();
    echo 'Wylogowano <BR/>';
}
?>
<a href="Kontrola_Dostepu_2.php">Powrót</a>

  </body>
</html>
