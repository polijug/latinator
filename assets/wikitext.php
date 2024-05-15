<?php
class WikiText
{
    public function __construct(string $text, string $lang, string $word)
    {
        $this->text = self::Isolate($text, $lang);
        $this->lang = $lang;
        $this->word = $word;
        if(!$this->text) return false;
    }
    public $text;
    public $lang;
    private $word;
    public static function auto($word, $lang)
    {
        if ($lang == "en"){
            $wikitext = new WikiText(API::enDict($word), "en", $word);
            if(!$wikitext) $wikitext = new WikiText(API::enDict(ucfirst($word)), "en", ucfirst($word));
        }
        else $wikitext = new WikiText(API::csDict($word), "cs", $word);
        if(!$wikitext) return false;
        return $wikitext->Parse();
    }
    public function getWord()
    {
        if (!empty($this->word)) return $this->word;
        return null;
    }
    public function printText()
    {
        MLog($this->text);
    }
    public function Parse($derive = true, $class = null)
    {
        $wordArray = [];

        switch ($this->lang) {
            case "en":
                if (!$derive)
                    $this->text = arrays::array_name_slice($this->text, "===" . ucfirst($class) . "===");

                $derived = $derive ? $this->isDerived() : $derive;

                if ($derived) {
                    $inflections = array_values(array_filter($this->text, function ($val) {
                        return str_starts_with($val, "# inflection of|la") || str_contains($val, "inflection of|la") || str_starts_with($val, "head|la|");
                    }));

                    $n = count($inflections);
                    $obtClass = "";
                    for ($i = 0; $i < $n; $i++) {
                        if (str_starts_with($inflections[$i], "head|la|")) {
                            $obtClass = explode(" ", explode("|", $inflections[$i])[2])[0];
                            continue;
                        }
                        $start = explode("||", $inflections[$i]); //sometimes more - start is same
                        $inf = explode("|;|", $start[1]);
                        for ($j = 0; $j < count($inf); $j++) {
                            $item = Inflections::Parse($start[0] . "||" . $inf[$j], $this->lang, $this->word, $obtClass);
                            $translations = WikiText::Base($item->getBase(), $item->getClass());
                            $item = $this->mergeItemTrans($item, $translations);
                            $wordArray[] = $item;
                        }
                    }
                }
                $base = WikiText::Base($this->word);
                return array_merge($wordArray, $base);
            case "cs":
                if (!$derive)
                    $this->text = arrays::array_name_slice($this->text, "=== " . Czech::Class($class) . " ===");

                $derived = $derive ? $this->isDerived() : $derive;

                if ($derived) {
                    $inflections = array_values(array_filter($this->text, function ($val) {
                        return str_starts_with($val, "# ''");
                    }));
                    $n = count($inflections);
                    $lastBase = "";
                    for ($i = 0; $i < $n; $i++) {
                        $item = Inflections::Parse($inflections[$i], $this->lang, $this->word);
                        if ($item) {
                            if ($item->getBase() != $lastBase || $lastBase == "") {
                                $translations = WikiText::Base($item->getBase(), $item->getClass());
                                if ($translations) {
                                    $item = $this->mergeItemTrans($item, $translations);
                                }
                            } else {
                                $item->addTranslation($wordArray[$i - 1]->getTranslation());
                                $type = Words::hasForms($item);
                                if ($type > 0)
                                    $item->setTable($wordArray[$i - 1]->getTable());
                                if ($type > 0 && $item->getGender() == null)
                                    $item->setGender($wordArray[$i - 1]->getGender());
                                if ($type == 1 && $item->getDeclination() == null)
                                    $item->setDeclination($wordArray[$i - 1]->getDeclination());
                                if ($type == 2 && $item->getConjugation() == null)
                                    $item->setConjugation($wordArray[$i - 1]->getConjugation());
                            }

                            $wordArray[] = $item;
                            $lastBase = $item->getBase();
                        }
                    }
                }
                    $base =  WikiText::Base($this->word);
                return array_merge($wordArray, $base);
        }
    }
    private static function Base($base, $class = null)
    {
        $words = Database::getWordDB(new Word($base, $class));
        $valid = Database::valid($base);
        if ($words && $valid || $words && $class != null) return $words;
        $text = WikiText::Isolate(API::enDict($base),"en", false);
        $cstext = WikiText::Isolate(API::csDict($base), "cs", false);
        if (count($text) == 0 && count($cstext) == 0) return false;
        if ($class != null) {
            $text = arrays::array_name_slice($text, "===" . ucfirst($class) . "===");
            $cstext = arrays::array_name_slice($cstext, "=== " . Czech::Class($class) . " ===");
        }
        $en = Base::Parse($text, "en", $base, $class);
        $cs = Base::Parse($cstext, "cs", $base, $class);

        $word = Words::Merge(array_merge($en, $cs));
        Database::insert($word);
        return $word;
    }
    private static function Slice($text, $lang){
        $name = $lang == "cs" ? "== latina ==" : "==Latin==";
        $text = arrays::array_name_slice($text, $name);
        if (count($text) == 0) return false;
        return $text;
    }
    private static function Isolate($text, $lang, $normal = true)
    {
        if ($normal)
            return self::Slice(explode("\n", str_replace(["\n\n", "{", "}", "[", "]"], ["\n"], $text)), $lang);
        else
            return self::Slice(explode("\n", str_replace(["\n\n", "{{", "}}", "[", "]"], ["\n", "{", "}"], $text)), $lang);
    }

    private function isDerived(): bool
    {
        $array = $this->text;
        $searchStr = $searchStr1 = "head|la";
        if ($this->lang == "cs") {
            $searchStr1 = "# \'\'";
            $searchStr = "# ''";
        }
        $n = count($array);
        for ($i = 2; $i < $n; $i++) {
            if ((str_starts_with($array[$i], $searchStr) || str_starts_with($array[$i], $searchStr1)) && $array[$i] != "head|la|conjunction")
                return true;
        }
        return false;
    }

    public static function Derived($text, $lang = "en"): bool
    {
        $array = $text;
        $searchStr = "head|la";
        if ($lang == "cs")
            $searchStr = "# ''";
        $n = count($array);
        for ($i = $lang == "cs" ? 2 : 1; $i < $n; $i++) {
            if (str_contains($array[$i], $searchStr) && $array[$i] != "head|la|conjunction" && !str_contains($array[$i], "head|la|pronoun"))
                return true;
        }
        return false;
    }

    public function mergeItemTrans($item, $translations)
    {
        $m = count($translations);
        for ($j = 0; $j < $m; $j++) {
            $item->addTranslation($translations[$j]->getTranslation());
            $type = Words::hasForms($item);
            if ($type > 0)
                $item->setTable($translations[$j]->getTable());
            if ($type > 0 && $item->getGender() == null)
                $item->setGender($translations[$j]->getGender());
            if ($type == 1 && $item->getDeclination() == null)
                $item->setDeclination($translations[$j]->getDeclination());
            if ($type == 2 && $item->getConjugation() == null)
                $item->setConjugation($translations[$j]->getConjugation());
        }
        return $item;
    }
}
