<?php
class Sentence
{
	public $sentence;
	public $words = [];
	public $count;
	public $format;
	public $rank;

	public function __construct($sentence)
	{
		global $output;
		$output->setTitle($sentence);
		$sentence = self::Analysis($sentence);
		$this->sentence = $sentence;
		$this->count = count($sentence);
		for ($i = 0; $i < $this->count; $i++) {
			$database = Database::getWordDB($sentence[$i]);
            $valid = Database::valid($sentence[$i]);
			if ($database && $valid) {
				$this->words[] = $database;
				continue;
			}
			$words = WikiText::auto($sentence[$i], "cs");
			if ($words)
				$words = Words::Combine($words, WikiText::auto($sentence[$i], "en"));
			else $words = WikiText::auto($sentence[$i], "en");
			if($words == false) $words = [];
			$this->words[] = $words;
		}
	}
	public function Formate()
	{
		$format = Formating::Formate($this->words);
		$this->format = $format->format;
		$this->words = $format->word;
		$this->Decide();
		$this->Print();
	}
	private static function Analysis($sentence)
	{
		return explode(" ", lcfirst(str_trim($sentence)));
	}
	private function Print()
	{
		$words = $this->words;
		$n = count($words);
		$btn = "<div class='words'>";
		$body = "";
		for ($i = 0; $i < $n; $i++) {
			$format = $this->format[$i];
			$short = $format["short"];
			$style = $i == 0 ? "style='background-color: #017a8a'" : "";
			if(isnull($words[$i][0])){
				$word = $this->sentence[$i];
				$btn .= "<div class='word' id='$word' $style tabindex=$i><b>$word</b><br>slovo neexistuje</div>";
				$body .= "<item id='".$word . "_body'><p><b>Slovo nenalezeno ve slovníku.</b></p></item>";
				continue;
			}
			$word = $words[$i][0]->getWord();
			$btn .= "<div class='word' id='$word' tabindex=$i $style title='" . $short[1] . "'>" . $short[2] . "</div>";
			$body .= "<item id='" . $word . "_body'><p>" . $short[0] . "</p>";
			$body .= $format["long"];
			if (count($format["other"]) > 0) {
				$body .= "<p><details><summary>Další možnosti</summary>";
				foreach ($format["other"] as $oth)
					$body .= $oth;
				$body .= "</details>";
			}
			$body .= "</item>";
		}
		$btn .= "</div>";
		global $output;
		$output->setContent($btn . $body, true);
	}
	private function Decide()
	{
		$output = [];
		$n = count($this->words);
		$firstPerson = -1; //0 noun; 1,2,3 pronoun, 4 empty pronoun
		$firstNumber = "";
		for ($i = 0; $i < $n; $i++) {
			$m = count($this->words[$i]);
			if(isnull($this->words[$i][0])) continue;
			$end = false;
			$candidate = "";
			$obey = false;
			$shape = $this->words[$i][0]->getWord();
			for ($j = 0; $j < $m && !$end; $j++) {
				$word = $this->words[$i][$j];
				switch ($word->getClass()) {
					case "noun":
						$val = 3;
					case "adjective":
						$val = $val ?? 2;
					case "numeral":
						$val = $val ?? 3;
					case "pronoun":
						$val = $val ?? 4;
						if ($candidate == "" || explode("_", $candidate)[0] < $val)
							$candidate = $val . "_" . $j;
						if ($i == 0 && $word->getClass() == "noun" || $word->getClass() == "pronoun") {
							$keys = array_keys($word->getForm());
							$o = count($keys);
							for ($k = 0; $k < $o && !$end; $k++) {
								if (Words::formIntersection($word->getForm()[$keys[$k]], "nom")[0] == "nom") {
									$shortW = new Noun(
										$word->getWord(),
										$word->getBase(),
										"nom",
										$keys[$k],
										$word->getGender(),
										$word->getDeclination(),
										$word->getTranslation()
									);
									$shortW->class = $word->getClass();
									$long = $this->format[$shape][$j];

									$end = true;
									if ($word->getClass() == "pronoun") {
										$firstPerson = $word->getPerson() != null ? $word->getPerson() : 4;
									}
									$firstPerson = $word->getClass() == "noun" ? 0 : $firstPerson;
									$firstNumber = $word->getNumber();
									unset($this->format[$shape][$j]);
								}
							}
						}
						if (!$end) {
							if ($word->getBold() != null) {
								$key = array_keys($word->getBold())[0];
								$shortW = new Noun(
									$word->getWord(),
									$word->getBase(),
									$word->getBold()[$key][0],
									$key[strlen($key) - 1],
									$key[0],
									$word->getDeclination(),
									$word->getTranslation()
								);
								$shortW->class = $word->getClass();
								$long = $this->format[$shape][$j];

								unset($this->format[$shape][$j]);
								$end = true;
							} else {
								$keys = array_keys($word->getForm());
								$o = count($keys);
								for ($k = 0; $k < $o && !$end; $k++) {
									$arr = Words::formIntersection($word->getForm()[$keys[$k]], ["nom", "acc"]);
									if ($obey || in_array("acc", $arr) || in_array("nom", $arr)) {
										//možná špatné pořadí
										$form = in_array("acc", $arr) ? "acc" : "nom";
										if ($obey)
											$form = $word->getForm()[$keys[$k]][0];
										if (is_array($word->getGender())) $gender = $keys[$k][0];
										else $gender = $word->getGender();
										$shortW = new Noun(
											$word->getWord(),
											$word->getBase(),
											$form,
											$keys[$k],
											$gender,
											$word->getDeclination(),
											$word->getTranslation()
										);
										$shortW->class = $word->getClass();
										$long = $this->format[$shape][$j];

										unset($this->format[$shape][$j]);
										$end = true;
									}
								}
							}
						}
						break;
					case "verb":
						if ($candidate == "" || explode("_", $candidate)[0] < 2)//podmínka, ale jaká?
							$candidate = 2 . "_" . $j;
						if ($i == $n - 1 && $firstPerson != -1) {
							$keys = array_keys($word->getPerson());
							$o = count($keys);
							for ($k = 0; $k < $o; $k++) {
								$gender = substr($keys[$k], 0, 3);
								$person = $word->getPerson()[$keys[$k]];
								if ($firstNumber == $keys[$k][4] && ($firstPerson == 0 && Words::formIntersection($person, 3)[0] == 3 || $firstPerson > 0 && (Words::formIntersection($person, $firstPerson) != [] || $firstPerson == 4))) {
									if ($firstPerson == 0)
										$pers = 3;
									else if ($firstPerson == 4) $pers = Words::formIntersection($person, [1, 2, 3])[0];
									else $pers = Words::formIntersection($person, $firstPerson)[0];

									$shortW = new Verb(
										$word->getWord(),
										$word->getBase(),
										$firstNumber,
										$word->getTense(),
										$pers,
										$gender,
										$word->getMood(),
										$word->getConjugation(),
										$word->getTranslation()
									);
									$long = $this->format[$shape][$j];

									unset($this->format[$shape][$j]);
									$end = true;
								}
							}
						}
						if (!$end) {
							$mood = Words::formIntersection($word->getMood(), ["indc", "inf"]);
							$tense = Words::formIntersection($word->getTense(), ["pres", "impf", "futr"]);
							if ($obey || Words::formIntersection($word->getGender(), "act")[0] == "act") {
								if ($mood != [] || $obey)
									if ($tense != [] || $obey) {
										$number = is_array($word->getNumber()) ? $word->getNumber()[0] : $word->getNumber();
										$person = $word->getPerson()["act_$number"];
										$person = is_array($person) ? $person[0] : $person;
										$gender = "act";
										if ($obey) {
											$tense = [$word->getTense()];
											$mood = [$word->getMood()];
											$key = array_keys($word->getPerson())[0];
											$person = $word->getPerson()[$key];
											$gender = substr($key, 0, 3);
										}
										$shortW = new Verb(
											$word->getWord(),
											$word->getBase(),
											$number,
											$tense[0],
											$person,
											$gender,
											$mood[0],
											$word->getConjugation(),
											$word->getTranslation()
										);
										$long = $this->format[$shape][$j];
										unset($this->format[$shape][$j]);
										$end = true;
									}
							}
						}
						break;
					case "preposition":
						$bold = $word->getBold();
						if ($bold != null) {
							$keys = array_keys($bold)[0];
							$shortW = new Preposition($word->getWord(), $word->getBase(), $bold[$keys], $word->getTranslation());
							$long = $this->format[$shape][$j];
							$end = true;
							unset($this->format[$shape][$j]);
						} else if ($obey) {
							$with = $word->getWith();
							$with = is_array($with) ? $with[0] : $with;
							$shortW = new Preposition($word->getWord(), $word->getBase(), $with, $word->getTranslation());
							$long = $this->format[$shape][$j];
							$end = true;
							unset($this->format[$shape][$j]);
						}
						break;
					case "connective":
						$val = 1;
					default:
					    $val = !isset($val) ? 0 : $val;
						if ($candidate == "" || explode("_", $candidate)[0] < $val)
							$candidate = $val . "_$j";
						if ($obey) {
							$shortW = new Connective($word->getBase(), $word->getTranslation());
							$shortW->class = $word->getClass();
							$end = true;
							$long = $this->format[$shape][$j];
							unset($this->format[$shape][$j]);
						}
						break;
				}
				if ($j == $m - 1 && !$end) {
					$j = explode("_", $candidate)[1] - 1;
					$obey = true;
				}
				$val = null;
			}
			$short = false;
			if ($end && isset($shortW)) {
				$short = self::FormateShort($shortW);
				$output[$i] = [
					"short" => $short,
					"long" => $long,
					"other" => $this->format[$shape]
				];
			}
		}
		$this->format = $output;
	}
	private static function FormateShort($word)
	{
		switch ($word->getClass()) {
			case "noun":
			case "adjective":
			case "numeral":
			case "pronoun":
				$formN = $word->getForm();
				$formN = Short::Form($formN[array_keys($formN)[0]]);
				$form = $formN . ". pád ";
				$translation = $word->getTranslation()[0] . " - ";
				$translation = $translation == " - " ? "" : $translation;
				$number = "čísla " . Short::Number($word->getNumber()) . "ho, ";
				$gender = !isnull($word->getGender()) ? "rod " . Short::Gender_N($word->getGender()) . ", " : "";
				$str = $translation . $form . $number . $gender . Czech::Class($word->getClass());
				$gender = "rod " . Short::Gender_N($word->getGender(), true) . "., ";
				$tooltip = "$formN. p., č. " . Short::Number($word->getNumber(), true) . "., " . $gender . Czech::Class($word->getClass());
				break;
			case "verb":
				$person = $word->getPerson();
				$person = $person[array_keys($person)[0]];
				$translation = $word->getTranslation()[0] . " - ";
				$translation = $translation == " - " ? "" : $translation;
				$str = $translation . "$person. osoba čísla " . Short::Number($word->getNumber()) . "ho, čas " . Short::Tense($word->getTense()) .
					", způsob " . Short::Mood($word->getMood()) . ", rod " . Short::Gender_V($word->getGender()) . ", " . Czech::Class($word->getClass());
				$tooltip = "$person. os., č. " . Short::Number($word->getNumber(), true) . "., čas " . Short::Tense($word->getTense(), true) .
					", zp. " . substr(Short::Mood($word->getMood()), 0, 3) . "., rod " . Short::Gender_V($word->getGender(), true) . ", " . Czech::Class($word->getClass());
				break;
			case "preposition":
				$str = $word->getTranslation()[0] . " - s " . Short::Form($word->form) . ". pádem, " . Czech::Class($word->getClass());
				$tooltip = "s " . Short::Form($word->form) . ". p., " . Czech::Class($word->getClass());
				break;
			default:
				$str = $word->getTranslation()[0] . " - " . Czech::Class($word->getClass());
				$tooltip = Czech::Class($word->getClass());
				break;
		}
		$button = "<b>" . $word->getWord() . "</b><br>" . $word->getTranslation()[0];
		return [
			$str,
			$tooltip,
			$button
		];
	}
}
