<?php
include("assets/table.php");

class Noun extends Word
{
    protected $number;
    protected $form;
    protected $gender;
    protected $table;
    protected $bold = [];
    protected $declination;

    public function __construct($word, $base, $form, $number, $gender = null, $declination = null, $translation = [])
    {
        $this->word = $word;
        $form = Czech::FormToEn($form);
        $this->base = str_trim($base);
        $this->form = [$number => $form];
        $this->number = $number;
        $this->gender = $gender;
        $this->translation = $translation;
        $this->declination = is_string($declination) ? $declination : array_filter($declination, static function ($var) {
            return !isnull($var);
        });
    }

    public function toJSON(): string
    {
        return "{
            'class': '$this->class',   
            'base': '$this->base',
            'word': '$this->word',
            'translation': " . jsonEncode($this->translation) . ",
            'gender': " . jsonEncode($this->getGender()) . ",
            'form': " . jsonEncode($this->form) . ",
            'number': " . jsonEncode($this->number) . ",
            'declination': " . jsonEncode($this->declination) . "
        }";
    }

    public $class = "noun";

    public function setTable($array)
    {
        $this->table = $array;
    }

    public function setBold($array)
    {
        if (isnull($this->bold))
            $this->bold = $array;
        else $this->bold = Merge::Values($this->bold, $array);
    }
    public function getDeclination()
    {
        return $this->declination ?? null;
    }
    public function setDeclination($declination)
    {
        $this->declination = $declination;
    }
    public function getTable()
    {
        if (is_string($this->table)) {
            $table = new Table();
            $table->table = $this->table;
            $table->setValidity();
            $this->table = $table;
        } else $table = $this->table;

        return $table ?? null;
    }

    public function getBold()
    {
        return $this->bold ?? null;
    }

    public function getForm(): ?array
    {
        return $this->form ?? null;
    }

    public function getNumber()
    {
        return $this->number ?? null;
    }

    public function setNumber($num)
    {
        $this->number = $num;
    }

    public function getGender()
    {
        if ($this->class == "noun") {
            if (is_array($this->gender))
                $this->gender = $this->gender[0];
        }
        return $this->gender ?? null;
    }

    public function setGender($gen)
    {
        $this->gender = $gen;
    }

    public function getAllInfo(): array
    {
        return array("base" => $this->base, "form" => $this->form, "number" => $this->number, "gender" => $this->gender);
    }

    public function TestFullness(): bool
    {
        $translate = count($this->translation) > 0;
        $base = isset($this->base);
        $form = isset($this->form);
        $number = isset($this->number);
        $table = isset($this->table) && $this->table->getValidity();

        return $translate && $base && $form && $number && $table;
    }

    public function Combine($word)
    {
        $trans = $word->getTranslation();
        $n = count($trans);
        for ($i = 0; $i < $n; $i++) {
            if (!in_array($trans[$i], $this->getTranslation(), true)) {
                $this->addTranslation($trans[$i]);
            }
        }
        $this->gender = $this->gender ?? $word->gender;
        $this->number = $this->number ?? $word->number;
        $this->declination = $this->declination ?? $word->declination;
        $this->table = Table::decideTable($this->table, $word->table);
        $this->form = $this->form ?? $word->form;
    }

    public function matchSpecParam($word): bool
    {
        if ($this->number != $word->number) return false;
        if ($this->form != $word->form) return false;
        return true;
    }

    public function Merge($word, $full = true)
    {
        if ($full) {
            $word->form = (array)$word->form;
            $this->form = Merge::Values($word->form, $this->form);
            $this->number = Merge::Values($this->number, $word->number);
        }
        $this->gender = Merge::Values($this->gender, $word->gender);
        $this->translation = Merge::Values($this->translation, $word->translation);
        $this->declination = Merge::Values($this->declination, $word->declination);
        $this->table = strlen($this->table->table) > strlen($word->table->table) ? $this->table : $word->table;
    }
}

class Adjective extends Noun
{
    public function __construct($word, $base, $form, $number, $gender, $declination = null, $translation = [])
    {
        $this->word = $word;
        $form = Czech::FormToEn($form);
        parent::__construct($word, $base, $form, $number, $gender, $declination, $translation);
        if (is_array($gender)) {
            $this->form = [];
            for ($i = 0; $i < count($gender); $i++) {
                $this->form[$gender[$i] . "_" . $number] = $form;
            }
        } else $this->form = [$gender . "_" . $number => $form];
    }

    public $class = "adjective";

    public function Merge($word, $full = true)
    {
        if ($full) {
            $word->form = (array)$word->form;
            $this->form = Merge::Values($this->form, $word->form);
            $this->number = Merge::Values($this->number, $word->number);
        }
        $this->gender = Merge::Values($this->gender, $word->gender);
        $this->declination = Merge::Values($this->declination, $word->declination);
        $this->translation = Merge::Values($this->translation, $word->translation);
        $this->table = strlen($this->table->table) > strlen($word->table->table) ? $this->table : $word->table;
    }
}

class Numeral extends Noun
{
    public function __construct($word, $base, $form, $number, $gender = null, $translation = [])
    {
        $this->word = $word;
        $form = Czech::FormToEn($form);
        parent::__construct($word, $base, $form, $number, $gender, null, $translation);
    }

    public $class = "numeral";
}

class Adverb extends Word
{
    public function __construct($base, $translation = [])
    {
        parent::__construct($base, "adverb", $translation);
    }
}

class Pronoun extends Noun
{
    public function __construct($word, $base, $form, $number, $type, $person = null, $gender = null, $translation = [])
    {
        $this->word = $word;
        $form = Czech::FormToEn($form);
        parent::__construct($word, $base, $form, $number, $gender, null, $translation);
        $this->type = $type;
        $this->person = $person;
    }

    public $class = "pronoun";
    public $type;
    public $person;

    public function getType()
    {
        return $this->type ?? null;
    }
    public function setType($type)
    {
        $this->type = $type;
    }
    public function getPerson()
    {
        return $this->person ?? null;
    }

    public function toJSON(): string
    {
        return "{
            'class': '$this->class',
            'base': '$this->base',
            'word': '$this->word',
            'translation': " . jsonEncode($this->translation) . ",
            'gender': '$this->gender',
            'form': " . jsonEncode($this->form) . ",
            'number': " . jsonEncode($this->number) . ",
            'type': '$this->type',
            'declination': '$this->declination',
            'person': '$this->person'
        }";
    }
}

class Preposition extends Word
{
    public function __construct($word, $base, $with, $translation)
    {
        $this->word = $word;
        $this->base = str_trim($base);
        $this->with = $with;
        $this->translation = $translation;
    }

    public $base;
    public $with;
    public $class = "preposition";
    public $bold;

    public function ParseWith($lang = "cs")
    { //TODO in en
        if (!isset($this->with))
            return;
        if($lang == "cs") {
            $with = explode("''", $this->with)[1];
            $this->with = Czech::FormToEn(str_trim(substr($with, 2, 3)));
        }
        else{
            preg_match_all("/\{la-prep\|(?<letter>[a-z]{3})/u", $this->with, $form);
            $this->with = $form['letter'];
        }
    }

    public function getBold()
    {
        return $this->bold ?? null;
    }

    public function setBold($bold){
        $this->bold = $bold;
    }

    public function getWith(){
        return $this->with ?? null;
    }

    public function toJSON(): string
    {
        return "{
            'class': '$this->class',
            'base': '$this->base',
            'word': '$this->word',
            'translation': " . jsonEncode($this->translation) . ",
            'with': " . jsonEncode($this->with) . "
        }";
    }

    public function TestFullness(): bool
    {
        $translate = count($this->translation) > 0;
        $base = isset($this->base);
        $with = isset($this->with);

        return $translate && $base && $with;
    }

    public function Combine($word)
    {
        $trans = $word->getTranslation();
        $n = count($trans);
        for ($i = 0; $i < $n; $i++) {
            if (!in_array($trans[$i], $this->getTranslation(), true)) {
                $this->addTranslation($trans[$i]);
            }
        }
        $this->with = $this->with ?? $word->with;
    }

    public function Merge($word)
    {
        $this->with = Merge::Values($this->with, $word->with);
        $this->translation = Merge::Values($this->translation, $word->translation);
    }
}

class Connective extends Word
{
    public function __construct($base, $translation = [])
    {
        parent::__construct($base, "connective", $translation);
    }
}

class Verb extends Noun
{
    protected $tense;
    protected $person;
    protected $mood;
    protected $conjugation;

    public function __construct($word, $base, $number, $tense, $person, $gender, $mood, $conjugation = null, $translation = [])
    {
        $this->word = $word;
        $this->base = str_trim($base);
        $this->tense = $tense;
        $this->person = [$gender . "_" . $number => $person]; //or mood_tense_gender_number?
        $this->gender = $gender;
        $this->mood = Czech::MoodToEn($mood);
        $this->number = $number;
        $this->translation = $translation;
        $this->conjugation = $conjugation;
    }

    public function toJSON(): string
    {
        return "{
            'class': '$this->class',
            'base': '$this->base',
            'word': '$this->word',
            'translation': " . jsonEncode($this->translation) . ",
            'tense': '$this->tense',
            'mood': '$this->mood',
            'person': " . jsonEncode($this->person) . ",
            'gender': " . jsonEncode($this->gender) . ",
            'number': " . jsonEncode($this->number) . ",
            'conjugation': '$this->conjugation'
        }";
    }

    public $class = "verb";

    public function getTense()
    {
        return $this->tense ?? null;
    }

    public function getPerson(): ?array
    {
        return $this->person ?? null;
    }

    public function getMood()
    {
        return $this->mood ?? null;
    }

    public function getConjugation()
    {
        if (is_array($this->conjugation))
            $this->conjugation = $this->conjugation[0];
        return $this->conjugation ?? null;
    }
    public function setConjugation($con)
    {
        $this->conjugation = $con;
    }

    public function getAllInfo(): array
    {
        return array("base" => $this->base, "tense" => $this->tense, "number" => $this->number, "gender" => $this->gender, "person" => $this->person, "mood" => $this->mood);
    }

    public function TestFullness(): bool
    {
        $translate = count($this->translation) > 0;
        $base = isset($this->base);
        $mood = isset($this->mood);
        $number = isset($this->number);
        $table = isset($this->table) && $this->table->getValidity();
        $tense = isset($this->tense);
        $person = isset($this->person);


        return $translate && $base && $mood && $number && $table && $tense && $person;
    }

    public function Combine($word)
    {
        $trans = $word->getTranslation();
        $n = count($trans);
        for ($i = 0; $i < $n; $i++)
            if (!in_array($trans[$i], $this->getTranslation(), true))
                $this->addTranslation($trans[$i]);
        $this->mood = $this->mood ?? $word->mood;
        $this->number = $this->number ?? $word->number;
        $this->table = Table::decideTable($this->table, $word->table);
        $this->tense = $this->tense ?? $word->tense;
        $this->conjugation = $this->conjugation ?? $word->conjugation;
    }

    public function matchSpecParam($word): bool
    {
        if ($this->number != $word->number) return false;
        if ($this->tense != $word->tense) return false;
        if ($this->person != $word->person) return false;
        if ($this->mood != $word->mood) return false;
        return true;
    }

    public function isSame($word): bool
    {
        return $this->base == $word->base && $this->class == $word->class && $this->mood == $word->mood && $this->tense == $word->tense;
    }

    public function Merge($word, $full = true)
    {
        if ($full) {
            $this->number = Merge::Values($this->number, $word->number);
            $this->person = Merge::Values($this->person, $word->person);
            $this->gender = Merge::Values($this->gender, $word->gender);
        }
        $this->translation = Merge::Values($this->translation, $word->translation);
        $this->table = strlen($this->table->table) > strlen($word->table->table) ? $this->table : $word->table;
        $this->conjugation = Merge::Values($this->conjugation, $word->conjugation);
    }
}

class Word
{
    public $class;
    protected $base;
    protected $word;
    protected $translation = [];

    public function __construct($base, $class, $translation = [])
    {
        $this->base = $base;
        $this->word = $base;
        $this->class = $class;
        $this->translation = $translation;
    }
    public function getBase(): string
    {
        return str_trim($this->base);
    }

    public function getClass()
    {
        return $this->class ?? null;
    }

    public function getTranslation($int = 1)
    {
        if($int == 1)
            return $this->translation ?? null;
        else{
            $translation = $this->translation[0];
            if(strlen($translation) > 23)
                return explode(", ", $translation)[0];
            return $translation;
        }
    }

    public function getWord()
    {
        return $this->word ?? null;
    }

    public function toJSON(): string
    {
        return "{
            'class': '$this->class',
            'base': '$this->base',
            'word': '$this->word',
            'translation': " . jsonEncode($this->translation) . "
        }";
    }

    public function addTranslation($translation)
    {
        if (is_string($translation))
            $this->translation[] = strtolower(str_trim($translation));
        else if (is_array($translation) && !isnull($translation) && !isnull($this->translation))
            $this->translation = array_unique(array_merge($this->translation, $translation));
        else $this->translation = $translation;
    }

    public function TestFullness(): bool
    {
        $translate = count($this->translation) > 0;
        $base = isset($this->base);
        return $translate && $base;
    }

    public function Combine($word)
    {
        $trans = $word->getTranslation();
        $n = count($trans);
        for ($i = 0; $i < $n; $i++)
            if (!in_array($trans[$i], $this->getTranslation(), true))
                $this->addTranslation($trans[$i]);
    }

    public function Merge($word)
    {
        $this->translation = Merge::Values($this->translation, $word->translation);
    }

    public function isSame($word): bool
    {
        return $this->base == $word->base && $this->class == $word->class;
    }

    public function matchSpecParam($word): bool
    {
        return true;
    }
}

class JSONobj
{
    public $class;
    public $base;
    public $word;
    public $translation;
    public $table;
    public $form;
    public $number;
    public $gender;
    public $tense;
    public $person;
    public $mood;
    public $with;
    public $bold;
    public $declination;
    public $conjugation;
    public $type;

    public function getBold()
    {
        return $this->bold ?? null;
    }
    public function setBold($bold)
    {
        if (isnull($this->bold))
            $this->bold = $bold;
        else $this->bold = Merge::Values($this->bold, $bold);
    }
    public function getBase()
    {
        return isnull($this->base) ? str_trim($this->base) : null;
    }

    public function getClass()
    {
        return $this->class ?? null;
    }

    public function getTranslation($int = 1)
    {
        if($int == 1)
            return $this->translation ?? null;
        else{
            $translation = $this->translation[0];
            if(strlen($translation) > 23)
                return explode(", ", $translation)[0];
            return $translation;
        }
    }
    public function addTranslation($translation)
    {
        $this->translation = $translation;
    }

    public function getTense()
    {
        return $this->tense ?? null;
    }

    public function getPerson(): array
    {
        return (array)$this->person;
    }

    public function getMood()
    {
        return $this->mood ?? null;
    }

    public function getTable(): Table
    {
        if (is_string($this->table)) {
            $table = new Table();
            $table->table = $this->table;
            $table->setValidity();
            $this->table = $table;
        } else $table = $this->table;

        return $table;
    }

    public function getForm()
    {
        return (array)$this->form;
    }

    public function getNumber()
    {
        return $this->number ?? null;
    }

    public function getGender()
    {
        return $this->gender ?? null;
    }

    public function getDeclination()
    {
        return $this->declination ?? null;
    }
    public function setDeclination($declination)
    {
        $this->declination = $declination;
    }

    public function getConjugation()
    {
        if (is_array($this->conjugation))
            $this->conjugation = $this->conjugation[0];
        return $this->conjugation ?? null;
    }
    public function setConjugation($con)
    {
        $this->conjugation = $con;
    }

    public function getWord()
    {
        return $this->word ?? null;
    }
    public function getType()
    {
        return $this->type ?? null;
    }
    public function setType($type)
    {
        $this->type = $type;
    }
    public function isSame($word): bool
    {
        return $this->base == $word->base && $this->class == $word->class;
    }
    public function getWith()
    {
        return $this->with ?? null;
    }
    public function matchSpecParam($word): bool
    {
        $form = Words::hasForms($word);
        if ($form == 1) {
            if ($this->number != $word->number) return false;
            if ($this->form != $word->form) return false;
        }
        if ($form == 2) {
            if ($this->number != $word->number) return false;
            if ($this->tense != $word->tense) return false;
            if ($this->person != $word->person) return false;
            if ($this->mood != $word->mood) return false;
        }
        return true;
    }
    public function Combine($word)
    {
        $form = Words::hasForms($word);
        $trans = $word->getTranslation();
        $n = count($trans);
        for ($i = 0; $i < $n; $i++)
            if (!in_array($trans[$i], $this->getTranslation(), true))
                $this->addTranslation($trans[$i]);
        if ($word->getClass() == "preposition")
            $this->with = $this->with ?? $word->with;
        if ($form == 1) {
            $this->gender = $this->gender ?? $word->gender;
            $this->number = $this->number ?? $word->number;
            $this->declination = $this->declination ?? $word->declination;
            $this->table = Table::decideTable($this->table, $word->table);
            $this->form = $this->form ?? $word->form;
        }
        if ($form == 2) {
            $this->mood = $this->mood ?? $word->mood;
            $this->number = $this->number ?? $word->number;
            $this->table = Table::decideTable($this->table, $word->table);
            $this->tense = $this->tense ?? $word->tense;
            $this->conjugation = $this->conjugation ?? $word->conjugation;
        }
    }
}

class Words
{
    public static function sortForms($form)
    {
        if (!is_array($form))
            return $form;
        $array = array();
        $n = count($form);
        for ($i = 0; $i < $n; $i++)
            switch ($form[$i]) {
                case "nom":
                    $array[0] = "nom";
                    break;
                case "gen":
                    $array[1] = "gen";
                    break;
                case "dat":
                    $array[2] = "dat";
                    break;
                case "acc":
                    $array[3] = "acc";
                    break;
                case "voc":
                    $array[4] = "voc";
                    break;
                case "abl":
                    $array[5] = "abl";
                    break;
            }
        $out = [];
        for ($i = 0; $i < 6; $i++)
            if (isset($array[$i]))
                $out[] = $array[$i];
        return $out;
    }
    public static function hasForms($word): int
    { //0 -other, 1 -form, 2 -coniug
        $class = $word->getClass();
        switch ($class) {
            case "verb":
                return 2;
            case "noun":
            case "pronoun":
            case "adjective":
            case "numeral":
                return 1;
            default:
                return 0;
        }
    }

    public static function Fullness($words)
    {
        $n = count($words);
        for ($i = 0; $i < $n; $i++)
            if (!$words[$i]->TestFullness())
                return false;
        return true;
    }

    public static function Combine($word1, $word2)
    {
        if ((!$word1 || $word1 == []) && (!$word2 || $word2 == [])) return false;
        if (!$word1 || $word1 == [] || !$word2 || $word2 == []) return !$word1 || $word1 == [] ? $word2 : $word1;
        $n1 = count($word1);
        $output = [];
        for ($i = 0; $i < $n1; $i++) {
            $n2 = count($word2);
            for ($j = 0; $j < $n2; $j++) {
                if ($word1[$i]->getClass() != $word2[$j]->getClass() || $word1[$i]->getBase() != $word2[$j]->getBase()) continue;
                if ($word1[$i]->matchSpecParam($word2[$j])) {
                    $word1[$i]->Combine($word2[$j]);
                    unset($word2[$j]);
                    $word2 = array_values($word2);
                    $j--;
                    $n2--;
                }
            }
            $output[] = $word1[$i];
        }
        if (count($word2) > 0) $output = array_merge($output, $word2);
        return $output;
    }

    public static function Merge($words): ?array
    {
        if (isnull($words)) return [];
        $n = count($words);
        for ($i = 0; $i < $n; $i++)
            for ($j = $i + 1; $j < $n; $j++) {
                if ($words[$i]->isSame($words[$j])) {
                    $words[$i]->Merge($words[$j]);
                    unset($words[$j]);
                    $words = array_values($words);
                    $n--;
                    $j--;
                }
            }
        return array_values($words);
    }

    public static function Pairable($words): array
    {
        $o = count($words);
        $pairable = ["noun", "adjective", "numeral", "pronoun", "preposition"];
        for ($i = 1; $i < $o; $i++) {
            if (!is_array($words[$i])) $words[$i] = [$words[$i]];
            if (!is_array($words[$i - 1])) $words[$i - 1] = [$words[$i - 1]];
            for ($j = 0; $j < count($words[$i]); $j++)
                for ($k = 0; $k < count($words[$i - 1]); $k++) {
                    $word1 = $words[$i][$j];
                    $word2 = $words[$i - 1][$k];
                    $result = [];
                    if(!$word1 || !$word2 || isnull($word1) || isnull($word2)) continue;
                    if (!in_array($word1->getClass(), $pairable) || !in_array($word2->getClass(), $pairable)) continue;
                    if ($word1->getClass() == "noun" && $word2->getClass() == "noun") continue;

                    $numbers = self::formIntersection($word1->getNumber(), $word2->getNumber());
                    $gender = self::formIntersection($word1->getGender(), $word2->getGender());

                    $m = count($numbers);
                    if ($word1->getClass() == "preposition" xor $word2->getClass() == "preposition") {
                        if ($word1->getClass() == "preposition") {
                            $preposition =  $word1;
                            $other = $word2;
                        } else {
                            $preposition =  $word2;
                            $other = $word1;
                        }
                        $keys = array_keys($other->getForm());
                        $m = count($keys);
                        for ($l = 0; $l < $m; $l++) {
                            $form = Words::formIntersection($other->getForm(), $preposition->getWith());
                            if($form != [])
                                $result[$keys[$l]] = $form;
                        }
                    } else if ($word1->getClass() != "adjective" or $word2->getClass() != "adjective")
                        for ($l = 0; $l < $m; $l++) {
                            if ($word1->getClass() != "adjective" and $word2->getClass() != "adjective")
                                $result[$gender[0] . "_" . $numbers[$l]] = self::formIntersection($word1->getForm()[$numbers[$l]], $word2->getForm()[$numbers[$l]]);
                            else if ($word1->getClass() == "adjective" xor $word2->getClass() == "adjective") {
                                $adjective = $word1->getClass() == "adjective" ? $word1 : $word2;
                                $other = $word2->getClass() != "adjective" ? $word2 : $word1;
                                if (isset($adjective->getForm()[$gender[0] . "_" . $numbers[$l]]) && isset($other->getForm()[$numbers[$l]]))
                                    $result[$gender[0] . "_" . $numbers[$l]] = self::formIntersection($adjective->getForm()[$gender[0] . "_" . $numbers[$l]], $other->getForm()[$numbers[$l]]);
                            }
                        }
                    else if (!isnull($word2->getBold())) { //if is present search by this, if not same return, else for each gender find corelation (if index isnt present continue)
                        $keys = array_keys($word2->getBold());
                        for ($l = 0; $l < count($keys); $l++)
                            if (array_key_exists($keys[$l], $word1->getForm()))
                                $result[$keys[$l]] = self::formIntersection($word2->getBold()[$keys[$l]], $word1->getForm()[$keys[$l]]);
                    } else {
                        $keys = self::formIntersection(array_keys($word1->getForm()), array_keys($word2->getForm()));
                        for ($l = 0; $l < count($keys); $l++)
                            $result[$keys[$l]] = self::formIntersection($word2->getForm()[$keys[$l]], $word1->getForm()[$keys[$l]]);
                    }
                    if ($result == []) continue;
                    if (is_array($words[$i])) {
                        $words[$i - 1][$k]->setBold($result);
                        $words[$i][$j]->setBold($result);
                    } else if (!isnull($words[$i])) {
                        $words[$i - 1]->setBold($result);
                        $words[$i]->setBold($result);
                    }
                }
        }
        return array_values($words);
    }
    public static function formIntersection($form1, $form2): array
    {
        if (is_array($form1) && is_array($form2)) {
            $result = array_intersect($form1, $form2);
        } else if (is_array($form1) || is_array($form2)) {
            if (is_array($form1))
                $result = in_array($form2, $form1) ? [$form2] : [];
            else
                $result = in_array($form1, $form2) ? [$form1] : [];
        } else $result = $form2 == $form1 ? [$form1] : [];
        return $result;
    }

    public static function decodeJSON($word): JSONobj
    {
        if (isset($word["tables"])) $table = $word["tables"];
        $word = json_decode(str_replace(["'", "\n", "\r"], ["\"", "", ""], $word["json"]));
        $out = new JSONobj();
        $out->class = $word->class;
        $out->base = $word->base;
        $out->word = $word->word;
        $out->translation = $word->translation;
        if (isset($word->gender)) $out->gender = $word->gender;
        if (isset($word->form)) $out->form = (array)$word->form;
        if (isset($word->number)) $out->number = $word->number;
        if (isset($table)) $out->table = html_entity_decode($table);
        if (isset($word->tense)) $out->tense = $word->tense;
        if (isset($word->person)) $out->person = (array)$word->person;
        if (isset($word->mood)) $out->mood = $word->mood;
        if (isset($word->with)) $out->with = $word->with;
        if (isset($word->conjugation)) $out->conjugation = $word->conjugation;
        if (isset($word->declination)) $out->declination = $word->declination;
        if (isset($word->type)) $out->type = $word->type;
        return $out;
    }
}

class Merge
{
    public static function Values($value1, $value2, $sidetoside = false): array
    {
        if (isnull($value1) && isnull($value2))
            return [];
        if ($sidetoside) $value1 = [$value1, $value2];
        if (isnull($value1) || isnull($value2)) {
            if (is_string($value1) || is_string($value2))
                $value1 = [!isnull($value1) ? $value1 : $value2];
            $value1 = !isnull($value1) ? $value1 : $value2;
        } else if (is_array($value1) && is_array($value2)) {
            if (!is_string(array_keys($value1)[0]))
                $value1 = array_merge($value1, $value2);
            else {
                $keys = array_unique(array_merge(array_keys($value1), array_keys($value2)));
                for ($i = 0; $i < count($keys); $i++)
                    $value1[$keys[$i]] = arrays::remove_null(self::Values($value1[$keys[$i]], $value2[$keys[$i]]));
                return $value1;
            }
        } else if (is_array($value2) || is_array($value1)) {
            if (is_array($value2)) {
                $value2[] = $value1;
                $value1 = $value2;
            } else
                $value1[] = $value2;
        } else $value1 = [$value1, $value2];
        return array_values(array_unique($value1));
    }
}
