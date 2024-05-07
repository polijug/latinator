<?php
class Table
{
    public function __construct($class = "", $word = "", $lang = "")
    {
        switch ($class) {
            case "noun":
            case "pronoun":
            case "numeral":
            case "adjective":
            case "verb":
                try {
                    $table = "<table" . strip_tags(explode("</table", explode("<table", self::retrieveTable($word, $class, $lang), 2)[1], 2)[0], ["tr", "br", "th", "td", "table"]) . "</table>";
                    if (strlen($table) == 14) $this->valid = false;
                    $table = preg_replace(["/\([^)]+\)/i"/*, "/\d/i"*/], "", $table);
                    if ($lang == "en")
                        $table = Czech::TableTranslation(strtolower($table));
                    $this->table = str_replace("style", "xd", $table);
                    Database::insertTable($this->table, $class, $word);
                } catch (Exception $exception) {
                    $this->valid = false;
                }
                break;
            default:
                $this->valid = false;
                break;
        }
    }
    public $table;
    private $valid = true;

    private function toBold($word)
    {
    }

    public function getValidity()
    {
        if (isnull($this->table) || $this->table == "<table</table>")
            $this->valid = false;

        return $this->valid && !isnull($this->table) && $this->table != "";
    }
    public function setValidity()
    {
        $this->valid = true;
    }
    public static function parseSections($word, $class, $lang)
    {
        $output = API::Sections($word, $lang);
        $n = count($output);
        $latin = false;
        for ($i = 0; $i < $n; $i++) {
            if (!str_contains($output[$i]->number, ".")) {
                if ($output[$i]->line == "latina" || $output[$i]->line == "Latin") //todo latin
                    $latin = true;
                else if ($latin) $latin = false;
            } else if ($latin && ($output[$i]->line == Czech::Class($class) || $output[$i]->line == ucfirst($class))) //todo enclass
                return $output[$i]->index;
        }
        return -1;
    }
    public static function retrieveTable($word, $class, $lang)
    {
        $index = self::parseSections($word, $class, $lang);
        if ($index < 0) return;
        return API::Section($word, $index, $lang);
    }
    public static function decideTable($table0, $table1)
    {
        $table0 = isset($table0) ?? $table1;
        if ($table0 instanceof Table)
            $table0 = $table0->getValidity() ? $table0 : $table1;
        else $table0 = $table1;
        return $table0;
    }
    public static function isValid($table)
    {
        if (isnull($table->table) || $table->table == "<table</table>")
            $table->valid = false;

        return $table->valid && !isnull($table->table) && $table->table != "";
    }
}
