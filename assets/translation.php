<?php
class Translate
{
    public static function Go($text, $lang)
    {
        switch ($lang) {
            case "en":
                $translations = array_values(str_replace(["# l|en", "# "], "", array_filter($text, function ($val) {
                    return str_starts_with($val, "# ") && !str_contains($val, "|") || str_starts_with($val, "# l|en");
                })));
                $n = count($translations);
                $translation = [];
                for ($i = 0; $i < $n; $i++) {
                    $item = explode(";", str_replace(", ", ";", $translations[$i]));
                    $m = count($item);
                    for ($j = 0; $j < $m; $j++) {
                        if (strlen($item[$j]) < 20 && !str_contains($item[$j], " case"))
                            array_push($translation, $item[$j]);
                    }
                }
                $text = implode(";", $translation);
            $translation = explode(";", str_replace([", ", ","], [";", ";"], API::deepL(/*substr(*/$text/*, 0, 0)*/)));
                return array_values(array_unique($translation));
            case "cs":
                $text = arrays::array_name_slice($text, "=== význam ===");
                if ($text == 0)
                    return false;
                $translations = array_values(str_replace("# ", "", array_filter($text, function ($val) {
                    return str_starts_with($val, "# ");
                })));
                $translations = array_values(explode(", ", implode(", ", str_replace(["|", "/"], ", ", $translations))));
                return $translations;
        }
    }
}
