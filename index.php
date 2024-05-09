<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("log_errors", 1);
set_include_path("libraries/");
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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'libraries/PHPMailer/src/Exception.php';
require 'libraries/PHPMailer/src/PHPMailer.php';
require 'libraries/PHPMailer/src/SMTP.php';

set_error_handler("error_handler", E_ERROR);
register_shutdown_function("fatal_handler");

$start = microtime(true);
const version = "0.3.1";
$output = new Output();

$sentence = GExisT("s"); //"s" stands for sentence
$definition = GExisT("d");

if (!$sentence && !$definition) { //show main page
    echo file_get_contents("assets/head.html") . file_get_contents("assets/lp.html");
    die("<H1 style='color: red'> Na stránce se pracuje! </H1>");
} else if ($sentence && !$definition) {
    $sent = new Sentence($sentence);
    $sent->Formate();
} else if (!$sentence && $definition) {
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

function str_trim($str)
{
    $lstop = false;
    $rstop = false;
    $rep = [",", ".", " "];
    for ($i = 0; $i < strlen($str) && (!$lstop || !$rstop); $i++) {
        if (in_array($str[$i], $rep) && !$lstop) {
            $str = substr($str, 1, strlen($str) - 1);
            $i--;
        } else $lstop = true;
        if (in_array($str[strlen($str) - 1], $rep) && !$rstop)
            $str = substr($str, 0, strlen($str) - 1);
        else $rstop = true;
    }
    return $str;
}

function error_handler($eN, $eMessage, $eFile, $eLine, $eContext)
{
    $eMessage = str_replace("\n", "<br>", $eMessage);
    $address = $_SERVER['REQUEST_URI'];
    $str = "Chyba $eN na adrese <a href=https://latinator.erza.cz/$address>$address</a> <br>
    v souboru: $eFile : $eLine<p>
    Chyba: <code>$eMessage</code>";
    global $output;
    email("erza@erza.cz", "erza@erza.cz", $str, "Chyba Latinatoru $eFile : $eLine");
    $output->setContent("<h1>Chyba vyhledávání slov</h1>
    Taková chyba se může vyskytnout, obzvláště na nově zavedené stránce. <p>
    Doporučuji překontrolovat zadání, nebo zkusit hledání s jiným dotazem. Chybu vykazuje většinou jen jedno ze slov. <p>
    <small>Administrátor byl upozorněn</small><p>
    <details><summary>Další informace</summary>
    $str</details>", true);
}

function email($to, $from, $text, $subject){
    $mail = new PHPMailer(true);
    $mail->setLanguage('cs', 'libraries/PHPMailer/language');
    $mail->isSMTP();

    $mail->setFrom($from, "Erža");
    $mail->addAddress($to, 'Erža');

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $text;
    $mail->send();
}

function fatal_handler()
{
    $errfile = "unknown file";
    $errstr  = "shutdown";
    $errno   = E_CORE_ERROR;
    $errline = 0;

    $error = error_get_last();

    if ($error !== NULL) {
        $errno   = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr  = $error["message"];
        $errcontext = $error["context"];

        error_handler($errno, $errstr, $errfile, $errline, $errcontext);
    }
}

class Output
{
    public function __construct()
    {
        $this->content = file_get_contents("assets/head.html") . str_replace("[year]", date("Y"), file_get_contents("assets/main.html"));
    }
    public function return()
    {
        $this->setPlaceholder();
        header('Content-type: text/html; charset=utf-8');
        print(str_replace(["[title]", "[content]"], "", $this->content));
        die;
    }
    public function setPlaceholder()
    {
        $plac = Database::randomWord();
        $str = "";
        for ($i = 0; $i < 3; $i++) {
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
