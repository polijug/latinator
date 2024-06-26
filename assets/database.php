<?php

class Database
{
    static private $servername = "localhost";
    private static $username = "latinator";
    private static $table = "latinator";
    private static $password = "QwMVT59PMN86cQ2";

    public static function connect()
    {
        $conn = new mysqli(self::$servername, self::$username, self::$password, self::$table);
        if ($conn->connect_error) {
            print("<p style='color: red'>Chyba 503, chyba připojení k databázi: " . $conn->connect_error . "</b>");
        }
        return $conn;
    }
    public static function insert($word)
    {
        $conn = self::connect();
        $n = count($word);
        for ($i = 0; $i < $n; $i++) {
            if ($word[$i] instanceof JSONobj) continue;
            $base = $word[$i]->getBase();
            $class = $word[$i]->getClass();
            $wor = $word[$i]->getWord();
            $json = $word[$i]->toJSON();
            $sql = "INSERT IGNORE INTO words (base, class, word, json) VALUES ('$base', '$class', '$wor',  \"$json\")";
            $conn->query($sql);
        }
    }

    public static function insertTable($str, $class, $base)
    {
        if(strlen($str) == 0) return;
        $str = htmlentities($str);
        $conn = self::connect();
        $tab = "INSERT IGNORE INTO tables (base, class, tables) VALUES ('$base', '$class', '$str');";
        $conn->query($tab);
    }

    public static function valid($word){
        $conn = self::connect();
        $sql = "SELECT * FROM valid WHERE word = '" . $word . "'";
        $result = $conn->query($sql);
        if($result->num_rows > 0) return true;
        return false;
    }

    public static function insertValid($word)
    {
        $conn = self::connect();
        if(is_array($word))
            $wor = $word[0]->getWord();
        else $wor = $word->getWord();
        $sql = "INSERT IGNORE INTO valid (word) VALUES ('$wor')";
        $conn->query($sql);
    }

    public static function getWordDB($word, $base = false)
    {
        $conn = self::connect();
        if (is_string($word))
            $sql = "SELECT * FROM words WHERE word = '$word'";
        else if (!isnull($word->getClass())) $sql = "SELECT * FROM words WHERE word = '" . $word->getBase() . "' AND base = '" . $word->getBase() . "' AND class = '" . $word->getClass() . "'";
        else $sql = "SELECT * FROM words WHERE word = '" . $word->getBase() . "' AND base = '" . $word->getBase() . "'";

        $result = $conn->query($sql);
        if ($result->num_rows == 0) return false;
        $out = [];
        while ($res = $result->fetch_array(MYSQLI_ASSOC)) {
            $act = Words::decodeJSON($res);
            $table  = $conn->query("SELECT tables FROM tables WHERE base = '" . $act->getBase() . "' AND class = '" . $act->getClass() . "'")->fetch_row();
            $act->table = html_entity_decode($table[0]);
            $out[] = $act;
        }
        if ($out == []) return false;
        return $out;
    }

    public static function randomWord()
    {
        $conn = self::connect();
        $sql = "SELECT word FROM words ORDER BY RAND() LIMIT 3";
        $result = $conn->query($sql)->fetch_all();
        return ($result);
    }
}
