<?php
class API
{
    public static function csDict($word)
    {
        return self::DictPreparse(self::Dict($word, "cs"));
    }

    public static function enDict($word)
    {
        return self::DictPreparse(self::Dict($word, "en"));
    }

    public static function Sections($word, $lang)
    {
        return json_decode(self::askAPI("https://" . $lang . ".wiktionary.org/w/api.php", [
            "action" => "parse",
            "page" => $word,
            "prop" => "sections",
            "format" => "json"
        ]))->parse->sections;
    }
    public static function Section($word, $index, $lang)
    {
        return self::DictPreparse(json_decode(self::askAPI("https://" . $lang . ".wiktionary.org/w/api.php", [
            "action" => "parse",
            "page" => $word,
            "prop" => "text",
            "section" => $index,
            "disablelimitreport" => true,
            "disableeditsection" => true,
            "sectionpreview" => true,
            "disabletoc" => true,
            "format" => "json"
        ])), false);
    }

    private static function Dict($word, $lang, $text = false)
    {
        $wikitext = $text ? "text" : "wikitext";
        return json_decode(self::askAPI("https://" . $lang . ".wiktionary.org/w/api.php", [
            "action" => "parse",
            "page" => $word,
            "prop" => $wikitext, //"sections",
            "format" => "json"
        ]));
    }
    private static function DictPreparse($object, $wikitext = true)
    {
        if (!isset($object->parse)) return "";
        if ($wikitext)
            $i = $object->parse->wikitext;
        else
            $i = $object->parse->text;
        $i = (array)$i;
        return $i["*"];
    }

    public static function latSimple()
    {
    }

    public static function deepL($string)
    {
        return json_decode(self::askAPI("https://api-free.deepl.com/v2/translate", [], [
            "text" => $string,
            "target_lang" => "CS"
        ], "Authorization: DeepL-Auth-Key 557c4ebb-5574-7578-246f-473286ba23ec:fx"))->translations[0]->text;
    }

    public static function handbook()
    {
    }

    protected static function askAPI($url, $GET = [], $POST = [], $head = "")
    {
        $url = $url . "?" . http_build_query($GET);
        if ($POST != "")
            $POST = http_build_query($POST);
        return self::load($url, $POST != "", $POST, $head);
    }

    protected static function load($url, $POST = false, $data = "", $head = "")
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if ($POST) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $headers = [
            'Accept-Language: en-US, cs-CZ, en-GB',
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
}
