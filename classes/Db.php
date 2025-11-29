<?php
require_once "Filter.php";

class Db
{
    private PDO $pdo;

    public function __construct($host, $user, $pass, $dbname)
    {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";

        // Ignorujemy parametry i narzucamy konto aplikacji
        $user = "root";
        $pass = "";

        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
        ]);
    }

    /* --------------------- MESSAGE FUNCTIONS --------------------- */

    public function getAllMessages()
    {
        $stmt = $this->pdo->query("SELECT * FROM message WHERE deleted = 0");
        return $stmt->fetchAll();
    }

    public function getMessageById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM message WHERE id = ?");
        $stmt->execute([intval($id)]);
        return $stmt->fetch();
    }

    public function addMessage($name, $type, $content)
    {
        $name = Filter::filterName($name);
        $type = Filter::filterType($type);
        $content = Filter::filterMessage($content);

        $stmt = $this->pdo->prepare("
            INSERT INTO message (name, type, message, deleted)
            VALUES (?, ?, ?, 0)
        ");

        return $stmt->execute([$name, $type, $content]);
    }

    public function updateMessage($id, $name, $type, $content)
    {
        $id = intval($id);
        $name = Filter::filterName($name);
        $type = Filter::filterType($type);
        $content = Filter::filterMessage($content);

        $stmt = $this->pdo->prepare("
            UPDATE message 
            SET name = ?, type = ?, message = ?
            WHERE id = ?
        ");

        return $stmt->execute([$name, $type, $content, $id]);
    }

    public function deleteMessage($id)
    {
        $id = intval($id);
        $stmt = $this->pdo->prepare("UPDATE message SET deleted = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /* ----------------------- USER FUNCTIONS ----------------------- */

    public function getUserByLoginAndPassword($login, $password)
    {
        $login = Filter::filterName($login);

        $stmt = $this->pdo->prepare("
            SELECT * FROM user WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if (!$user || !$user->salt || !$user->hash) return false;

        $checkhash = hash('sha512', $password . $user->salt);

        return ($checkhash === $user->hash) ? $user : false;
    }

    public function createUser($login, $password, $privilleges = 'USER')
    {
        $login = Filter::filterName($login);
        $privilleges = strtoupper($privilleges);
        $salt = bin2hex(random_bytes(16));

        $hash = hash('sha512', $password . $salt);

        $stmt = $this->pdo->prepare("
            INSERT INTO user (login, hash, salt, privilleges)
            VALUES (?, ?, ?, ?)
        ");

        return $stmt->execute([$login, $hash, $salt, $privilleges]);
    }

    public function changePassword($uid, $newPassword) {
        $uid = intval($uid);
        $salt = bin2hex(random_bytes(16));
        $hash = hash('sha512', $newPassword . $salt);

        $stmt = $this->pdo->prepare("
            UPDATE user SET hash = ?, salt = ? WHERE id = ?
            ");
        return $stmt->execute([$hash, $salt, $uid]);
    }


    /* ----------------------- LOG FUNCTIONS ----------------------- */

    public function getLogs($uid = null)
    {
        if ($uid) {
            $stmt = $this->pdo->prepare("SELECT * FROM log WHERE id_user = ?");
            $stmt->execute([intval($uid)]);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM log");
        }

        return $stmt->fetchAll();
    }

    public function addLog($uid, $text)
    {
        $uid = intval($uid);
        $text = Filter::cleanString($text);

        $stmt = $this->pdo->prepare("
            INSERT INTO log (time, communicate, id_user)
            VALUES (NOW(), ?, ?)
        ");

        return $stmt->execute([$text, $uid]);
    }


// Pobranie użytkownika po ID
public function getUserById($id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM user WHERE id = ?
            ");
        $stmt->execute([intval($id)]);
        return $stmt->fetch();
    }

    public function searchMessages($text) {
        $text = '%' . $text . '%';
        $stmt = $this->pdo->prepare(" 
            SELECT * FROM message WHERE deleted = 0 AND message LIKE ? 
            ");
        $stmt->execute([$text]);
        return $stmt->fetchAll();
    }
}
?>
