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
        $this->base = trim($base);
        $this->form = [$number => $form];
        $this->number = $number;
        $this->gender = $gender;
        $this->translation = $translation;
        $this->declination = $declination;
    }

    public function toJSON()
    {
        $gender = $this->gender;
        if (is_array($gender)) $gender = jsonEncode($gender);
        else $gender = "'" . $gender . "'";
        return "{
            'class': '$this->class',
            'base': '$this->base',
            'word': '$this->word',
            'translation': " . jsonEncode($this->translation) . ",
            'gender': $gender,
            'form': " . jsonEncode($this->form) . ",
            'number': " . jsonEncode($this->number) . ",
            'declination': '$this->declination'
        }";
    }

    public $class = "noun";

    public function setTable($array)
    {
        $this->table = $array;
    }

    public function setBold($array)
    {
        $this->bold = $array;
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
        return isset($this->table) ? $this->table : null;
    }

    public function getBold()
    {
        return $this->bold ?? null;
    }

    public function getForm()
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
        return $this->gender ?? null;
    }

    public function setGender($gen)
    {
        $this->gender = $gen;
    }

    public function getAllInfo()
    {
        return array("base" => $this->base, "form" => $this->form, "number" => $this->number, "gender" => $this->gender);
    }

    public function TestFullness()
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
        $this->table = Table::decideTable($this->table, $word->table);
        $this->form = $this->form ?? $word->form;
    }

    public function matchSpecParam($word)
    {
        if ($this->number != $word->number) return false;
        if ($this->form != $word->form) return false;
        return true;
    }

    public function Merge($word)
    {
        $word->form[$this->number] = isset($word->form[$this->number]) ? Words::sortForms(Merge::Values($this->form[$this->number], $word->form[$this->number])) : $this->form[$this->number];
        $this->form = $word->form;
        $this->number = Merge::Values($this->number, $word->number);
        $this->translation = Merge::Values($this->translation, $word->translation);
        $this->table = strlen($this->table->table) > strlen($word->table->table) ? $this->table : $word->table;
    }
}

class Adjective extends Noun
{
    public function __construct($word, $base, $form, $number, $gender, $translation = [])
    {
        $this->word = $word;
        $form = Czech::FormToEn($form);
        parent::__construct($word, $base, $form, $number, $gender, null, $translation);
        $this->form = [$gender . "_" . $number => $form];
    }

    public $class = "adjective";

    public function Merge($word)
    {
        $word->form[$this->gender . "_" . $this->number] = isset($word->form[$this->gender . "_" . $this->number]) ? Words::sortForms(Merge::Values($this->form[$this->gender . "_" . $this->number], $word->form[$this->gender . "_" . $this->number])) : $this->form[$this->gender . "_" . $this->number];
        $this->form = $word->form;
        $this->number = Merge::Values($this->number, $word->number);
        $this->gender = Merge::Values($this->gender, $word->gender);
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
    public function __construct($word, $base, $translation = [])
    {
        $this->word = $word;
        $this->base = trim($base);
        $this->translation = $translation;
    }

    public $class = "adverb";
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

    public function toJSON()
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
        $with = Czech::FormToEn($with);
        $this->base = trim($base);
        $this->with = $with;
        $this->translation = $translation;
    }

    public $base;
    public $with;
    public $class = "preposition";

    public function ParseWith()
    { //TODO in en
        if (!isset($this->with))
            return;
        $with = explode("''", $this->with)[1];
        $this->with = substr($with, 1, 3);
    }

    public function toJSON()
    {
        return "{
            'class': '$this->class',
            'base': '$this->base',
            'word': '$this->word',
            'translation': " . jsonEncode($this->translation) . ",
            'with': " . jsonEncode($this->with) . "
        }";
    }

    public function TestFullness()
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
        $this->translation = Merge::Values($this->with, $word->with);
        $this->translation = Merge::Values($this->translation, $word->translation, true);
    }
}

class Connective extends Word
{
    public function __construct($base, $translation = [])
    {
        $this->base = trim($base);
        $this->translation = $translation;
    }

    public $class = "connective";
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
        $this->base = trim($base);
        $this->tense = $tense;
        $this->person = [$gender . "_" . $number => $person]; //or mood_tense_gender_number?
        $this->gender = $gender;
        $this->mood = $mood;
        $this->number = $number;
        $this->translation = $translation;
        $this->conjugation = $conjugation;
    }

    public function toJSON()
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

    public function getPerson()
    {
        return $this->person ?? null;
    }

    public function getMood()
    {
        return $this->mood ?? null;
    }

    public function getConjugation()
    {
        return $this->conjugation ?? null;
    }
    public function setConjugation($con)
    {
        $this->conjugation = $con;
    }

    public function getAllInfo()
    {
        return array("base" => $this->base, "tense" => $this->tense, "number" => $this->number, "gender" => $this->gender, "person" => $this->person, "mood" => $this->mood);
    }

    public function TestFullness()
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
    }

    public function matchSpecParam($word)
    {
        if ($this->number != $word->number) return false;
        if ($this->tense != $word->tense) return false;
        if ($this->person != $word->person) return false;
        if ($this->mood != $word->mood) return false;
        return true;
    }

    public function isSame($word)
    {
        return $this->base == $word->base && $this->class == $word->class && $this->mood == $word->mood && $this->tense == $word->tense;
    }

    public function Merge($word)
    {
        $this->number = Merge::Values($this->number, $word->number);
        $this->gender = Merge::Values($this->gender, $word->gender);
        //pozor na array!!
        $word->person[$this->gender . "_" . $this->number] = isset($word->person[$this->gender . "_" . $this->number]) ? Merge::Values($this->person[$this->gender . "_" . $this->number], $word->person[$this->gender . "_" . $this->number]) : $this->person[$this->gender . "_" . $this->number];
        $this->person = $word->person;
        $this->translation = Merge::Values($this->translation, $word->translation);
        $this->table = strlen($this->table->table) > strlen($word->table->table) ? $this->table : $word->table;
    }
}

class Word
{
    public $class;
    protected $base;
    protected $word;
    protected $translation = [];

    public function __construct($base, $class)
    {
        $this->base = $base;
        $this->word = $base;
        $this->class = $class;
    }
    public function getBase()
    {
        return trim($this->base);
    }

    public function getClass()
    {
        return $this->class ?? null;
    }

    public function getTranslation()
    {
        return $this->translation ?? null;
    }

    public function getWord()
    {
        return $this->word ?? null;
    }

    public function toJSON()
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
            array_push($this->translation, strtolower(trim($translation)));
        else if (is_array($translation) && !is_null($translation) && !is_null($this->translation))
            $this->translation = array_unique(array_merge($this->translation, $translation));
        else $this->translation = $translation;
    }

    public function TestFullness()
    {
        $translate = count($this->translation) > 0;
        $base = isset($this->base);
        return $translate && $base;
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
    }

    public function isSame($word)
    {
        return $this->base == $word->base && $this->class == $word->class;
    }

    public function matchSpecParam($word)
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
        $this->bold = $bold;
    }
    public function getBase()
    {
        return trim($this->base);
    }

    public function getClass()
    {
        return $this->class ?? null;
    }

    public function getTranslation()
    {
        return $this->translation ?? null;
    }

    public function getTense()
    {
        return $this->tense ?? null;
    }

    public function getPerson()
    {
        return (array)$this->person;
    }

    public function getMood()
    {
        return $this->mood ?? null;
    }

    public function getTable()
    {
        $table = new Table();
        $table->table = $this->table;
        $table->setValidity();
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
        if ($word1 == false || $word1 == [] || $word2 == false || $word2 == []) return $word1 == false || $word1 == [] ? $word2 : $word1;
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
            array_push($output, $word1[$i]);
        }
        if (count($word2) > 0) $output = array_merge($output, $word2);
        return $output;
    }

    public static function Merge($words)
    {
        $n = count($words);
        for ($i = 1; $i < $n; $i++) {
            if ($words[$i - 1]->isSame($words[$i])) {
                $words[$i]->Merge($words[$i - 1]);
                unset($words[$i - 1]);
            }
        }
        return array_values($words);
    }

    public static function Pairable($words)
    {
        $n = count($words);
        //mlog($words); //two allein
        $pairable = ["noun", "adjective", "numeral", "pronoun", "preposition"];
        for ($i = 1; $i < $n; $i++) {
            if (is_array($words[0])) {
                $word1 = $words[$i][0];
                $word2 = $words[$i - 1][0];
            } else {
                $word1 = $words[$i];
                $word2 = $words[$i - 1];
            }
            $result = [];
            if (!in_array($word1->getClass(), $pairable) || !in_array($word2->getClass(), $pairable)) continue;

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
                $n = count($keys);
                for ($i = 0; $i < $n; $i++) {
                    $result[$keys[$i]] = Words::formIntersection($other->getForm(), $preposition->getWith());
                }
            } else if ($word1->getClass() != "adjective" or $word2->getClass() != "adjective")
                for ($j = 0; $j < $m; $j++) {
                    if ($word1->getClass() != "adjective" and $word2->getClass() != "adjective")
                        $result[$gender[0] . "_" . $numbers[$j]] = self::formIntersection($word1->getForm()[$numbers[$j]], $word2->getForm()[$numbers[$j]]);
                    else if ($word1->getClass() == "adjective" xor $word2->getClass() == "adjective") {
                        $adjective = $word1->getClass() == "adjective" ? $word1 : $word2;
                        $other = $word2->getClass() != "adjective" ? $word2 : $word1;
                        if (isset($adjective->getForm()[$gender[0] . "_" . $numbers[$j]]) && isset($other->getForm()[$numbers[$j]]))
                            $result[$gender[0] . "_" . $numbers[$j]] = self::formIntersection($adjective->getForm()[$gender[0] . "_" . $numbers[$j]], $other->getForm()[$numbers[$j]]);
                    }
                }
            else if (!is_null($word2->getBold())) { //if is present search by this, if not same return, else for each gender find corelation (if index isnt present continue)
                $keys = array_keys($word2->getBold());
                for ($k = 0; $k < count($keys); $k++)
                    if (array_key_exists($keys[$k], $word1->getForm()))
                        $result[$keys[$k]] = self::formIntersection($word2->getBold()[$keys[$k]], $word1->getForm()[$keys[$k]]);
            } else {
                $keys = self::formIntersection(array_keys($word1->getForm()), array_keys($word2->getForm()));
                for ($k = 0; $k < count($keys); $k++)
                    $result[$keys[$k]] = self::formIntersection($word2->getForm()[$keys[$k]], $word1->getForm()[$keys[$k]]);
            }
            if ($result == []) continue;
            if (is_array($words[$i])) {
                $words[$i - 1][0]->setBold($result);
                $words[$i][0]->setBold($result);
            } else if (!is_null($words[$i])) {
                $words[$i - 1]->setBold($result);
                $words[$i]->setBold($result);
            }
        }
        return array_values($words);
    }
    public static function formIntersection($form1, $form2)
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

    public static function decodeJSON($word)
    {
        if (isset($word["tables"])) $table = $word["tables"];
        $word = json_decode(str_replace(["'", "\n", "\r"], ["\"", "", ""], $word["json"]));
        $out = new JSONobj();
        $out->class = $word->class;
        $out->base = $word->base;
        $out->word = $word->word;
        $out->translation = $word->translation;
        if (isset($word->gender)) $out->gender = $word->gender;
        if (isset($word->form)) $out->form = $word->form;
        if (isset($word->number)) $out->number = $word->number;
        if (isset($table)) $out->table = html_entity_decode($table);
        if (isset($word->tense)) $out->tense = $word->tense;
        if (isset($word->person)) $out->person = $word->person;
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
    public static function Values($value1, $value2, $sidetoside = false)
    {
        if ($sidetoside) $value1 = [$value1, $value2];
        if (is_array($value1) && is_array($value2)) {
            $value1 = array_merge($value1, $value2);
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
