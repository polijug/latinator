<?php
class WikiText
{
    public function __construct(string $text, string $lang, string $word)
    {
        $this->text = self::Isolate($text);
        $this->lang = $lang;
        $this->word = $word;
    }
    public $text;
    public $lang;
    private $word;
    public static function auto($word, $lang)
    {
        if ($lang == "en")
            $wikitext = new WikiText(API::enDict($word), "en", $word);
        else $wikitext = new WikiText(API::csDict($word), "cs", $word);
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
                $this->text = arrays::array_name_slice($this->text, "==Latin==");
                if (count($this->text) == 0) return false;
                if (!$derive)
                    $this->text = arrays::array_name_slice($this->text, "===" . ucfirst($class) . "===");

                $derived = $derive ? $this->isDerived() : $derive;

                $this->printText();
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
                            $translations = WikiText::Base($item->getBase(), $item->getClass()); //todo: no translation
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
                            $wordArray[] = $item;
                        }
                    }
                }
                $base = WikiText::Base($this->word, $class);
                $wordArray = array_merge($wordArray, $base);

                return $wordArray;
            case "cs":
                $this->text = arrays::array_name_slice($this->text, "== latina ==");
                if (count($this->text) == 0) return false;
                if (!$derive)
                    $this->text = arrays::array_name_slice($this->text, "=== " . Czech::Class($class) . " ===");

                $derived = $derive ? $this->isDerived() : $derive;

                $this->printText();

                if ($derived) {
                    $inflections = array_values(array_filter($this->text, function ($val) {
                        if (str_starts_with($val, '# \'\''))
                            return str_starts_with($val, "# ''") || str_starts_with($val, '# \'\'');
                    }));
                    $n = count($inflections);
                    $lastBase = "";
                    for ($i = 0; $i < $n; $i++) {
                        $item = Inflections::Parse($inflections[$i], $this->lang, $this->word);
                        if ($item != false) {
                            if ($item->getBase() != $lastBase || $lastBase == "") {
                                $translations = WikiText::Base($item->getBase(), $item->getClass());
                                if ($translations != false) {
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
                if (!$derived) {
                    $base = WikiText::Base($this->word, $class);
                    $wordArray = array_merge($wordArray, $base);
                }
                mlog($wordArray);
                mlog("wikitext_142");
                return $wordArray;
        }
    }
    private static function Base($base, $class = null)
    {
        $words = Database::getWordDB(new Word($base, $class));
        if ($words != false) return $words;
        $text = WikiText::Isolate(API::enDict($base), false);
        $text = arrays::array_name_slice($text, "==Latin==");
        $cstext = WikiText::Isolate(API::csDict($base));
        $cstext = arrays::array_name_slice($cstext, "== latina ==");
        if (count($text) == 0 && count($cstext) == 0) return false;
        if ($class != null) {
            $text = arrays::array_name_slice($text, "===" . ucfirst($class) . "===");
            $cstext = arrays::array_name_slice($cstext, "=== " . Czech::Class($class) . " ===");
        }
        $en = Base::Parse($text, "en", $base, $class);
        $cs = Base::Parse($cstext, "cs", $base, $class);

        $word = Words::Merge(array_merge($cs, $en));
        Database::insert($word);
        return $word;
    }
    private static function Isolate($text, $normal = true)
    {
        if ($normal)
            return explode("\n", str_replace(["\n\n", "{", "}", "[", "]"], ["\n"], $text));
        else
            return explode("\n", str_replace(["\n\n", "{{", "}}", "[", "]"], ["\n", "{", "}"], $text));
    }
    private function isDerived()
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
    public static function Derived($text, $lang = "en")
    {
        $array = $text;
        $searchStr = "head|la";
        if ($lang == "cs")
            $searchStr = "# ''";
        $n = count($array);
        for ($i = $lang == "cs" ? 2 : 1; $i < $n; $i++) {
            if (str_starts_with($array[$i], $searchStr) && $array[$i] != "head|la|conjunction" && !str_starts_with($array[$i], "head|la|pronoun"))
                return true;
        }
        return false;
    }
}
