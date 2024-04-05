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
            $base = $word[$i]->getBase();
            $class = $word[$i]->getClass();
            $wor = $word[$i]->getWord();
            if (Words::hasForms($word[$i]) > 0)
                $table = htmlentities($word[$i]->getTable()->table);
            $json = $word[$i]->toJSON();
            $sql = "INSERT INTO words (base, class, word, json) VALUES ('$base', '$class', '$wor',  \"$json\")";
            $tab = "IF NOT EXISTS (SELECT * FROM tables WHERE base = '$base' AND class = '$class')
BEGIN
INSERT INTO tables (base, class, tables) VALUES ('$base', '$class', '$table');
END";
            $conn->query($sql);
            $conn->query($tab);
        }
    }
    public static function getWordDB($word)
    {
        $conn = self::connect();
        if(is_string($word))
        $sql = "SELECT * FROM words WHERE word = '$word'";
        else if(!is_null($word->getClass())) $sql = "SELECT * FROM words WHERE word = '". $word->getBase() ."' AND base = '" . $word->getBase() . "' AND class = '" . $word->getClass() . "'";
        else $sql = "SELECT * FROM words WHERE word = '". $word->getBase() ."' AND base = '" . $word->getBase() . "'";

        $result = $conn->query($sql);
        if ($result->num_rows == 0) return false;
        $out = [];
        while ($res = $result->fetch_array(MYSQLI_ASSOC)) {
            $act = Words::decodeJSON($res);
            $act->table  = $conn->query("SELECT tables FROM tables WHERE base = '".$act->getBase()."' AND class = '".$act->getClass()."'");
            $out[] = $act;
        }
        if($out == []) return false;
        return $out;
    }
}
