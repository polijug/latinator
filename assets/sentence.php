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
        $this->format = $format;
        mlog($this->format);
    }
    private static function Analysis($sentence)
    {
        $sentence = explode(" ", lcfirst(trim($sentence)));
        return $sentence;
    }
    private function Decide()
    {
        $n = count($this->words);
        for ($i = 0; $i < $n; $i++) {
            $m = count($this->words[$i]);
            $end= false;
            for ($j = 0; $j < $m && !$end; $j++) {
                $word = $this->words[$i][$j];
                switch ($word->getClass()) {
                    case "noun":
                        if($i == 0){
                            $end = true;
                        }
                        break;
                    case "adjective":
                    case "numeral":
                    case "pronoun":

                        break;
                    case "verb":
                        break;
                    case "preposition":
                        break;
                    default:
                        break;
                }
            }
        }
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
                $tooltip = "$form. p., č. " . Short::Number($word->getNumber())[0] . ", rod " . Short::Gender_N($word->getGender())[0] . "., " .
                    Czech::Class($word->getClass());
                break;
            case "verb":
                $person = $word->getPerson();
                $person = $person[array_keys($person)[0]];
                //Být - 3. osoba čísla jednotného, čas přítomný, způsob oznamovací, rod činný, sloveso
                $str = $word->getTranslation()[0] . " - $person. osoba čísla " . Short::Number($word->getNumber()) . "ho, čas " . Short::Tense($word->getTense()) .
                    ", způsob " . Short::Mood($word->getMood()) . ", rod " . Short::Gender_V($word->getGender()) . ", " . Czech::Class($word->getClass());
                $tooltip = "$person. os., č. " . Short::Number($word->getNumber())[0] . "., čas " . substr(Short::Tense($word->getTense()), 0, 4) .
                    "., zp. " . substr(Short::Mood($word->getMood()), 0, 3) . "., rod " . substr(Short::Gender_V($word->getGender()), 0, 3) . "., " . Czech::Class($word->getClass());
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
