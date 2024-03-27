<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("log_errors", 1);
include_once('assets/words.php');
include_once("assets/czech.php");
include_once("assets/wikitext.php");
include_once("assets/standard.php");
include_once("assets/API.php");
include_once("assets/translation.php");
include_once("assets/base.php");
include_once("assets/table.php");
include_once("assets/format.php");
include_once("assets/database.php");
if (PHP_MAJOR_VERSION < 8)
    include_once("assets/compatibility.php");
/*
 * ask for sentence - frontend
 * * receive in get parameter s
 * analyse sentence
 * * cut sentence
 * ask API
 * * first wikidictionary.cz (cs)
 * * second wikidictionary.com (en)
 * * third latinissimple.com through API
 * * * for english APIs use DeepL API to translate
 * * * formulation of czech from jazykova prirucka or wikidic.cz
 * it is needed somehow receive and store table of conjunction and declination
 * for each word store infos like translation, actual person, time (passive/active) / gender, conjunction, number
 *
 */
$start = microtime(true);
const version = "0.2";
$output = new Output();

$sentence = GExisT("s"); //"s" stands for sentence

if (!$sentence) { //show main page
    die("<H1 style='color: red'> Na stránce se pracuje! </H1>");
    $output->setTitleContent("Latinátor", "<H1 style='color: red'> Na stránce se pracuje! </H1>", true);
}

/*
 * class - slovní druh
 * base
 * tense - čas (5)
 * person - osoba (5)
 * gender - rod (1, 2, 3, 4, 5)
 * number - číslo (1, 2, 3, 5)
 * form - pád (1, 2, 3, 4)
 * mood - způsob (5)
 */

$sent = new Sentence($sentence);
//MLog($sent);
$sent->Formate();


$time_elapsed_secs = microtime(true) - $start;
MLog($time_elapsed_secs, true);

//better usage
function GExisT($string)
{
    if (isset($_GET[$string]))
        return $_GET[$string];
    return false;
}

function MLog($text, $die = false)
{
    global $output;
    $output->setContent(var_export($text, true) . "<p>", $die);
}

function jsonEncode($object){
    return str_replace("\"", "'",json_encode($object, JSON_UNESCAPED_UNICODE));
}


class Sentence
{
    public $sentence;
    public $words = [];
    public $possTranslations; //translation of entire sentence
    public $count;
    public $format;

    public function __construct($sentence)
    {
        $sentence = self::Analysis($sentence);
        $this->sentence = $sentence;
        $this->count = count($sentence);
        for ($i = 0; $i < $this->count; $i++) {
            $database = Database::getWordDB($sentence[$i]);
            if($database !== false){
                array_push($this->words, $database);
                continue;
            }
            $words = WikiText::auto($sentence[$i], "cs");
            //if ($words == false || $words == array() || Words::Fullness($words) == false) { //exception in fullness - table
            $words = Words::Combine($words, WikiText::auto($sentence[$i], "en")); //todo comb array with array - pair with class
            //}
            array_push($this->words, $words);
        }
    }
    public function Formate(){

        $format = new Formating($this->words);
        $format->formatAnswer();
        $this->format = $format;
        mlog($this->format);
    }
    private static function Analysis($sentence)
    {
        $sentence = explode(" ", trim(strtolower($sentence)));
        return $sentence;
    }
}

class Output
{
    public function __construct()
    {
        $this->content = str_replace("[year]", date("Y"), file_get_contents("assets/main.html"));
    }
    public function return()
    {
        print(str_replace(["[title]", "[content]"], "", $this->content));
        die;
    }
    public function setTitle($title)
    {
        $this->content = str_replace("[title]", $title . "[title]", $this->content);
    }
    public function setContent($content, $die = false)
    {
        $this->content = str_replace("[content]", $content . "[content]", $this->content);
        if ($die) $this->return();
    }
    public function setTitleContent($title, $content, $die = false)
    {
        $this->content = str_replace(["[content]", "[title]"], [$content . "[content]", $title . "[title]"], $this->content);
        if ($die) $this->return();
    }
    private $content;
}
