<?php
class Formating
{
    public $word = [];
    public $format = []; //string
    public function __construct($word)
    {
        //WORD DUPLICITY
        $n = count($word);
        for ($i = 0; $i < $n; $i++)
            if ($word[$i][0] instanceof JSONobj)
                $this->word[] = $word[$i];
            else {
                $this->word[] = Words::Merge($word[$i]);
                Database::insert($this->word[$i]);
            }
        $this->word = Words::Pairable($this->word);
    }
    public function formatAnswer()
    {
        $str = [];
        $o = count($this->word);

        //mlog($this->word, false);
        for ($i = 0; $i < $o; $i++) {
            $last = "";
            for ($p = 0; $p < count($this->word[$i]); $p++) { //for one word more alternatives
                $word = $this->word[$i][$p];

                //ONE ARRAY FOR EACH WORD

                //arrays - singular, plural, other - decide what has higher probability - classical more probable, hide not that used
                //if more decide which
                //NOT DECIDE
                $base = $word->getBase() . "_" . substr($word->getClass(), 0, 2);
                if (!isset($str[$base])) $str[$base] = [];
                switch ($word->getClass()) {
                    case "noun":
                    case "numeral":
                    case "pronoun":
                        $bold = $word->bold;
                        if (is_array($word->getNumber())) {
                            $arr = [];
                            $n = count($word->getNumber());
                            for ($j = 0; $j < $n; $j++)
                                if (!is_array($word->getForm()[$word->getNumber()[$j]])) {
                                    if (!is_null($bold) && isset($bold[$word->getGender() . "_" . $word->getNumber()[$j]]) && in_array($word->getForm()[$word->getNumber()[$j]], $bold))
                                        $b = true;
                                    else $b = false;
                                    $arr[] = Formating::Class($word, ["form" => $word->getForm()[$word->getNumber()[$j]], "bold" => $b, "number" => $word->getNumber()[$j]]);
                                } else for ($k = 0; $k < count($word->getForm()[$word->getNumber()[$j]]); $k++) {
                                    if (!is_null($bold) && isset($bold[$word->getGender() . "_" . $word->getNumber()[$j]]) && in_array($word->getForm()[$word->getNumber()[$j]][$k], $bold[$word->getGender() . "_" . $word->getNumber()[$j]]))
                                        $b = true;
                                    else $b = false;
                                    $arr[] = Formating::Class($word, ["form" => $word->getForm()[$word->getNumber()[$j]][$k], "bold" => $b, "number" => $word->getNumber()[$j]]);
                                }
                            $str[$base][] = $arr;
                        } else $str[$base][] = Formating::Class($word);
                        break;
                    case "adjective":
                        $bold = $word->bold;
                        $keys = array_keys($word->getForm());
                        $n = count($keys);
                        $arr = [];
                        for ($k = 0; $k < $n; $k++) {
                            $form = $word->getForm()[$keys[$k]];
                            if (is_array($form)) {
                                $m = count($form);
                                for ($j = 0; $j < $m; $j++) {
                                    if (!is_null($bold) && isset($bold[$keys[$k]]) && in_array($word->getForm()[$keys[$k]][$j], $bold[$keys[$k]]))
                                        $b = true;
                                    else $b = false;
                                    $arr[] = Formating::Class($word, ["form" => $form[$j], "gender" => $keys[$k][0], "bold" => $b, "number" => $keys[$k][strlen($keys[$k]) - 1]]);
                                }
                            } else {
                                if (!is_null($bold) && isset($bold[$keys[$k]]) && in_array($word->getForm()[$keys[$k]], $bold[$keys[$k]]))
                                    $b = true;
                                else $b = false;
                                $arr[] = Formating::Class($word, ["form" => $form, "gender" => $keys[$k][0], "bold" => $b, "number" => $keys[$k][strlen($keys[$k]) - 1]]);
                            }
                        }
                        $str[$base][] = $arr;
                        break;
                    case "verb":
                        //todo edit for actual regime
                        $keys = array_keys($word->getPerson());
                        $n = count($keys);
                        $arr = [];
                        for ($k = 0; $k < $n; $k++)
                            $arr[] = Formating::Class($word, ["person" => $word->getPerson()[$keys[$k]], "gender" => substr($keys[$k], 0, 3), "number" => $keys[$k][strlen($keys[$k]) - 1]]);
                        $str[$base][] = $arr;
                        break;
                    case "adverb":
                    case "connective":
                        $str[$base][] = Formating::Class($word);
                        break;
                    case "connective":
                        if (is_array($word->with)) {
                            $arr = [];
                            $n = count($word->with);
                            for ($j = 0; $j < $n; $j++)
                                $arr[] = Formating::Class($word, ["with" => $word->with[$j]]);
                            $str[$base][] = $arr;
                        } else $str[$base][] = Formating::Class($word);
                        break;
                }
                if ($base != $last)
                    $str[$base]["long"] = Formating::Long($this->word[$i][$p]);
                $last = $base;
            }
        }
        //mlog($str);
        //or there send
        $this->format = Formating::Build($str);
    }
    private static function Class($word, $variables = null)
    { //todo translation + variable elements - zvýraznit
        //mlog($variables);
        $base = "<b>" . $word->getBase() . "</b>";
        switch ($word->getClass()) {
            case "noun":
            case "adjective":
            case "numeral":
            case "pronoun":
                $gender = "";
                if (!is_null($word->getGender()) && !is_array($word->getGender()))
                    $gender = " " . Czech::Gender($word->getGender()) . "a";
                if (isset($variables["gender"]) && !is_null($variables["gender"]))
                    $gender = " " . Czech::Gender($variables["gender"]) . "a";
                if($variables["bold"]) {$boldS = "<bold>"; $boldE = "</bold>";}
                $short = $boldS . Czech::Form($variables != null ? $variables["form"] : $word->getForm()[$word->getNumber()]) . " " . Czech::Number($variables != null ? $variables["number"] : $word->getNumber()) . "u" . $gender . ", " . Czech::Class($word->getClass()) . " " . $base . $boldE;
                break;
            case "verb":
                //todo  // osoba, číslo, čas, rod, způsob? (mood),
                $person = "";
                if ($variables["person"] != "0")
                    $person = is_array($variables["person"]) ? implode(". / ") . ". osoby " : $variables["person"] . ". osoba ";
                $short = $person . "" . Czech::Number($variables != null ? $variables["number"] : $word->getNumber()) . "u " . Czech::Tense($word->getTense()) . " " . Czech::Gender($variables != null ? $variables["gender"] : $word->getGender()) . " " .
                    Czech::Mood($word->getMood()) . ", " . Czech::Class($word->getClass()) . " " . $base;
                break;
            case "adverb":
            case "connective":
                $short = Czech::Class($word->getClass()) . " " . $base;
                break;
            case "preposition":
                $short = Czech::Class($word->getClass()) . " s " . Czech::Form($variables != null ? $variables["with"] : $word->getWith()) . "em, " . $base;
                break;
        }
        return $short;
    }
    private static function Long($word)
    {
        $str = "<h4>Překlady</h4>"; //really bad
        $translation = $word->getTranslation();
        $n = count($translation);
        for ($i = 0; $i < $n; $i++)
            $str .= "<li>" . $translation[$i] . "</li>";
        $type = Words::hasForms($word);
        if ($type == 1) $type = "skloňování";
        if ($type == 2) $type = "časování";
        if (is_string($type) && $word->getTable()->getValidity()) {
            $str .= "<p> <h4>Tabulka $type</h4>";
            $str .= $word->getTable()->table;
        }
        return $str;
    }
    private static function Build($str)
    {
        $output = "";
        foreach ($str as $word) {
            $n = count($word) - 1;
            for ($i = 0; $i < $n; $i++) {
                $short = "";
                if (is_array($word[$i]))
                    for ($j = 0; $j < count($word[$i]); $j++)
                        $short .= $word[$i][$j] . "<br>";
                if ($short == "") $short = $word[$i];
                //for each array - make
                //----
                $output .= "<details><summary>" . $short . "</summary>";
                $output .= "<div>" . $word["long"] . "</div></details>";
            }
        }
        return $output;
    }
}
