<?php

class WM_Keywords {

	//declare variables
	//the site contents
	public $contents;
	public $encoding;
	//the generated keywords
	public $keywords;
	//minimum word length for inclusion into the single word
	//metakeys
	public $wordLengthMin = 4;
	public $wordOccuredMin = 3;
	//minimum word length for inclusion into the 2 word
	//phrase metakeys
	public $word2WordPhraseLengthMin = 3;
	public $phrase2WordLengthMinOccur = 3;
	//minimum word length for inclusion into the 3 word
	//phrase metakeys
	public $word3WordPhraseLengthMin = 3;
	//minimum phrase length for inclusion into the 2 word
	//phrase metakeys
	public $phrase2WordLengthMin = 10;
	public $phrase3WordLengthMinOccur = 2;
	//minimum phrase length for inclusion into the 3 word
	//phrase metakeys
	public $phrase3WordLengthMin = 2;

	public function __construct($params, $encoding = 'utf-8') {
		//get parameters
		$this->encoding = $encoding;
		mb_internal_encoding($encoding);
		$this->contents = $this->replace_chars($params['content']);

		// single word
		if(isset($params['min_word_length'])) {
			$this->wordLengthMin = $params['min_word_length'];
		}
		if(isset($params['min_word_occur'])) {
			$this->wordOccuredMin = $params['min_word_occur'];
		}

		// 2 word phrase
		if(isset($params['min_2words_length'])) {
			$this->word2WordPhraseLengthMin = $params['min_2words_length'];
		}
		if(isset($params['min_2words_phrase_length'])) {
			$this->phrase2WordLengthMin = $params['min_2words_phrase_length'];
		}
		if(isset($params['min_2words_phrase_occur'])) {
			$this->phrase2WordLengthMinOccur = $params['min_2words_phrase_occur'];
		}

		// 3 word phrase
		if(isset($params['min_3words_length'])) {
			$this->word3WordPhraseLengthMin = $params['min_3words_length'];
		}
		if(isset($params['min_3words_phrase_length'])) {
			$this->phrase3WordLengthMin = $params['min_3words_phrase_length'];
		}
		if(isset($params['min_3words_phrase_occur'])) {
			$this->phrase3WordLengthMinOccur = $params['min_3words_phrase_occur'];
		}

		//parse single, two words and three words

	}
	
	public static function translate($string) {
			$string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
			$cir = array('/а/','/б/','/в/','/г/','/д/','/е/','/ж/','/з/','/и/','/й/','/к/',
					    '/л/','/м/','/н/','/о/','/п/','/р/','/с/','/т/','/у/','/ф/','/х/','/ц/','/ч/','/ш/','/щ/',
					    '/ъ/','/ь/','/ю/','/я/','/А/','/Б/','/В/','/Г/','/Д/','/Е/','/Ж/','/З/','/И/','/Й/','/К/',
					    '/Л/','/М/','/Н/','/О/','/П/','/Р/','/С/','/Т/','/У/','/Ф/','/Х/','/Ц/','/Ч/','/Ш/','/Щ/',
					    '/Ъ/','/Ь/','/Ю/','/Я/');
	    
	        $lat = array('a','b','v','g','d','e','j','z','i','i','k',
					    'l','m','n','o','p','r','s','t','u','f','h','c','ch','sh','sht',
					    'a','y','iu','a',
					    'A','B','V','G','D','E','J','Z','I','I','K',
					    'L','M','N','O','P','R','S','T','U','F','H','C','Ch','Sh','Sht',
					    'A','Y','Iu','A');
	        
	        $string = preg_replace($cir, $lat, $string);
        return $string;
	}

	public function get_keywords() {
		$keywords = $this->parse_words().$this->parse_2words().$this->parse_3words();
		return mb_substr($keywords, 0, -2);
	}
	
	public function get_keywords_lat() {
		return self::translate($this->get_keywords());
	}

	//turn the site contents into an array
	//then replace common html tags.
	public function replace_chars($content) {
		//convert all characters to lower case
		$content = mb_strtolower($content);
		//$content = mb_strtolower($content, "UTF-8");
		$content = strip_tags($content);

      //updated in v0.3, 24 May 2009
		$punctuations = array(',', ')', '(', '.', "'", '"',
		'<', '>', '!', '?', '/', '-',
		'_', '[', ']', ':', '+', '=', '#',
		'$', '&quot;', '&copy;', '&gt;', '&lt;', 
		'&nbsp;', '&trade;', '&reg;', ';', 
		'”', '„','&rdquo;','&bdquo;','&rdquo','&bdquo','&ndash','&ndash;',
		chr(10), chr(13), chr(9));

		$content = str_replace($punctuations, " ", $content);
		// replace multiple gaps
		$content = preg_replace('/ {2,}/si', " ", $content);

		return $content;
	}

	//single words META KEYWORDS
	public function parse_words() {
		//list of commonly used words
		// this can be edited to suit your needs
		$common = array("able", "about", "above", "act", "add", "afraid", "after", "again", "against", "age", "ago", "agree", "all", "almost", "alone", "along", "already", "also", "although", "always", "am", "amount", "an", "and", "anger", "angry", "animal", "another", "answer", "any", 
						"appear", "apple", "are", "arrive", "arm", "arms", "around", "arrive", "as", "ask", "at", "attempt", "aunt", "away", "back", "bad", "bag", "bay", "be", "became", "because", "become", "been", "before", "began", "begin", "behind", "being", "bell", "belong", "below", 
						"beside", "best", "better", "between", "beyond", "big", "body", "bone", "born", "borrow", "both", "bottom", "box", "boy", "break", "bring", "brought", "bug", "built", "busy", "but", "buy", "by", "call", "came", "can", "cause", "choose", "close", "close", "consider", 
						"come", "consider", "considerable", "contain", "continue", "could", "cry", "cut", "dare", "dark", "deal", "dear", "decide", "deep", "did", "die", "do", "does", "dog", "done", "doubt", "down", "during", "each", "ear", "early", "eat", "effort", "either", "else", "end", 
						"enjoy", "enough", "enter", "even", "ever", "every", "except", "expect", "explain", "fail", "fall", "far", "fat", "favor", "fear", "feel", "feet", "fell", "felt", "few", "fill", "find", "fit", "fly", "follow", "for", "forever", "forget", "from", "front", "gave", "get", 
						"gives", "goes", "gone", "good", "got", "gray", "great", "green", "grew", "grow", "guess", "had", "half", "hang", "happen", "has", "hat", "have", "he", "hear", "heard", "held", "hello", "help", "her", "here", "hers", "high", "hill", "him", "his", "hit", "hold", "hot", 
						"how", "however", "I", "if", "ill", "in", "indeed", "instead", "into", "iron", "is", "it", "its", "just", "keep", "kept", "knew", "know", "known", "late", "least", "led", "left", "lend", "less", "let", "like", "likely", "likr", "lone", "long", "look", "lot", "make", 
						"many", "may", "me", "mean", "met", "might", "mile", "mine", "moon", "more", "most", "move", "much", "must", "my", "near", "nearly", "necessary", "neither", "never", "next", "no", "none", "nor", "not", "note", "nothing", "now", "number", "of", "off", "often", "oh", 
						"on", "once", "only", "or", "other", "ought", "our", "out", "please", "prepare", "probable", "pull", "pure", "push", "put", "raise", "ran", "rather", "reach", "realize", "reply", "require", "rest", "run", "said", "same", "sat", "saw", "say", "see", "seem", "seen", "self", 
						"sell", "sent", "separate", "set", "shall", "she", "should", "side", "sign", "since", "so", "sold", "some", "soon", "sorry", "stay", "step", "stick", "still", "stood", "such", "sudden", "suppose", "take", "taken", "talk", "tall", "tell", "ten", "than", "thank", "that", 
						"the", "their", "them", "then", "there", "therefore", "these", "they", "this", "those", "though", "through", "till", "to", "today", "told", "tomorrow", "too", "took", "tore", "tought", "toward", "tried", "tries", "trust", "try", "turn", "two", "under", "until", "up", 
						"upon", "us", "use", "usual", "various", "verb", "very", "visit", "want", "was", "we", "well", "went", "were", "what", "when", "where", "whether", "which", "while", "white", "who", "whom", "whose", "why", "will", "with", "within", "without", "would", "yes", "yet", "you", 
						"young", "your", "br", "img", "p","lt", "gt", "quot", "copy", "&bdquo;", "&rdquo;","rdquo","bdquo",
						"могат", "възраст", "и", "или", "друг", "на", "са", "с", "да", "не", "своите", "години","предлага", "гости", "гостите", "гост", "няма", "гкпп", "изисквания");
		
		//create an array out of the site contents
		$s = split(" ", $this->contents);
		//initialize array
		$k = array();
		//iterate inside the array
		foreach( $s as $key=>$val ) {
			//delete single or two letter words and
			//Add it to the list if the word is not
			//contained in the common words list.
			if(mb_strlen(trim($val)) >= $this->wordLengthMin  && !in_array(trim($val), $common)  && !is_numeric(trim($val))) {
				$k[] = trim($val);
			}
		}
		//count the words
		$k = array_count_values($k);
		//sort the words from
		//highest count to the
		//lowest.
		$occur_filtered = $this->occure_filter($k, $this->wordOccuredMin);
		arsort($occur_filtered);

		$imploded = $this->implode(", ", $occur_filtered);
		//release unused variables
		unset($k);
		unset($s);

		return $imploded;
	}

	public function parse_2words() {
		//create an array out of the site contents
		$x = split(" ", $this->contents);
		//initilize array

		$y = array();
		for ($i=0; $i < count($x)-1; $i++) {
			//delete phrases lesser than 5 characters
			if( (mb_strlen(trim($x[$i])) >= $this->word2WordPhraseLengthMin ) && (mb_strlen(trim($x[$i+1])) >= $this->word2WordPhraseLengthMin) )
			{
				$y[] = trim($x[$i])." ".trim($x[$i+1]);
			}
		}

		//count the 2 word phrases
		$y = array_count_values($y);

		$occur_filtered = $this->occure_filter($y, $this->phrase2WordLengthMinOccur);
		//sort the words from highest count to the lowest.
		arsort($occur_filtered);

		$imploded = $this->implode(", ", $occur_filtered);
		//release unused variables
		unset($y);
		unset($x);

		return $imploded;
	}

	public function parse_3words() {
		//create an array out of the site contents
		$a = split(" ", $this->contents);
		//initilize array
		$b = array();

		for ($i=0; $i < count($a)-2; $i++) {
			//delete phrases lesser than 5 characters
			if( (mb_strlen(trim($a[$i])) >= $this->word3WordPhraseLengthMin) && (mb_strlen(trim($a[$i+1])) > $this->word3WordPhraseLengthMin) && (mb_strlen(trim($a[$i+2])) > $this->word3WordPhraseLengthMin) && (mb_strlen(trim($a[$i]).trim($a[$i+1]).trim($a[$i+2])) > $this->phrase3WordLengthMin) )
			{
				$b[] = trim($a[$i])." ".trim($a[$i+1])." ".trim($a[$i+2]);
			}
		}

		//count the 3 word phrases
		$b = array_count_values($b);
		//sort the words from
		//highest count to the
		//lowest.
		$occur_filtered = $this->occure_filter($b, $this->phrase3WordLengthMinOccur);
		arsort($occur_filtered);

		$imploded = $this->implode(", ", $occur_filtered);
		//release unused variables
		unset($a);
		unset($b);

		return $imploded;
	}

	public function occure_filter($array_count_values, $min_occur) {
		$occur_filtered = array();
		foreach ($array_count_values as $word => $occured) {
			if ($occured >= $min_occur) {
				$occur_filtered[$word] = $occured;
			}
		}

		return $occur_filtered;
	}

	public function implode($gule, $array) {
		$c = "";
		foreach($array as $key=>$val) {
			@$c .= $key.$gule;
		}
		return $c;
	}
	
}

?>