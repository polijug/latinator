<?php
class Definition
{
    public $words = [];
    public $word;
    public $formate;
    public function __construct($word)
    {
        global $output;
        $output->setTitle($word);
        $this->word = $word;
        $sentence = new Sentence($word);
        $this->words = $sentence->words;
        $this->formate = Formating::Formate($this->words)->format;
        $this->formate = $this->formate[array_keys($this->formate)[0]];
    }
    public function print()
    {
        $str = "<h3>Výklad slova <i>" . $this->word . "</i></h3>";
        for ($i = 0; $i < count($this->formate); $i++) {
            $str .= $this->formate[$i] . "<br>";
        }
        global $output;
        $output->setContent($str, true);
    }
}
