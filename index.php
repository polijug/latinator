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
include_once("assets/sentence.php");
include_once("assets/short.php");
include_once("assets/definition.php");
if (PHP_MAJOR_VERSION < 8)
    include_once("assets/compatibility.php");

$start = microtime(true);
const version = "0.2";
$output = new Output();

$sentence = GExisT("s"); //"s" stands for sentence
$definition = GExisT("d");

if (!$sentence && !$definition) { //show main page
    die("<H1 style='color: red'> Na stránce se pracuje! </H1>");
} else if($sentence && !$definition){
    $sent = new Sentence($sentence);
    $sent->Formate();
} else if (!$sentence && $definition){
    $def = new Definition($definition);
    $def->print();
} else {
    //chyba
}



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
    $text = is_string($text) ? $text : var_export($text, true);
    $output->setContent($text . "<p>", $die);
}

function jsonEncode($object)
{
    if (is_array($object))
        return str_replace(['"', "''", ",,"], ["'", "", ","], json_encode($object, JSON_UNESCAPED_UNICODE));
    return "'" . str_replace(["'", '"'], "", $object) . "'";
}

function isnull($object): bool
{
    return is_null($object) || $object == [] || $object == "";
}

function str_trim($str){
    $lstop = false;
    $rstop = false;
    $rep = [",", ".", " "];
    for($i = 0; $i < strlen($str) && (!$lstop || !$rstop); $i++){
        if(in_array($str[$i], $rep) && !$lstop){
            $str = substr($str, 1, strlen($str) - 1);
            $i--;
        } else $lstop = true;
        if(in_array($str[strlen($str) - 1], $rep) && !$rstop)
            $str = substr($str, 0, strlen($str) - 1);
        else $rstop = true;
    }
    return $str;
}

class Output
{
    public function __construct()
    {
        $this->content = str_replace("[year]", date("Y"), file_get_contents("assets/main.html"));
    }
    public function return()
    {
        $this->setPlaceholder();
        header('Content-type: text/html; charset=utf-8');
        print(str_replace(["[title]", "[content]"], "", $this->content));
        die;
    }
    public function setPlaceholder(){
        $plac = Database::randomWord();
        $str = "";
        for($i = 0; $i < 3; $i++){
            $str .= $plac[$i][0];
            $str .= $i < 2 ? ", " : "...";
        }
        $this->content = str_replace("[placeholder]", $str, $this->content);
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
