<?php
class Translate
{
    public static function Go($text, $lang)
    {
        switch ($lang) {
            case "en":
                $translations = array_values(str_replace(["# l|en", "# "], "", array_filter($text, function ($val) {
                    return str_starts_with($val, "# ") && (!str_contains($val, "|") || str_contains($val, "l|en|")) || str_contains($val, "{") && str_starts_with($val, "# ");
                })));
                $n = count($translations);
                $translation = [];
                for ($i = 0; $i < $n; $i++) {
                    $item = preg_replace("/(\{[a-z]\|[a-z]{2}\||\{[^}]+}|})/i", "", $translations[$i]);
                    $item = str_replace([";", "l|en|", " ,", "#English|"], [",", "", ","], $item);
                    if (!str_contains($item, " case"))
                        $translation[] = str_trim($item);
                }
                $text = implode(";", $translation);
                $translation = explode(";", API::deepL($text));
                for ($i = 0; $i < count($translation); $i++) 
                    $translation[$i] = str_trim(implode(", ", array_values(array_unique(explode(", ", $translation[$i])))));
                return array_values(arrays::remove_null(array_unique($translation)));
            case "cs":
                if (WikiText::Derived($text, "cs")) return [];
                $text = arrays::array_name_slice($text, "=== význam ===");
                if ($text == 0)
                    return false;
                $translations = array_values(str_replace("# ", "", array_filter($text, function ($val) {
                    return str_starts_with($val, "# ");
                })));
                $n = count($translations);
                for ($i = 0; $i < $n; $i++) {
                    $translations[$i] = str_trim(preg_replace("/(\{[a-z]\|[a-z]{2}\||\{[^}]+}|})/i", "", $translations[$i]));
                }

                return arrays::remove_null(array_values(explode(", ", implode(", ", str_replace(["|", "/"], ", ", $translations)))));
        }
    }
}
