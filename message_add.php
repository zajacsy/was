<?php
include_once "session_handler.php";
include_once "classes/Page.php";
include_once "classes/Db.php";

Page::display_header("Add message");
$db = new Db("localhost", "root", "", "bezpieczenstwo");

if (isset($_POST['add_message'])) {
    $db->addMessage($_POST['name'], $_POST['type'], $_POST['content']);
    echo "<b>Message added!</b><br>";
}
?>

<hr>
<P>Add message</P>
<form method="post" action="">
    <table>
        <tr><td>Name</td><td><input required type="text" name="name" size="56" /></td></tr>

        <tr><td>Type</td>
            <td>
                <select name="type">
                    <option value="public">Public</option>
                    <option value="private">Private</option>
                </select>
            </td>
        </tr>

        <tr><td>Message content</td>
            <td><textarea required name="content" rows="10" cols="40"></textarea></td>
        </tr>
    </table>

    <input type="submit" value="Add message" name="add_message">
</form>

<hr>
<P>Navigation</P>
<?php Page::display_navigation(); ?>
</body>
</html>
