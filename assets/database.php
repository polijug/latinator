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
    public static function insert($word){
        $conn = self::connect();
        $base = $word[0]->getBase();
        $class = $word[0]->getClass();
        $wor = $word[0]->getWord();
        $table = htmlentities($word[0]->getTable()->table);

        $n = count($word);
        $json = "[";
        for($i = 0; $i < $n; $i++){
            $word[$i] = $word[$i]->toJSON();
            $json .= $word[$i];
            if($i < $n -1) $json .= ", ";
        }
        $json .="]";

        $sql = "INSERT INTO words (base, class, word, tables, json) VALUES ('$base', '$class', '$wor', '$table',  \"$json\")";
        $conn->query($sql);
    }
    public static function getWordDB($word){
        $conn = self::connect();
        $sql = "SELECT * FROM words WHERE word = '$word'";

        $result = $conn->query($sql);
        if($result->num_rows == 0) return false;
        $out = [];
        while($res = $result->fetch_array(MYSQLI_ASSOC)){
            $out[] = Words::decodeJSON($res);
        }
        return $out;
    }
}