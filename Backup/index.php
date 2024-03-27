<?php
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

 setlocale(LC_CTYPE, 'cs_CZ');

const version = "0.1";

$sentence = GExisT("s");//"s" stands for sentence

if (!$sentence) { //show main page
    readfile("main.html");
    exit;
}

$sentence = SentenceAnalysis($sentence);
$words = count($sentence);
$prosperties = array($words);
for ($i = 0; $i < $words; $i++)
    $prosperties[$i] = array("class" => null, "mood" => null, "tense" => null, "person" => null, "gender" => null, "number" => null, "form" => null, "translation" => null);
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

print_r(
wikitextParsing(csDict($sentence[0]), "cs")
)
;
//better usage


function GExisT($string)
{
    if (isset($_GET[$string]))
        return $_GET[$string];
    return false;
}

function SentenceAnalysis($sentence)
{
    $sentence = explode(" ", trim(strtolower($sentence)));
    return $sentence;
}

function csDict($word)
{
    //https://cs.wiktionary.org/w/api.php
    return json_decode(askAPI("https://cs.wiktionary.org/w/api.php", [
        "action" => "parse",
        "page" => $word,
        "prop" => "wikitext",//"sections",
        "format" => "json"
    ]));
}

function enDict($word)
{
    return json_decode(askAPI("https://en.wiktionary.org/w/api.php", [
        "action" => "parse",
        "page" => $word,
        "prop" => "wikitext",//"sections",
        "format" => "json"
    ]));
}

function latSimple()
{

}

function deepL($string)
{
    return json_decode(askAPI("https://api-free.deepl.com/v2/translate", "", [
        "text" => $string,
        "target_lang" => "CS"
    ], "Authorization: DeepL-Auth-Key 557c4ebb-5574-7578-246f-473286ba23ec:fx"))->translations[0]->text;
}

function handbook()
{

}

function wikitextParsing($wikitext, $lang, $derive = true, $class = false)
{
    $text = wikitextIsolate($wikitext);

    switch ($lang) {
        case "la":
            if (str_starts_with($text[0], "caput|la"))
                $text[0] = "basic";
            else if (str_starts_with($text[0], "caput|mul"))
                $text[0] = "another";
            else $text[0] = "null";


            if ($text[0] == "another") {

            } else if ($text[0] != "null") { //basic word
                //getting translations
                $translations = array_filter($text, function ($val) {
                    $val = str_replace(" ", "", $val);
                    return str_starts_with($val, "*cs:t+|cs") || str_starts_with($val, "*en:t+|en");
                });
                print_r($translations);
            }

            break; //deprecated
        case "en":
            $prospArr = [];
            $text = array_name_slice($text, "==Latin==", 2);
            if(!$derive)
                $text = array_name_slice($text, "===" . ucfirst($class) . "===", 3);
            //print_r($text);

            $derived = $derive;
            if($derive)
                $derived = isDerived($text); //setting first index is bad
            var_dump($derived);

            if ($derived) {
                $inflections = array_filter($text, function ($val) {
                    $val = str_replace(" ", "", $val);
                    return str_starts_with($val, "#inflectionof|la");
                });
                $inflections = array_values($inflections);
                $n = count($inflections);
                for ($i = 0; $i < $n; $i++) {
                    $start = explode("||", $inflections[$i]);
                    $inf = explode("|;|", $start[1]);
                    for ($j = 0; $j < count($inf); $j++){
                        $item = parseInflections($start[0] . "||".$inf[$j]);
                        $item["translation"] = wikitextParsing(enDict($item["base"]), "en", false, $item["class"])[0];
                        array_push($prospArr, $item);
                    }
                }
                print_r($prospArr);
            }

            $translations = array_values(str_replace(["# l|en", "# "], "", array_filter($text, function ($val) {
                return str_starts_with($val, "# ") && str_contains($val, "|") != true || str_starts_with($val, "# l|en");
            })));
            $n = count($translations);
            $text = "";
            for($i = 0; $i < $n; $i++)
                $text .= $translations[$i] . ";";
            $translations = explode(";", deepL($text));

            print_r($translations);

            return [$translations, $prospArr];
            //dont know, if translating verb or noun
        case "cs":
            $text = array_name_slice($text, "== latina ==", 2);
            if(!$derive)
                $text = array_name_slice($text, "=== " . ucfirst(czechClass($class)) . " ===", 3);
            
            $derived = $derive;
            if($derive)
                $derived = isDerived($text);

            if($derived){
                $inflections = array_values(array_filter($text, function ($val) {
                    $val = str_replace(" ", "", $val);
                    return str_starts_with($val, "#''");
                }));
                $n = count($inflections);
            }
            print_r($inflections);
            return $text;
            break;
    }
}

function czechClass($class){
    switch($class){
        case "verb":
            return "sloveso";
        case "noun":
            return "podstatné jméno";
        case "adjective":
            return "přídavné jméno";
        default:
            return $class;
    }
}

function parseInflections($info)
{
    $info = str_replace("# inflection of|la|", "", $info);
    $info = explode("|", $info);

    $base = iconv('utf-8', 'ascii//TRANSLIT', $info[0]);
    if (is_numeric($info[2])) {
        $class = "verb";
        $person = (int)$info[2];
        $number = $info[3];
        $tense = $info[4];
        $gender = $info[5]; //active, pasive
        $mood = $info[6]; //způsob
    } else if($info[2] == "pres"){
        $class = "verb";
        $tense = $info[2];
        $gender = $info[3];
        $mood = $info[4];
    } else if($info[3] == "f" || $info[3] == "n" || $info[3] == "m"){
        $class = "adjective";
        $form = $info[2];
        $gender = $info[3];
        $number = $info[4];
    }
    else {
        $class = "noun";
        $form = $info[2];
        $number = $info[3];
    }
    $prosperties = array("class" => $class, "base" => $base, "tense" => $tense, "person" => $person, "gender" => $gender, "number" => $number, "form" => $form, "mood" => $mood);
    return $prosperties;
}

function isDerived($array)
{
    //isn't completely accurate, some words have more uses
    for ($i = 2; $i < count($array); $i++) {
        if (str_starts_with($array[$i], "head|la")) return true;
    }
    return false;
}

function wikitextIsolate($answer)
{
    $text = ((array)$answer->parse->wikitext)["*"];
    return explode("\n", str_replace(["\n\n"], "\n", str_replace(["{", "}", "[", "]"], "", $text)));
}

function askAPI($url, $GET = "", $POST = "", $head = "")
{
    $url = $url . "?" . http_build_query($GET);
    if ($POST != "") $POST = http_build_query($POST);
    return load($url, $POST != "", $POST, $head);
}

function load($url, $POST = false, $data = "", $head = "")
{
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    if ($POST) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    $headers = [
        'Accept-Language: en-US, cs-CZ, en-GB',
        'Cache-Control: no-cache',
        'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
        'Referer: https://latinator.erza.cz',
        "User-Agent: Latinator/" . version . " (erza@erza.cz)",
        $head
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    if (curl_error($ch))
        throw new Exception(curl_error($ch));
    curl_close($ch);
    return $response;
}

function array_name_slice($array, $startname, $fromEnd = 0)
{
    $i = -1;
    do{
        if($i>-1)
            $startname = "=$startname=";
        $index = array_search($startname, $array);
        $i++;
    } while($index === false && $i < 4);
    if($i == 4) return;

    array_splice($array,0, $index);

    if($fromEnd) {
        $fromEnd += $i;
        switch($fromEnd){
            case 2:
                $regex = "/^==(?:(?!=).)+==$/i";
                break;
            case 3:
                $regex = "/^===(?:(?!=).)+===$/i";
                break;
            case 4:
                $regex = "/^====(?:(?!=).)+====$/i";
                break;
            case 5:
                $regex = "/^=====(?:(?!=).)+=====$/i";
                break;
        }

        array_splice($array, array_find_first_occurence($array, $regex, $startname));
    }
    return $array;
}

function array_find_first_occurence($array, $regex, $not){
    $n = count($array);
    for($i = 0; $i< $n; $i++)
        if(preg_match($regex, $array[$i]) && $array[$i] != $not)
            return $i;
    return $n;
}

//Compatibility with 8.0
function str_starts_with($haystack, $needle)
{
    return substr($haystack, 0, strlen($needle)) === $needle;
}

function str_contains($haystack, $needle)
{
    return $needle !== '' && mb_strpos($haystack, $needle) !== false;
}