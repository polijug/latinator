<?php
class Formating
{
    public static function Formate($words)
    {
        return new Interpretation($words);
    }
}

class Interpretation
{
    public $word = [];
    public $format = [];
    public function __construct($word)
    {
        $n = count($word);
        for ($i = 0; $i < $n; $i++)
            if ($word[$i][0] instanceof JSONobj)
                $this->word[] = $word[$i];
            else {
                $this->word[] = $word[$i] ? Words::Merge($word[$i]) : false;
                if ($this->word[$i]){
                    Database::insert($this->word[$i]);
                    Database::insertValid($this->word[$i]);
                }
            }
        if ($n > 1)
            $this->word = Words::Pairable($this->word);
        $this->formatAnswer();
    }
    public function formatAnswer()
    {
        $str = [];
        $o = count($this->word);
        for ($i = 0; $i < $o; $i++) {
            for ($p = 0; $p < count($this->word[$i]); $p++) {
                $word = $this->word[$i][$p];
                if(isnull($word)){
                    continue;
                }
                $base = $word->getWord();
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
                                    if (!isnull($bold) && isset($bold[$word->getGender() . "_" . $number[$j]]) && in_array($word->getForm()[$number[$j]], $bold))
                                        $b = true;
                                    else $b = false;
                                    $arr[] = self::Class($word, ["form" => $word->getForm()[$number[$j]], "bold" => $b, "number" => $number[$j]]);
                                } else for ($k = 0; $k < count($word->getForm()[$number[$j]]); $k++) {
                                    if (!isnull($bold) && isset($bold[$word->getGender() . "_" . $number[$j]]) && in_array($word->getForm()[$number[$j]][$k], $bold[$word->getGender() . "_" . $number[$j]]))
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
                                    if (!isnull($bold) && isset($bold[$keys[$k]]) && in_array($word->getForm()[$keys[$k]][$j], $bold[$keys[$k]]))
                                        $b = true;
                                    else $b = false;
                                    $arr[] = self::Class($word, ["form" => $form[$j], "gender" => $keys[$k][0], "bold" => $b, "number" => $keys[$k][strlen($keys[$k]) - 1]]);
                                }
                            } else {
                                if (!isnull($bold) && isset($bold[$keys[$k]]) && in_array($word->getForm()[$keys[$k]], $bold[$keys[$k]]))
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
                    case "connective":
                    case "adverb":
                        $str[$base][] = self::Class($word);
                        break;
                    case "preposition":
                        if (is_array($word->with)) {
                            $arr = [];
                            $n = count($word->with);
                            for ($j = 0; $j < $n; $j++)
                                $arr[] = self::Class($word, ["with" => $word->with[$j]]);
                            $str[$base][] = $arr;
                        } else $str[$base][] = self::Class($word);
                        break;
                }
                $str[$base]["long_$p"] = self::Long($this->word[$i][$p]);
                $str[$base]["title_$p"] = self::Title($this->word[$i][$p]);
            }
        }
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
                        $b = !isnull($bold) && isset($bold[$word->getGender() . "_" . $word->getNumber()]) && in_array($word->getForm()[$word->getNumber()], $bold[$word->getGender() . "_" . $word->getNumber()]);
                    $variables = ["bold" => $b];
                }
                $gender = "";
                if (!isnull($word->getGender()) && !is_array($word->getGender()) && $word->getGender() != "")
                    $gender = " " . Czech::Gender($word->getGender()) . "a";
                if (isset($variables["gender"]) && !isnull($variables["gender"]) && $variables["gender"] != "")
                    $gender = " " . Czech::Gender($variables["gender"]) . "a";
                $boldE = $boldS = "";
                if ($variables["bold"]) {
                    $boldS = "<bold>";
                    $boldE = "</bold>";
                }
                $short = $boldS . Czech::Form(isset($variables["form"]) ? $variables["form"] : $word->getForm()[$word->getNumber()]) . " " . Czech::Number(isset($variables["number"]) ? $variables["number"] : $word->getNumber()) . "u" . $gender . $boldE;
                break;
            case "verb":
                if ($variables["person"] != "0") {
                    $person = is_array($variables["person"]) ? implode(". / ", $variables["person"]) . ". osoby " : $variables["person"] . ". osoba ";
                    $short = $person . "" . Czech::Number($variables != null ? $variables["number"] : $word->getNumber()) . "u " . Czech::Tense($word->getTense()) . " " . Czech::Gender($variables != null ? $variables["gender"] : $word->getGender()) . " " .
                        Czech::Mood($word->getMood());
                } else
                    $short = "infinitiv slovesa " . $base;
                break;
            case "adverb":
            case "connective":
                $short = "";
                break;
            case "preposition":
                $short = "s " . Czech::Form($variables != null ? $variables["with"] : $word->getWith()) . "em";
                break;
        }
        return $short;
    }
    private static function Title($word)
    {
        $translation = $word->getTranslation(0);

        return $word->getBase() . " ($translation) - " . Czech::Class($word->getClass()) . self::DeclConj($word);
    }

    private static function DeclConj($word)
    {
        $forms = Words::hasForms($word);
        if($forms == 0) return "";
        if ($forms == 1 && !isnull($word->getDeclination()))
            return ", " .  (is_array($word->getDeclination()) ? implode(". / ", $word->getDeclination()) . ". deklinace " : $word->getDeclination() . ". deklinace ");
        if ($forms == 2 && !isnull($word->getConjugation()))
            return ", " . $word->getConjugation() . ". konjugace";
    }

    private static function Long($word)
    { //and title
        $str = "<h4>Překlady</h4>";
        $translation = $word->getTranslation();
        $n = count($translation);
        for ($i = 0; $i < $n; $i++)
            $str .= "<li>" . str_trim($translation[$i]) . "</li>";
        $type = Words::hasForms($word);
        if ($type == 1) $type = "skloňování"; 
        if ($type == 2) $type = "časování";
        if (is_string($type) && $word->getTable() != null && $word->getTable()->getValidity()) {
            $str .= "<p> <h4>Tabulka $type</h4>";
            $str .= $word->getTable()->toBold($word->getWord());
        }
        $base = $word->getBase();
        $str .= "<small>Zdroj a další informace:<br><a target=_blank href=https://en.wiktionary.org/wiki/$base#Latin>Wiktionary ($base)</a><br>" .
            "<a target=_blank href=https://cs.wiktionary.org/wiki/$base#latina>Wikislovník ($base)</a></small>";
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
            $n = count($word) / 3; //because there are three - word, long and title
            for ($i = 0; $i < $n; $i++) {
                $short = "<bold>" . $word["title_$i"] . "</bold><br>";
                if (is_array($word[$i]))
                    for ($j = 0; $j < count($word[$i]); $j++)
                        $short .= $word[$i][$j] . "<br>";
                else $short .= $word[$i];
                $str = "<details><summary>" . $short . "</summary>";
                $str .= "<div>" . $word["long_$i"] . "</div></details>";
                $output[$keys[$k]][] = $str;
            }
        }
        return $output;
    }
}
