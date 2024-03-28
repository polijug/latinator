<?php
class Czech
{
    public static function Class($class)
    {
        switch ($class) {
            case "verb":
                return "sloveso";
            case "noun":
                return "podstatné jméno";
            case "adjective":
                return "přídavné jméno";
            case "pronoun":
                return "zájmeno";
            case "numeral":
                return "číslovka";
            case "adverb":
                return "příslovce";
            case "connective":
                return "spojka";
            default:
                return $class;
        }
    }
    public static function Person($person, $invert = false)
    {
        if (!$invert)
            switch ($person) {
                case "první":
                    return 1;
                case "druhá":
                    return 2;
                case "třetí":
                    return 3;
            }
        else
            switch ($person) {
                case 1:
                    return "první";
                case 2:
                    return "druhá";
                case 3:
                    return "třetí";
            }
    }
    public static function Form($form)
    {
        switch ($form) {
            case "nom":
                return "nominativ";
            case "gen":
                return "genitiv";
            case "dat":
                return "dativ";
            case "acc":
                return "akusativ";
            case "abl":
                return "ablativ";
            case "voc":
                return "vokativ";
        }
    }
    public static function Tense($tense)
    {
        switch ($tense) {
            case "pres":
                return "prézent";
            case "impf":
                return "imperfekt";
            case "futr":
                return "furuturum";
            case "perf":
                return "perfektum";
            case "plup":
                return "plusquamperfektum";
            case "futrperf":
                "futurum perfektum";
        }
    }
    public static function Gender($gender)
    {
        if ($gender == null) return "";
        switch ($gender) {
            case "inf":
                return "infinitiv";
            case "m":
                return "maskulin";
            case "n":
                return "neutr";
            case "f":
                return "feminin";
        }
    }
    public static function Mood($mood)
    {
        if ($mood == "actv") return "activ";
        else if ($mood == "inf") return "infinitiv";
        else return "pasiv";
    }
    public static function Number($num)
    {
        if ($num == "s") return "singulár";
        else return "plurál";
    }
    public static function FormToEn($form)
    {
        switch ($form) {
            case "aku":
                return "acc";
            case "vok":
                return "voc";
            default:
                return $form;
        }
    }
    public static function TenseToEn($tense)
    {
        switch ($tense) {
            case "plus":
                return "plup";
            case "impe":
                return "impf";
            default:
                return $tense;
        }
    }

    public static function GenderToEn($gender)
    {
        if ($gender == "akt") return "act";
    }

    public static function TableTranslation($table)
    {
        $translations = [
            "conjugation" => "konjugace", "first" => "první", "second" => "druhá", "third" => "třetí", "masc." => "Mužský", "fem." => "Ženský", "neut." => "Střední",
            "singular" => "Singulár", "plural" => "Plurál", "masculine" => "Mužský", "feminine" => "Ženský", "neuter" => "Střední",
            "number" => "Číslo", "person" => "Osoba", "gender" => "Rod", "nominative" => "Nominativ", "genitive" => "Genitiv", "dative" => "Dativ", "accusative" => "Akusativ",
            "ablative" => "Ablativ", "vocative" => "Vokativ", "case" => "Pád", "indicative" => "indikativ", "active" => "aktivum", "passive" => "pasivum", "present" => "prézent",
            /*"imperfect" => "imperfektum",*/ "future" => "futurum", "perfect" => "perfektum", "pluperfekt" => "plusquamperfekt", "of" => "od", "subjunctive" => "konjunktiv",
            "imperative" => "imperativ", "infinitives" => "infinitivy", "participles" => "participia", "verbal nouns" => "zpodstatnělá slovesa", "gerund" => "podstatné jméno",
            "supine" => "přídavné jméno", "non-finite forms" => "neurčité slovesné tvary"
        ];
        $search = array_keys($translations);
        $replace = array_values($translations);
        return str_replace($search, $replace, $table);
    }
}
