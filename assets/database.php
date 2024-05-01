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
            if($word[$i] instanceof JSONobj) continue;
            $base = $word[$i]->getBase();
            $class = $word[$i]->getClass();
            $wor = $word[$i]->getWord();
            if (Words::hasForms($word[$i]) > 0 && $word[$i]->getTable() != null && $word[$i]->getTable()->getValidity())
                $table = htmlentities($word[$i]->getTable()->table);
            $json = $word[$i]->toJSON();
            $sql = "INSERT IGNORE INTO words (base, class, word, json) VALUES ('$base', '$class', '$wor',  \"$json\")";
            if (isset($table))
                $tab = "INSERT IGNORE INTO tables (base, class, tables) VALUES ('$base', '$class', '$table');";
            $conn->query($sql);
            $conn->query($tab);
        }
    }
    public static function getWordDB($word)
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
}
