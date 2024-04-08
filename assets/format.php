<?php
class Formating
{
    public static function Formate($words)
    {
        $int = new Interpretation($words);
        return $int;
    }
}

class Interpretation
{
    public $word = [];
    public $format = []; //string
    public function __construct($word)
    {
        $n = count($word);
        for ($i = 0; $i < $n; $i++)
            if ($word[$i][0] instanceof JSONobj)
                $this->word[] = $word[$i];
            else {
                $this->word[] = Words::Merge($word[$i]);
                Database::insert($this->word[$i]);
            }
        if ($n > 1)
            $this->word = Words::Pairable($this->word);
        mlog($this->word);
        mlog("interpretation_28");
        $this->formatAnswer();
    }
    public function formatAnswer()
    {
        $str = [];
        $o = count($this->word);
        for ($i = 0; $i < $o; $i++) {
            $last = "";
            for ($p = 0; $p < count($this->word[$i]); $p++) { //for one word more alternatives
                $word = $this->word[$i][$p];
                mlog($word);
                mlog($p . "_formatAnswer_40");
                $base = $word->getWord();
                $TBase = $word->getBase();
                if (!isset($str[$base])) $str[$base] = [];
                switch ($word->getClass()) {
                    case "noun":
                    case "numeral":
                    case "pronoun":
                        $bold = $word->getBold();
                        if (is_array($word->getNumber()) || is_array($word->getForm()[$word->getNumber()])) { //chybná podmínka -> array do nearrayového
                            $arr = [];
                            $number = is_array($word->getNumber()) ? $word->getNumber() : [$word->getNumber()];
                            $n = count($number);
                            for ($j = 0; $j < $n; $j++)
                                if (!is_array($word->getForm()[$number[$j]])) {
                                    if (!is_null($bold) && isset($bold[$word->getGender() . "_" . $number[$j]]) && in_array($word->getForm()[$number[$j]], $bold))
                                        $b = true;
                                    else $b = false;
                                    $arr[] = self::Class($word, ["form" => $word->getForm()[$number[$j]], "bold" => $b, "number" => $number[$j]]);
                                } else for ($k = 0; $k < count($word->getForm()[$number[$j]]); $k++) {
                                    if (!is_null($bold) && isset($bold[$word->getGender() . "_" . $number[$j]]) && in_array($word->getForm()[$number[$j]][$k], $bold[$word->getGender() . "_" . $number[$j]]))
                                        $b = true;
                                    else $b = false;

                                    $arr[] = self::Class($word, ["form" => $word->getForm()[$number[$j]][$k], "bold" => $b, "number" => $number[$j]]);
                                }
                            $str[$base][] = $arr;
                        } else
                            $str[$base][] = self::Class($word);
                        break;
                    case "adjective":
                        $bold = $word->getBold();
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
                                    $arr[] = self::Class($word, ["form" => $form[$j], "gender" => $keys[$k][0], "bold" => $b, "number" => $keys[$k][strlen($keys[$k]) - 1]]);
                                }
                            } else {
                                if (!is_null($bold) && isset($bold[$keys[$k]]) && in_array($word->getForm()[$keys[$k]], $bold[$keys[$k]]))
                                    $b = true;
                                else $b = false;
                                $arr[] = self::Class($word, ["form" => $form, "gender" => $keys[$k][0], "bold" => $b, "number" => $keys[$k][strlen($keys[$k]) - 1]]);
                            }
                        }
                        $str[$base][] = $arr;
                        break;
                    case "verb":
                        $keys = array_keys($word->getPerson());
                        $n = count($keys);
                        $arr = [];
                        for ($k = 0; $k < $n; $k++)
                            $arr[] = self::Class($word, ["person" => $word->getPerson()[$keys[$k]], "gender" => substr($keys[$k], 0, 3), "number" => $keys[$k][strlen($keys[$k]) - 1]]);
                        $str[$base][] = $arr;
                        break;
                    case "adverb":
                        $str[$base][] = self::Class($word);
                        break;
                    case "connective":
                        if (is_array($word->with)) {
                            $arr = [];
                            $n = count($word->with);
                            for ($j = 0; $j < $n; $j++)
                                $arr[] = self::Class($word, ["with" => $word->with[$j]]);
                            $str[$base][] = $arr;
                        } else $str[$base][] = self::Class($word);
                        break;
                }
                if ($TBase != $last)
                    $str[$base]["long_$p"] = self::Long($this->word[$i][$p]);
                $last = $TBase;
            }
        }
        mlog($str);
        $this->format = self::Build($str);
    }
    private static function Class($word, $variables = null)
    { 
        $base = "<b>" . $word->getBase() . "</b>";
        switch ($word->getClass()) {
            case "noun":
            case "adjective":
            case "numeral":
            case "pronoun":
                if ($variables == null) {
                    $bold = $word->getBold();
                    $word->getGender() . "_" .
                        $b = !is_null($bold) && isset($bold[$word->getGender() . "_" . $word->getNumber()]) && in_array($word->getForm()[$word->getNumber()], $bold[$word->getGender() . "_" . $word->getNumber()]);
                    $variables = ["bold" => $b];
                }
                $gender = "";
                if (!is_null($word->getGender()) && !is_array($word->getGender()) && $word->getGender() != "")
                    $gender = " " . Czech::Gender($word->getGender()) . "a";
                if (isset($variables["gender"]) && !is_null($variables["gender"]) && $variables["gender"] != "")
                    $gender = " " . Czech::Gender($variables["gender"]) . "a";
                $boldE = $boldS = "";
                if ($variables["bold"]) {
                    $boldS = "<bold>";
                    $boldE = "</bold>";
                }
                $short = $boldS . Czech::Form(isset($variables["form"]) ? $variables["form"] : $word->getForm()[$word->getNumber()]) . " " . Czech::Number(isset($variables["number"]) ? $variables["number"] : $word->getNumber()) . "u" . $gender . ", " . Czech::Class($word->getClass()) . " " . $base . $boldE;
                break;
            case "verb":
                $person = "";
                if ($variables["person"] != "0") {
                    $person = is_array($variables["person"]) ? implode(". / ", $variables["person"]) . ". osoby " : $variables["person"] . ". osoba ";
                    $short = $person . "" . Czech::Number($variables != null ? $variables["number"] : $word->getNumber()) . "u " . Czech::Tense($word->getTense()) . " " . Czech::Gender($variables != null ? $variables["gender"] : $word->getGender()) . " " .
                        Czech::Mood($word->getMood()) . ", " . Czech::Class($word->getClass()) . " " . $base;
                } else
                    $short = "infinitiv slovesa " . $base;
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
        $str = "<h4>Překlady</h4>";
        $translation = $word->getTranslation();
        $n = count($translation);
        for ($i = 0; $i < $n; $i++)
            $str .= "<li>" . $translation[$i] . "</li>";
        $type = Words::hasForms($word);
        if ($type == 1) $type = "skloňování";
        if ($type == 2) $type = "časování";
        if (is_string($type) && $word->getTable() != null && $word->getTable()->getValidity()) {
            $str .= "<p> <h4>Tabulka $type</h4>";
            $str .= $word->getTable()->table;
        }
        return $str;
    }
    private static function Build($words)
    {
        $output = [];
        $keys = array_keys($words);
        $o = count($keys);
        for ($k = 0; $k < $o; $k++) {
            $word = $words[$keys[$k]];
            $output[$keys[$k]] = [];
            $n = count($word)/2;
            for ($i = 0; $i < $n; $i++) {
                $short = "";
                if (is_array($word[$i]))
                    for ($j = 0; $j < count($word[$i]); $j++)
                        $short .= $word[$i][$j] . "<br>";
                if ($short == "") $short = $word[$i];
                $str = "<details><summary>" . $short . "</summary>";
                $str .= "<div>" . $word["long_$i"] . "</div></details>";
                $output[$keys[$k]][] = $str;
            }
        }
        return $output;
    }
}
