<?php
include_once "classes/Page.php";
include_once "classes/Db.php";

Page::display_header("Messages");
$db = new Db("localhost", "root", "", "bezpieczenstwo");

// Jeśli jest wyszukiwanie – pobierz wyniki
$search = $_GET['search'] ?? null;

$allMessages = $db->getAllMessages();
$searchResults = [];

if ($search) {
    $searchResults = $db->searchMessages($search);
}
?>

<hr>
<p>Wszystkie wiadomości</p>
<ol>
    <?php foreach ($allMessages as $msg): ?>
        <li>
            <?php echo htmlspecialchars($msg->message); ?>
            <a href='message_edit.php?id=<?php echo $msg->id; ?>'>[Edit]</a>
        </li>
    <?php endforeach; ?>
</ol>

<hr>

<!-- Pole wyszukiwania -->
<form method="GET">
    <input type="text" name="search" placeholder="Szukaj wiadomości..."
           value="<?php echo htmlspecialchars($search ?? ''); ?>">
    <button type="submit">Szukaj</button>
</form>

<hr>

<?php if ($search): ?>
    <p>Wyniki wyszukiwania dla: <strong><?php echo htmlspecialchars($search); ?></strong></p>

    <ol>
        <?php foreach ($searchResults as $msg): ?>
            <li>
                <?php echo htmlspecialchars($msg->message); ?>
                <a href='message_edit.php?id=<?php echo $msg->id; ?>'>[Edit]</a>
            </li>
        <?php endforeach; ?>

        <?php if (count($searchResults) === 0): ?>
            <li><i>Brak wyników.</i></li>
        <?php endif; ?>
    </ol>
<?php endif; ?>

<hr>
<p>Nawigacja</p>
<?php Page::display_navigation(); ?>
</body>
</html>
