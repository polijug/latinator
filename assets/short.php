<?php
class Short
{
    public static function Form($form)
    {
        switch ($form) {
            case "nom":
                return 1;
            case "gen":
                return 2;
            case "dat":
                return 3;
            case "acc":
                return 4;
            case "voc":
                return 5;
            case "abl":
                return 6;
        }
    }

    public static function Number($num, $short = false)
    {
        if ($num == "s") {
            if ($short) return "j";
            return "jednotné";
        }
        if ($short) return "mn";
        return "množné";
    }

    public static function Gender_N($gender, $short = false)
    {
        switch ($gender) {
            case "m":
                if ($short) return "m";
                return "mužský";
            case "f":
                if ($short) return "ž";
                return "ženský";
            case "n":
                if ($short) return "s";
                return "střední";
        }
    }
    public static function Gender_V($gender, $short = false)
    {
        if ($gender == "pas") {
            if ($short) return "trp.";
            return "trpný";
        }
        if ($short) return "čin.";
        return "činný";
    }
    public static function Tense($tense, $short = false)
    {
        switch ($tense) {
            case "pres":
                if ($short) return "přít.";
                return "přítomný";
            case "impf":
                if ($short) return "min. (imp)";
                return "minulý (imperfekt)";
            case "futr":
                if ($short) return "bud.";
                return "budoucí";
            case "perf":
                if ($short) return "min. (perf)";
                return "minulý (perfekt)";
            case "plup":
                if ($short) return "předmin.";
                return "předminulý";
            case "futrperf":
                if ($short) return "předbud.";
                return "předbudoucí";
        }
    }
    public static function Mood($mood)
    {
        switch ($mood) {
            case "indc":
            case "ind":
                return "oznamovací";
            case "imp":
                return "rozkazovací";
            case "sub":
                return "podmiňovací";
            default:
                return $mood;
        }
    }
}
