<?php
include_once "classes/Page.php";
include_once "classes/Db.php";

Page::display_header("Edit message");

$db = new Db("localhost", "root", "", "bezpieczenstwo");

if (!isset($_GET['id'])) {
    echo "No message ID.";
    exit;
}

$id = intval($_GET['id']);
$msg = $db->getMessageById($id);

if (!$msg) {
    echo "Message not found.";
    exit;
}

if (isset($_POST['save_message'])) {
    $db->updateMessage($id, $_POST['name'], $_POST['type'], $_POST['content']);
    echo "<b>Message updated!</b><br>";
}
?>

<hr>
<p>Edit message</p>

<form method="post">
    <table>
        <tr>
            <td>Name</td>
            <td><input required type="text" name="name" value="<?php echo htmlspecialchars($msg->name); ?>"></td>
        </tr>

        <tr>
            <td>Type</td>
            <td>
                <select name="type">
                    <option value="public" <?php if ($msg->type == "public") echo "selected"; ?>>Public</option>
                    <option value="private" <?php if ($msg->type == "private") echo "selected"; ?>>Private</option>
                </select>
            </td>
        </tr>

        <tr>
            <td>Message</td>
            <td><textarea required name="content"><?php echo htmlspecialchars($msg->message); ?></textarea></td>
        </tr>
    </table>

    <input type="submit" value="Save changes" name="save_message">
</form>

<hr>
<P>Navigation</P>
<?php Page::display_navigation(); ?>
</body>
</html>
