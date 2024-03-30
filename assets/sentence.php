<?php
class Sentence
{
    public $sentence;
    public $words = [];
    public $count;
    public $format;

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
        //$format->formatAnswer();
        $this->format = $format;
        mlog($this->format);
    }
    private static function Analysis($sentence)
    {
        $sentence = explode(" ", lcfirst(trim($sentence)));
        return $sentence;
    }
}