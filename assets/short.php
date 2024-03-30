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

    public static function Number($num)
    {
        if ($num == "s") return "jednotné";
        return "množné";
    }

    public static function Gender_N($gender)
    {
        switch ($gender) {
            case "m":
                return "mužský";
            case "f":
                return "ženský";
            case "n":
                return "střední";
        }
    }
    public static function Gender_V($gender){
        if($gender == "pas") return "trpný";
        else return "činný";
    }
    public static function Tense($tense)
    {
        switch ($tense) {
            case "pres":
                return "přítomný";
            case "impf":
                return "minulý (imperfekt)";
            case "futr":
                return "budoucí";
            case "perf":
                return "minulý (perfekt)";
            case "plup":
                return "předminulý";
            case "futrperf":
                "předbudoucí";
        }
    }
    public static function Mood($mood)
    {
        switch ($mood) {
            case "ind":
                return ""; //oznamovací
            case "imp":
                return "rozkazovací";
            case "sub":
                return "podmiňovací";
            default:
                return $mood;
        }
    }
}
