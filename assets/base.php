<?php
class Base
{
    public static function Parse($text, $lang, $word, $class = null, $translation = true)
    {
        $words = [];
        $word = trim($word);
        switch ($lang) {
            case "en":
                $i = 0;
                $bool = true;
                while ($bool && $i <= 1) {
                    $bases = array_values(array_filter($text, function ($val) use ($class, $i) {
                        $regex = "/^===(?:(?!=).)+===$/i";
                        if ($i == 1) $regex = "/^====(?:(?!=).)+====$/i";
                        if ($class != null)
                            $bool = preg_match("/" . ucfirst($class) . "/i", $val);
                        else {
                            $not = ["Pronunciation", "Anagrams", "Etymology===$", "Alternative forms", "References"];
                            foreach ($not as $n)
                                if (preg_match("/" . $n . "/i", $val) == 1)
                                    return false;
                            $bool = preg_match($regex, $val);
                        }
                        return $bool;
                    }));
                    $bool = preg_match("/Etymology [0-9]+/i", $bases[1]);
                    $i++;
                }
                $n = count($bases);
                $history = [];

                for ($i = 0; $i < $n; $i++) {
                    $l = array_count_values($history);
                    if (isset($l[$bases[$i]]))
                        $base = arrays::array_name_slice($text, $bases[$i], $l[$bases[$i]]);
                    else $base = arrays::array_name_slice($text, $bases[$i]);
                    $history[] = $bases[$i];
                    if (Wikitext::Derived($base)) continue;
                    $translate = [];
                    if ($translation)
                        $translate = Translate::Go($base, $lang);
                    switch (str_replace(["=", " "], "", $base[0])) {
                        case "Noun":
                            preg_match("/g=(?<letter>[a-z])/i", $base[1], $matches);
                            preg_match("/<\d.(?<letter>[a-z])/i", $base[1], $matchesDot);
                            $gender = strtolower($matches["letter"]);
                            $gender = strlen($gender) == 1 ? $gender : strtolower($matchesDot["letter"]);
                            if (strlen($gender) == 0 || !in_array($gender, ["m", "n", "f"])) {
                                $f = preg_match("/|m=/i", $base[1]);
                                $m = preg_match("/|f=/i", $base[1]);
                                if ($m && !$f)
                                    $gender = "m";
                                if (!$m && $f)
                                    $gender = "f";
                                if (!$m && !$f)
                                    $gender = "n";
                            }
                            if ($gender == "") $gender = null;
                            preg_match("/<(?<number>\d)/i", $base[1], $decl);
                            $decl = $decl["number"];
                            $sentence = new Noun(
                                $word,
                                $word,
                                "nom",
                                "s",
                                $gender,
                                $decl,
                                $translate
                            );
                            break;
                        case "Adjective":
                            $sentence = new Adjective(
                                $word,
                                $word,
                                "nom",
                                "s",
                                "m",
                                null,
                                $translate
                            );
                            break;
                        case "Pronoun":
                            if (str_starts_with($base[1], "head|la|pronoun")) {
                                preg_match("/cat2=(?<letter>[a-z]+)/i", $base[1], $matches);
                                $type = $matches["letter"];
                                preg_match("/\|(?<letter>[a-z]+) person/i", $base[1], $matches);
                                $person = $matches["letter"];
                            } else if (str_starts_with($base[1], "la-det")) {
                                preg_match("/cat3=(?<letter>[a-z]+)/i", $base[1], $matches);
                                $type = $matches["letter"];
                                $person = 3;
                            }
                            $sentence = new Pronoun(
                                $word,
                                $word,
                                "nom",
                                "s",
                                $type,
                                Czech::PersonToEn($person),
                                null,
                                $translate
                            );
                            break;
                        case "Numeral":
                            $sentence = new Numeral(
                                $word,
                                $word,
                                "nom",
                                "s",
                                "", //TODO
                                $translate
                            );
                            break;
                        case "Verb":
                            preg_match("/la-verb\|(?<number>\d)/i", $base[1], $conj);
                            $conj = $conj["number"];
                            $sentence = new Verb(
                                $word,
                                $word,
                                "s",
                                "pres",
                                1,
                                "act",
                                "indc",
                                $conj,
                                $translate
                            );
                            break;
                        case "Adverb":
                            $sentence = new Adverb(
                                $word,
                                $word,
                                $translate
                            );
                            break;
                        case "Preposition":
                            $sentence = new Preposition(
                                $word,
                                $word,
                                $base[1],
                                $translate
                            );
                            $sentence->ParseWith();
                            break;
                        case "Conjunction":
                            $sentence = new Connective($word, $word, $translate);
                            break;
                        default: //TODO možná spíše english
                            continue 2;
                    }
                    $table = new Table($sentence->getClass(), $sentence->getBase(), $lang);

                    if ($table->getValidity())
                        $sentence->setTable($table);
                    $words[] = $sentence;
                }

                return $words;
            case "cs":
                $bases = array_values(array_filter($text, function ($val) use ($class) {
                    $regex = "/^===(?:(?!=).)+===$/i";
                    if ($class != null) {
                        if (str_contains($val, "("))
                            $bool = preg_match("/ " . Czech::Class($class) . " \([0-9]+\) /i", $val);
                        else
                            $bool = preg_match("/ " . Czech::Class($class) . " /i", $val);
                    } else
                        $bool = preg_match($regex, $val);
                    return $bool;
                }));

                $n = count($bases);
                for ($i = 0; $i < $n; $i++) {
                    $base = arrays::array_name_slice($text, $bases[$i]);
                    $translate = [];
                    $preparse = explode("(", $bases[$i]);
                    if ($translation)
                        $translate = Translate::Go($base, $lang);
                    $base[0] = $preparse[0];
                    switch (str_replace(["=", " "], "", $base[0])) {
                        case "podstatnéjméno":
                            $gender = null;
                            $decl = null;
                            if (str_starts_with($base[1], "* ")) {
                                preg_match("/rod (?<letter>[a-z])/i", $base[1], $gender);
                                $gender = Czech::GenderToEn($gender["letter"]);
                            }
                            if (str_starts_with($base[2], "* ")) {
                                $decl = $base[2][2];
                            }
                            $sentence = new Noun(
                                $word,
                                $word,
                                "nom",
                                "s",
                                $gender,
                                $decl,
                                $translate
                            );
                            break;
                        case "přídavnéjméno": //TODO deklination
                            preg_match_all("/\d/i", $base[1], $matches);
                            $sentence = new Adjective(
                                $word,
                                $word,
                                "nom",
                                "s",
                                "m",
                                $matches[0],
                                $translate
                            );
                            break;
                        case "zájmeno":
                            $sentence = new Pronoun(
                                $word,
                                $word,
                                "nom",
                                "s",
                                Czech::TypeToEn(explode("''", $base[1], 3)[1]),
                                null,
                                explode("''", $base[2], 3)[1], //TODO english gender
                                $translate
                            );
                            break;
                        case "číslovka": //TODO možná spíše english
                            $sentence = new Numeral(
                                $word,
                                $word,
                                "nom",
                                "s",
                                "", //TODO
                                $translate
                            );
                            break;
                        case "sloveso":
                            $sentence = new Verb( //TODO konjugace
                                $word,
                                $word,
                                "s",
                                "pres",
                                0,
                                "inf",
                                "indc",
                                null,
                                $translate
                            );
                            break;
                        case "příslovce":
                            $sentence = new Adverb(
                                $word,
                                $word,
                                $translate
                            );
                            break;
                        case "předložka": //TODO předložky
                            $sentence = new Preposition(
                                $word,
                                $word,
                                $base[1],
                                $translate
                            );
                            $sentence->ParseWith();
                            break;
                        case "spojka":
                            $sentence = new Connective($word, $translate);
                            break;
                        default:
                            continue 2;
                    }
                    $table = new Table($sentence->getClass(), $sentence->getBase(), $lang);
                    if ($table->getValidity())
                        $sentence->setTable($table);
                    $words[] = $sentence;
                }
        }
        if($words == []) return false;
        return $words;
    }
}
class Inflections
{
    public static function Parse($info, $lang, $wordd, $class = "") //TODO numeral
    {
        switch ($lang) {
            case "en":
                if (str_contains($info, "#English|")) {
                    $translation = explode(";", $info, 2);
                    $info = $translation[1];
                    $translation = explode(", ", str_replace("#English|", "", $translation[0]));
                    $translation[0] = str_replace("# ", "", $translation[0]);
                    $translation = explode(";", API::deepL(implode(";", $translation)));
                }
                $info = str_replace(["# ", "inflection of|la|"], "", $info);
                if (str_contains($info, "//")) {
                    $info = explode("//", $info);
                    $n = count($info);
                    $inf = array();
                    $start = explode("|", $info[0]);
                    $inf[] = $start[count($start) - 1];
                    for ($i = 1; $i < $n - 1; $i++) {
                        $inf[] = $info[$i];
                    }
                    $end = explode("|", $info[$n - 1]);
                    $inf[] = $end[0];
                    unset($end[0]);
                    unset($start[count($start) - 1]);
                    $info = array_values($start);
                    $info[] = $inf;
                    $info = array_merge($info, array_values($end));
                } else $info = explode("|", $info);

                $word = "";

                $base = iconv('utf-8', 'ascii//TRANSLIT', $info[0]);
                switch ($class) {
                    case "pronoun":
                        $word = new Pronoun(
                            $wordd,
                            $base,
                            $info[2], //form
                            $info[3],
                            null,
                            null,
                            null,
                            $translation ?? null
                        );
                        break;
                    case "noun":
                        $word = new Noun(
                            $wordd,
                            $base,
                            $info[2], //form
                            $info[3],
                            null,
                            null,
                            $translation ?? null
                        );
                        break;
                    case "adjective":
                        $word = new Adjective(
                            $wordd,
                            $base,
                            $info[2],
                            $info[4],
                            $info[3],
                            null,
                            $translation ?? null
                        );
                        break;
                    case "verb":
                        if ($info[5] == "perf")
                            $word = new Verb(
                                $wordd,
                                $base,
                                $info[3], //number
                                "futrperf", //tense
                                (int) $info[2], //person
                                $info[6], //gender
                                $info[7],
                                null,
                                $translation ?? null
                            );
                        else
                        if (is_numeric($info[2])) {
                            $word = new Verb(
                                $wordd,
                                $base,
                                $info[3], //number
                                $info[4], //tense
                                (int) $info[2], //person
                                substr($info[5], 0, 3), //gender
                                substr($info[6], 0, 3),
                                null,
                                $translation ?? null
                            ); //mood
                        } else if ($info[2] == "pres") {
                            $word = new Verb(
                                $wordd,
                                $base,
                                null, //number
                                $info[2], //tense
                                0, //person
                                $info[3], //gender
                                $info[4],
                                null,
                                $translation ?? null
                            );
                        }
                        break;
                    case "numeral":
                        $word = new Numeral(
                            $wordd,
                            $base,
                            $info[2],
                            $info[4],
                            $info[3],
                            $translation ?? null
                        );
                        break;
                }
                return $word;
            case "cs":
                $info = str_replace(array("''", '# ', '\'\''), "", $info);
                $info = explode(" ", $info);
                $word = false;

                $n = count($info);
                if ($info[2] == "substantiva") {
                    $word = new Noun($wordd, explode("#", $info[3])[0], substr($info[0], 0, 3), $info[1][0]);
                } else if (isset($info[6]) && $info[6] == "slovesa") {
                    $word = new Verb(
                        $wordd,
                        explode("#", $info[7])[0],
                        $info[2][0],
                        Czech::TenseToEn(substr($info[4], 0, 4)),
                        Czech::Person($info[0]),
                        Czech::GenderToEn(substr($info[5], 0, 3)),
                        substr($info[3], 0, 3),
                    );
                    for ($i = 9; $i < $n; $i++)
                        $word->addTranslation($info[$i]);
                } else if ($info[3] == "adjektiva") {
                    $word = new Adjective(
                        $wordd,
                        explode("#", $info[4])[0],
                        substr($info[0], 0, 3),
                        $info[1][0],
                        $info[2][0]
                    );
                } else if ($info[1] == "zájmena") {
                    $word = new Pronoun(
                        $wordd,
                        explode("#", $info[2])[0],
                        substr($info[0], 0, 3),
                        "s",
                        null,
                        null
                    ); //form, number, type
                }
                return $word;
        }
    }
}
