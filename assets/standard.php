<?php
class arrays
{
    public static function remove_null($array){
        $keys = array_keys($array);
        if(!is_string($keys[0])) {
            for ($i = 0; $i < count($array); $i++) {
                if ($array[$i] == null) {
                    unset($array[$i]);
                }
            }
            return array_values($array);
        } else{
            for ($i = 0; $i < count($keys); $i++) {
                if(is_array($array[$keys[$i]]))
                    $array[$keys[$i]] = self::remove_null($array[$keys[$i]]);
                if($array[$keys[$i]] == null)
                    unset($array[$keys[$i]]);
                if($keys[$i] == "")
                    unset($array[$keys[$i]]);
            }
            return $array;
        }
    }
    static function array_name_slice($array, $startname, $avoid = 0)
    {
        $Sregex = preg_quote("/" . $startname . "/i");
        $index = self::array_find_first_occurence($array, $Sregex, "/^sjhalkfjdhsalkj$/i", $avoid);

        $fromEnd = substr_count($array[$index], "=", 0, 5);
        array_splice($array, 0, $index);
        switch ($fromEnd) {
            case 2:
                $regex = "/^==(?:(?!=).)+==$/i";
                break;
            case 3:
                $regex = "/^===(?:(?!=).)+===$/i";
                break;
            case 4:
                $regex = "/^====(?:(?!=).)+====$/i";
                break;
            case 5:
                $regex = "/^=====(?:(?!=).)+=====$/i";
                break;
        }
        array_splice($array, self::array_find_first_occurence($array, $regex, $Sregex));
        return $array;
    }

    static function array_find_first_occurence($array, $regex, $not = "/^ljkasdůflkjkjknbkcjkss$/i", $count = 0)
    {
        $n = count($array);
        $j = 0;
        for ($i = 0; $i < $n; $i++)
            if (preg_match($regex, $array[$i]) && !preg_match($not, $array[$i])) {
                if ($j == $count)
                    return $i;
                if ($count != 0)
                    $j++;
            }

        return $n;
    }
}
