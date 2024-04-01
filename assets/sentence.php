<?php
class Sentence
{
    public $sentence;
    public $words = [];
    public $count;
    public $format;
    public $rank;

    public function __construct($sentence)
    {
        $sentence = self::Analysis($sentence);
        $this->sentence = $sentence;
        $this->count = count($sentence);
        for ($i = 0; $i < $this->count; $i++) {
            $database = Database::getWordDB($sentence[$i]);
            if ($database !== false) {
                array_push($this->words, $database);
                continue;
            }
            $words = WikiText::auto($sentence[$i], "cs");
            $words = Words::Combine($words, WikiText::auto($sentence[$i], "en"));
            array_push($this->words, $words);
        }
    }
    public function Formate()
    {
        $format = Formating::Formate($this->words);
        $this->format = $format->format;
        $this->words = $format->word;
        $this->Decide();
        $this->Print();
    }
    private static function Analysis($sentence)
    {
        $sentence = explode(" ", lcfirst(trim($sentence)));
        return $sentence;
    }
    private function Print()
    {
    }
    private function Decide()
    {
        $output = [];
        $n = count($this->words);
        $firstPerson = -1; //0 noun; 1,2,3 pronoun, 4 empty pronoun
        $firstNumber = "";
        for ($i = 0; $i < $n; $i++) {
            $m = count($this->words[$i]);
            $end = false;
            $candidate = "";
            $obey = false;
            $shape = $this->words[$i][0]->getWord();
            for ($j = 0; $j < $m && !$end; $j++) {
                $word = $this->words[$i][$j];
                switch ($word->getClass()) {
                        //if nothing set in the end, have list of the best and return to the $j position and with bool force set that
                    case "noun":
                        $val = 3;
                    case "adjective":
                        $val = $val ?? 1;
                    case "numeral":
                        $val = $val ?? 3;
                    case "pronoun":
                        $val = $val ?? 4;
                        if ($candidate == "" ||  explode("_", $candidate)[0] < $val) //podmínka, ale jaká? nebo array? no ale pak bych musel rozhodovat o tom, co vybrat
                            $candidate = $val . "_" . $j;
                        if ($i == 0 && $word->getClass() == "noun" || $word->getClass() == "pronoun") { //chyba s číslem slovesa
                            $keys = array_keys($word->getForm());
                            $o = count($keys);
                            for ($k = 0; $k < $o && !$end; $k++) {
                                if (Words::formIntersection($word->getForm()[$keys[$k]], "nom")[0] == "nom") {
                                    $shortW = new Noun(
                                        $word->getWord(),
                                        $word->getBase(),
                                        "nom",
                                        $keys[$k],
                                        $word->getGender(),
                                        $word->getDeclination(),
                                        $word->getTranslation()
                                    );
                                    $shortW->class = $word->getClass();
                                    $long = $this->format[$shape][$j];
                                    $end = true;
                                    if ($word->getClass() == "pronoun") {
                                        $firstPerson = $word->getPerson() != null ? $word->getPerson() : 4;
                                    }
                                    $firstPerson = $word->getClass() == "noun" ? 0 : $firstPerson;
                                    $firstNumber = $word->getNumber();
                                    unset($this->format[$shape][$j]);
                                }
                            }
                        }
                        if (!$end) {
                            if ($word->getBold() != null) {
                                $key = array_keys($word->getBold())[0];
                                $shortW = new Noun(
                                    $word->getWord(),
                                    $word->getBase(),
                                    $word->getBold()[$key][0],
                                    $key[strlen($key) - 1],
                                    $key[0],
                                    $word->getDeclination(),
                                    $word->getTranslation()
                                );
                                $shortW->class = $word->getClass();
                                $long = $this->format[$shape][$j];
                                unset($this->format[$shape][$j]);
                                $end = true;
                            } else {
                                $keys = $word->getForm();
                                $o = count($keys);
                                for ($k = 0; $k < $o && !$end; $k++) {
                                    $arr = Words::formIntersection($word->getForm[$keys[$k]], ["nom", "acc"]);
                                    if ($obey || in_array("acc", $arr) || in_array("nom", $arr)) { //možná špatné pořadí
                                        $form = in_array("acc", $arr) ? "acc" : "nom";
                                        if ($obey)
                                            $form = $word->getForm[$keys[$k]][0];
                                        if (is_array($word->getGender())) $gender = $keys[$k][0];
                                        else $gender = $word->getGender();
                                        $shortW = new Noun(
                                            $word->getWord(),
                                            $word->getBase(),
                                            $form,
                                            $keys[$k],
                                            $gender,
                                            $word->getDeclination(),
                                            $word->getTranslation()
                                        );
                                        $shortW->class = $word->getClass();
                                        $long = $this->format[$shape][$j];
                                        unset($this->format[$shape][$j]);
                                        $end = true;
                                    }
                                }
                            }
                        }
                        break;
                    case "verb":
                        if ($candidate == "" || explode("_", $candidate)[0] < 2) //podmínka, ale jaká?
                            $candidate = 2 . "_" . $j;
                        if ($i == $n - 1 && $firstPerson != -1) {
                            $keys = array_keys($word->getPerson());
                            $o = count($keys);
                            for ($k = 0; $k < $o; $k++) {
                                $gender = substr($keys[$k], 0, 3);
                                $person = $word->getPerson()[$keys[$k]];
                                if ($firstNumber == $keys[$k][4] && ($firstPerson == 0 && Words::formIntersection($person, 3)[0] == 3 || $firstPerson > 0 && (Words::formIntersection($person, $firstPerson) != [] || $firstPerson == 4))) {
                                    if ($firstPerson == 0)
                                        $pers = 3;
                                    else if ($firstPerson == 4) $pers = Words::formIntersection($person, [1, 2, 3])[0];
                                    else $pers = Words::formIntersection($person, $firstPerson)[0];

                                    $shortW = new Verb(
                                        $word->getWord(),
                                        $word->getBase(),
                                        $firstNumber,
                                        $word->getTense(),
                                        $pers,
                                        $gender,
                                        $word->getMood(),
                                        $word->getConjugation(),
                                        $word->getTranslation()
                                    );
                                    $long = $this->format[$shape][$j];
                                    unset($this->format[$shape][$j]);
                                    $end = true;
                                }
                            }
                        }
                        if (!$end) { //act   ind/inf     pres/impf/futr  3.
                            $mood = Words::formIntersection($word->getMood(), ["ind", "inf"]);
                            $tense = Words::formIntersection($word->getTense(), ["pres", "impf", "futr"]);
                            if ($obey || Words::formIntersection($word->getGender(), "act")[0] == "act") {  //možná moc přísné
                                if ($mood != [] || $obey)
                                    if ($tense != [] || $obey) {
                                        $number = is_array($word->getNumber()) ? $word->getNumber()[0] : $word->getNumber();
                                        $person = $word->getPerson()["act_$number"];
                                        $person = is_array($person) ? $person[0] : $person;
                                        $gender = "act";
                                        if ($obey) {
                                            $tense = [$word->getTense()];
                                            $mood = [$word->getMood()];
                                            $key = array_keys($word->getPerson())[0];
                                            $person = $word->getPerson()[$key];
                                            $gender = substr($key, 0, 3);
                                        }
                                        $shortW = new Verb(
                                            $word->getWord(),
                                            $word->getBase(),
                                            $number,
                                            $tense[0],
                                            $person,
                                            $gender,
                                            $mood[0],
                                            $word->getConjugation(),
                                            $word->getTranslation()
                                        );
                                        mlog($this->format[$shape]);
                                        $long = $this->format[$shape][$j];
                                        unset($this->format[$shape][$j]);
                                        $end = true;
                                    }

                                //ind/inf - co když na konci se nic takového nenajde? (tady se nebavíme o konci) - tak to má blbý, tohle je překlad vět, ne slov.
                            } 
                        }
                        break;
                    case "preposition":
                        $with = $word->getWith();
                        $bold = $word->getBold();
                        if ($bold != null) {
                            $keys = array_keys($bold)[0];
                            $shortW = new Preposition($word->getWord(), $word->getBase(), $bold[$keys], $word->getTranslation());
                            $long = $this->format[$shape][$j];
                            $end = true;
                            unset($this->format[$shape][$j]);
                        } else if ($obey) {
                            $with = $this->getWith();
                            $with = is_array($with) ? $with[0] : $with;
                            $shortW = new Preposition($word->getWord(), $word->getBase(), $with, $word->getTranslation());
                            $long = $this->format[$shape][$j];
                            $end = true;
                            unset($this->format[$shape][$j]);
                        }
                        break;
                    default:
                        if ($candidate == "")
                            $candidate = "0_" . $j;
                        if ($obey) {
                            $shortW = new Connective($word->getBase(), $word->getTranslation());
                            $shortW->class = $word->getClass();
                            $end = true;
                            $long = $this->format[$shape][$j];
                            unset($this->format[$shape][$j]);
                        }
                        break;
                }
                if ($j == $m - 1 && !$end) {
                    $j = explode("_", $candidate)[1];
                    $obey = true;
                }            //if nothing set in the end, have list of the best and return to the $j position and with bool force set that

            }
            $short = false;
            if ($end && isset($shortW)) {
                $short = self::FormateShort($shortW);
                $output[$i] = ["short" => $short, "long" => $long, "other" => $this->format[$shape]];
            }
        }
        $this->format = $output;
    }
    private static function FormateShort($word)
    {
        switch ($word->getClass()) {
            case "noun":
            case "adjective":
            case "numeral":
            case "pronoun":
                $form = $word->getForm();
                $form = Short::Form($form[array_keys($form)[0]]);
                $str = $word->getTranslation()[0] . " - $form. pád čísla " . Short::Number($word->getNumber())
                    . "ho, rod " . Short::Gender_N($word->getGender()) . ", " . Czech::Class($word->getClass());
                $tooltip = "$form. p., č. " . Short::Number($word->getNumber(), true) . "., rod " . Short::Gender_N($word->getGender(), true) . "., " .
                    Czech::Class($word->getClass());
                break;
            case "verb":
                $person = $word->getPerson();
                $person = $person[array_keys($person)[0]];
                $str = $word->getTranslation()[0] . " - $person. osoba čísla " . Short::Number($word->getNumber()) . "ho, čas " . Short::Tense($word->getTense()) .
                    ", způsob " . Short::Mood($word->getMood()) . ", rod " . Short::Gender_V($word->getGender()) . ", " . Czech::Class($word->getClass());
                $tooltip = "$person. os., č. " . Short::Number($word->getNumber(), true) . "., čas " . Short::Tense($word->getTense(), true) .
                    ", zp. " . substr(Short::Mood($word->getMood()), 0, 3) . "., rod " . Short::Gender_V($word->getGender(), true) . ", " . Czech::Class($word->getClass());
                break;
            case "preposition":
                $str = $word->getTranslation()[0] . " - s " . Short::Form($word->form) . ". pádem, " . Czech::Class($word->getClass());
                $tooltip = "s " . Short::Form($word->form) . ". p., " . Czech::Class($word->getClass());
                break;
            default:
                $str = $word->getTranslation()[0] . " - " . Czech::Class($word->getClass());
                $tooltip = Czech::Class($word->getClass());
                break;
        }
        return [$str, $tooltip];
    }
    /*private function Struct()
    { //decide when formatting
        $rank = array();
        $n = count($this->words);
        for ($i = 0; $i < $n; $i++) {
            $rank[$i] = array();
            $word = $this->words[$i];
            $m = count($word);
            for ($j = 0; $j < $m; $j++) {
                if ($word[$j]->getClass() == "noun" && $i == 0)
                    $rank[$i][$j] = self::RankWordinStruct($word[$j], "s"); //rank inflections ---
                else if ($word[$j]->getClass() == "verb" && $i == $n - 1)
                    $rank[$i][$j] = self::RankWordinStruct($word[$j], "e");
                else $rank[$i][$j] = 0;
            }
        }
        $this->rank = $rank;
    }
    /*private static function RankWordinStruct($word, $position)
    { //(s/c/e)
        $rankW = array();
        $class = $word->getClass();
        if ($class == "noun" && $position == "s") { //start
            $form = $word->getForm();
            if (isset($form["s"]) && Words::formIntersection($form["s"], "nom")[0] == "nom")
                $rankW["s"] = ["nom" => 1];
            if (isset($form["p"]) && Words::formIntersection($form["p"], "nom")[0] == "nom")
                $rankW["p"] = ["nom" => 1];
        } else if ($class == "verb" && $position == "e") {
            $keys = array_keys($word->getPerson());
            $n = count($keys);
            for ($i = 0; $i < $n; $i++) {
                $person = $word->getPerson()[$keys[$i]];
                if (Words::formIntersection($person, 3) == 3)
                    $rankW[$keys[$i]] = [$person => 1];
            }
        }
        return $rankW;
    }*/
}
