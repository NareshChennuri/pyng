<?php

class WM_Bbcode {
	
	private static $BBCODE_TPL_READY = false;
	
	private static function template() {
		return '<!-- BEGIN ulist_open --><ul><!-- END ulist_open -->
				<!-- BEGIN ulist_close --></ul><!-- END ulist_close -->
				
				<!-- BEGIN olist_open --><ol type="{LIST_TYPE}"><!-- END olist_open -->
				<!-- BEGIN olist_close --></ol><!-- END olist_close -->
				
				<!-- BEGIN listitem --><li><!-- END listitem -->

				<!-- BEGIN quote_open -->
				<table width="90%" cellspacing="1" cellpadding="3" border="0" align="center">
				<tr> 
					  <td><span class="genmed"><b>{L_QUOTE}:</b></span></td>
					</tr>
					<tr>
					  <td class="quote"><!-- END quote_open -->
				<!-- BEGIN quote_close --></td>
					</tr>
				</table>
				<!-- END quote_close -->

				<!-- BEGIN b_open --><span style="font-weight: bold"><!-- END b_open -->
				<!-- BEGIN b_close --></span><!-- END b_close -->
				
				<!-- BEGIN u_open --><span style="text-decoration: underline"><!-- END u_open -->
				<!-- BEGIN u_close --></span><!-- END u_close -->
				
				<!-- BEGIN i_open --><span style="font-style: italic"><!-- END i_open -->
				<!-- BEGIN i_close --></span><!-- END i_close -->
				
				<!-- BEGIN img --><img src="{URL}" border="0" alt="" class="bbcode-image" /><!-- END img -->
				
				<!-- BEGIN url --><a href="{URL}" target="_blank" class="postlink">{DESCRIPTION}</a><!-- END url -->
				
				<!-- BEGIN email --><a href="mailto:{EMAIL}">{EMAIL}</A><!-- END email -->';
	}

	public static function load_bbcode_template() {
		$tpl = self::template();
	
		// replace \ with \\ and then ' with \'.
		$tpl = str_replace('\\', '\\\\', $tpl);
		$tpl  = str_replace('\'', '\\\'', $tpl);
	
		// strip newlines.
		$tpl  = str_replace("\n", '', $tpl);
	
		$bbcode_tpls = array();
		
		if(preg_match_all('#<!-- BEGIN (.*?) -->(.*?)<!-- END (.*?) -->#', $tpl, $match)) {
			foreach($match[1] AS $row => $key) {
				$bbcode_tpls[$key] = $match[2][$row];
			}
		}
	
		return $bbcode_tpls;
	}
	
	public static function prepare_bbcode_template($bbcode_tpl) {
		$translate = JO_Translate::getInstance();
	
		$bbcode_tpl['olist_open'] = str_replace('{LIST_TYPE}', '\\1', $bbcode_tpl['olist_open']);
	
		$bbcode_tpl['quote_open'] = str_replace('{L_QUOTE}', $translate->translate('Quote'), $bbcode_tpl['quote_open']);
	
		$bbcode_tpl['img'] = str_replace('{URL}', '\\1', $bbcode_tpl['img']);
	
		// We do URLs in several different ways..
		$bbcode_tpl['url1'] = str_replace('{URL}', '\\1', $bbcode_tpl['url']);
		$bbcode_tpl['url1'] = str_replace('{DESCRIPTION}', '\\1', $bbcode_tpl['url1']);
	
		$bbcode_tpl['url2'] = str_replace('{URL}', 'http://\\1', $bbcode_tpl['url']);
		$bbcode_tpl['url2'] = str_replace('{DESCRIPTION}', '\\1', $bbcode_tpl['url2']);
	
		$bbcode_tpl['url3'] = str_replace('{URL}', '\\1', $bbcode_tpl['url']);
		$bbcode_tpl['url3'] = str_replace('{DESCRIPTION}', '\\2', $bbcode_tpl['url3']);
	
		$bbcode_tpl['url4'] = str_replace('{URL}', 'http://\\1', $bbcode_tpl['url']); 
		$bbcode_tpl['url4'] = str_replace('{DESCRIPTION}', '\\3', $bbcode_tpl['url4']);
	
		$bbcode_tpl['email'] = str_replace('{EMAIL}', '\\1', $bbcode_tpl['email']);
	
		self::$BBCODE_TPL_READY = true;
		
		return $bbcode_tpl;
	}
	
	public static function bbencode_second_pass($text)
	{
	
		// pad it with a space so we can distinguish between FALSE and matching the 1st char (index 0).
		// This is important; bbencode_quote(), bbencode_list(), and bbencode_code() all depend on it.
		$text = " " . $text;
	
		// First: If there isn't a "[" and a "]" in the message, don't bother.
		if (! (strpos($text, "[") && strpos($text, "]")) )
		{
			// Remove padding, return.
			$text = substr($text, 1);
			return $text;
		}
	
		// Only load the templates ONCE..
		if (!self::$BBCODE_TPL_READY)
		{
			// load templates from file into array.
			$bbcode_tpl = self::load_bbcode_template();
	
			// prepare array for use in regexps.
			$bbcode_tpl = self::prepare_bbcode_template($bbcode_tpl);
		}
	
		// [CODE] and [/CODE] for posting code (HTML, PHP, C etc etc) in your posts.
		$text = self::bbencode_second_pass_code($text, $bbcode_tpl);
	
		// [QUOTE] and [/QUOTE] for posting replies with quote, or just for quoting stuff.
		$text = str_replace("[quote]", $bbcode_tpl['quote_open'], $text);
		$text = str_replace("[/quote]", $bbcode_tpl['quote_close'], $text);
	
		// New one liner to deal with opening quotes with usernames...
		// replaces the two line version that I had here before..
//		$text = preg_replace("/\[quote=\"(.*?)\"\]/si", $bbcode_tpl['quote_username_open'], $text);
	
		// [list] and [list=x] for (un)ordered lists.
		// unordered lists
		$text = str_replace("[list]", $bbcode_tpl['ulist_open'], $text);
		// li tags
		$text = str_replace("[*]", $bbcode_tpl['listitem'], $text);
		// ending tags
		$text = str_replace("[/list:u]", $bbcode_tpl['ulist_close'], $text);
		$text = str_replace("[/list:o]", $bbcode_tpl['olist_close'], $text);
		// Ordered lists
		$text = preg_replace("/\[list=([a1])\]/si", $bbcode_tpl['olist_open'], $text);
	
		// [b] and [/b] for bolding text.
		$text = str_replace("[b]", $bbcode_tpl['b_open'], $text);
		$text = str_replace("[/b]", $bbcode_tpl['b_close'], $text);
	
		// [u] and [/u] for underlining text.
		$text = str_replace("[u]", $bbcode_tpl['u_open'], $text);
		$text = str_replace("[/u]", $bbcode_tpl['u_close'], $text);
	
		// [i] and [/i] for italicizing text.
		$text = str_replace("[i]", $bbcode_tpl['i_open'], $text);
		$text = str_replace("[/i]", $bbcode_tpl['i_close'], $text);
	
		// Patterns and replacements for URL and email tags..
		$patterns = array();
		$replacements = array();
	
		// [img]image_url_here[/img] code..
		// This one gets first-passed..
		$patterns[] = "#\[img\](.*?)\[/img\]#si";
		$replacements[] = $bbcode_tpl['img'];
	
		// matches a [url]xxxx://www.phpbb.com[/url] code.. 
		$patterns[] = "#\[url\]([\w]+?://[^ \"\n\r\t<]*?)\[/url\]#is"; 
		$replacements[] = $bbcode_tpl['url1']; 
	
		// [url]www.phpbb.com[/url] code.. (no xxxx:// prefix). 
		$patterns[] = "#\[url\]((www|ftp)\.[^ \"\n\r\t<]*?)\[/url\]#is"; 
		$replacements[] = $bbcode_tpl['url2']; 
	
		// [url=xxxx://www.phpbb.com]phpBB[/url] code.. 
		$patterns[] = "#\[url=([\w]+?://[^ \"\n\r\t<]*?)\](.*?)\[/url\]#is"; 
		$replacements[] = $bbcode_tpl['url3']; 
	
		// [url=www.phpbb.com]phpBB[/url] code.. (no xxxx:// prefix). 
		$patterns[] = "#\[url=((www|ftp)\.[^ \"\n\r\t<]*?)\](.*?)\[/url\]#is"; 
		$replacements[] = $bbcode_tpl['url4']; 
	
		// [email]user@domain.tld[/email] code..
		$patterns[] = "#\[email\]([a-z0-9&\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)\[/email\]#si";
		$replacements[] = $bbcode_tpl['email'];
	
		$text = preg_replace($patterns, $replacements, $text);
	
		// Remove our padding from the string..
		$text = substr($text, 1);
	
		return self::make_clickable($text);
	
	}
	
	public static function bbencode_first_pass($text)
	{
		// pad it with a space so we can distinguish between FALSE and matching the 1st char (index 0).
		// This is important; bbencode_quote(), bbencode_list(), and bbencode_code() all depend on it.
		$text = " " . $text;
	
		// [QUOTE] and [/QUOTE] for posting replies with quote, or just for quoting stuff.
		$text = self::bbencode_first_pass_pda($text, '[quote]', '[/quote]', '', false, '');
		$text = self::bbencode_first_pass_pda($text, '/\[quote=(\\\".*?\\\")\]/is', '[/quote]', '', false, '', "[quote=\\1]");
	
		// [list] and [list=x] for (un)ordered lists.
		$open_tag = array();
		$open_tag[0] = "[list]";
	
		// unordered..
		$text = self::bbencode_first_pass_pda($text, $open_tag, "[/list]", "[/list:u]", false, 'replace_listitems');
	
		$open_tag[0] = "[list=1]";
		$open_tag[1] = "[list=a]";
	
		// ordered.
		$text = self::bbencode_first_pass_pda($text, $open_tag, "[/list]", "[/list:o]",  false, 'replace_listitems');

		// [b] and [/b] for bolding text.
		$text = preg_replace("#\[b\](.*?)\[/b\]#si", "[b]\\1[/b]", $text);
	
		// [u] and [/u] for underlining text.
		$text = preg_replace("#\[u\](.*?)\[/u\]#si", "[u]\\1[/u]", $text);
	
		// [i] and [/i] for italicizing text.
		$text = preg_replace("#\[i\](.*?)\[/i\]#si", "[i]\\1[/i]", $text);
	
		// [img]image_url_here[/img] code..
		$text = preg_replace("#\[img\]((ht|f)tp://)([^\r\n\t<\"]*?)\[/img\]#sie", "'[img]\\1' . str_replace(' ', '%20', '\\3') . '[/img]'", $text);
	
		// Remove our padding from the string..
		return substr($text, 1);;
	
	}
	
	
	public static function bbencode_first_pass_pda($text, $open_tag, $close_tag, $close_tag_new, $mark_lowest_level, $func, $open_regexp_replace = false)
	{
		$open_tag_count = 0;
	
		if (!$close_tag_new || ($close_tag_new == ''))
		{
			$close_tag_new = $close_tag;
		}
	
		$close_tag_length = strlen($close_tag);
		$close_tag_new_length = strlen($close_tag_new);
	
		$use_function_pointer = ($func && ($func != ''));
	
		$stack = array();
	
		if (is_array($open_tag))
		{
			if (0 == count($open_tag))
			{
				// No opening tags to match, so return.
				return $text;
			}
			$open_tag_count = count($open_tag);
		}
		else
		{
			// only one opening tag. make it into a 1-element array.
			$open_tag_temp = $open_tag;
			$open_tag = array();
			$open_tag[0] = $open_tag_temp;
			$open_tag_count = 1;
		}
	
		$open_is_regexp = false;
	
		if ($open_regexp_replace)
		{
			$open_is_regexp = true;
			if (!is_array($open_regexp_replace))
			{
				$open_regexp_temp = $open_regexp_replace;
				$open_regexp_replace = array();
				$open_regexp_replace[0] = $open_regexp_temp;
			}
		}
	
		if ($mark_lowest_level && $open_is_regexp)
		{
			throw new JO_Exception("Unsupported operation for bbcode_first_pass_pda().");
		}
	
		// Start at the 2nd char of the string, looking for opening tags.
		$curr_pos = 1;
		while ($curr_pos && ($curr_pos < strlen($text)))
		{
			$curr_pos = strpos($text, "[", $curr_pos);
	
			// If not found, $curr_pos will be 0, and the loop will end.
			if ($curr_pos)
			{
				// We found a [. It starts at $curr_pos.
				// check if it's a starting or ending tag.
				$found_start = false;
				$which_start_tag = "";
				$start_tag_index = -1;
	
				for ($i = 0; $i < $open_tag_count; $i++)
				{
					// Grab everything until the first "]"...
					$possible_start = substr($text, $curr_pos, strpos($text, ']', $curr_pos + 1) - $curr_pos + 1);
	
					//
					// We're going to try and catch usernames with "[' characters.
					//
					if( preg_match('#\[quote=\\\"#si', $possible_start, $match) && !preg_match('#\[quote=\\\"(.*?)\\\"\]#si', $possible_start) )
					{
						// OK we are in a quote tag that probably contains a ] bracket.
						// Grab a bit more of the string to hopefully get all of it..
						if ($close_pos = strpos($text, '"]', $curr_pos + 9))
						{
							if (strpos(substr($text, $curr_pos + 9, $close_pos - ($curr_pos + 9)), '[quote') === false)
							{
								$possible_start = substr($text, $curr_pos, $close_pos - $curr_pos + 2);
							}
						}
					}
	
					// Now compare, either using regexp or not.
					if ($open_is_regexp)
					{
						$match_result = array();
						if (preg_match($open_tag[$i], $possible_start, $match_result))
						{
							$found_start = true;
							$which_start_tag = $match_result[0];
							$start_tag_index = $i;
							break;
						}
					}
					else
					{
						// straightforward string comparison.
						if (0 == strcasecmp($open_tag[$i], $possible_start))
						{
							$found_start = true;
							$which_start_tag = $open_tag[$i];
							$start_tag_index = $i;
							break;
						}
					}
				}
	
				if ($found_start)
				{
					// We have an opening tag.
					// Push its position, the text we matched, and its index in the open_tag array on to the stack, and then keep going to the right.
					$match = array("pos" => $curr_pos, "tag" => $which_start_tag, "index" => $start_tag_index);
					self::bbcode_array_push($stack, $match);
					//
					// Rather than just increment $curr_pos
					// Set it to the ending of the tag we just found
					// Keeps error in nested tag from breaking out
					// of table structure..
					//
					$curr_pos += strlen($possible_start);
				}
				else
				{
					// check for a closing tag..
					$possible_end = substr($text, $curr_pos, $close_tag_length);
					if (0 == strcasecmp($close_tag, $possible_end))
					{
						// We have an ending tag.
						// Check if we've already found a matching starting tag.
						if (sizeof($stack) > 0)
						{
							// There exists a starting tag.
							$curr_nesting_depth = sizeof($stack);
							// We need to do 2 replacements now.
							$match = self::bbcode_array_pop($stack);
							$start_index = $match['pos'];
							$start_tag = $match['tag'];
							$start_length = strlen($start_tag);
							$start_tag_index = $match['index'];
	
							if ($open_is_regexp)
							{
								$start_tag = preg_replace($open_tag[$start_tag_index], $open_regexp_replace[$start_tag_index], $start_tag);
							}
	
							// everything before the opening tag.
							$before_start_tag = substr($text, 0, $start_index);
	
							// everything after the opening tag, but before the closing tag.
							$between_tags = substr($text, $start_index + $start_length, $curr_pos - $start_index - $start_length);
	
							// Run the given function on the text between the tags..
							if ($use_function_pointer)
							{
								$between_tags = $func($between_tags);
							}
	
							// everything after the closing tag.
							$after_end_tag = substr($text, $curr_pos + $close_tag_length);
	
							// Mark the lowest nesting level if needed.
							if ($mark_lowest_level && ($curr_nesting_depth == 1))
							{
								if ($open_tag[0] == '[code]')
								{
									$code_entities_match = array('#<#', '#>#', '#"#', '#:#', '#\[#', '#\]#', '#\(#', '#\)#', '#\{#', '#\}#');
									$code_entities_replace = array('&lt;', '&gt;', '&quot;', '&#58;', '&#91;', '&#93;', '&#40;', '&#41;', '&#123;', '&#125;');
									$between_tags = preg_replace($code_entities_match, $code_entities_replace, $between_tags);
								}
								$text = $before_start_tag . substr($start_tag, 0, $start_length - 1) . ":$curr_nesting_depth]";
								$text .= $between_tags . substr($close_tag_new, 0, $close_tag_new_length - 1) . ":$curr_nesting_depth]";
							}
							else
							{
								if ($open_tag[0] == '[code]')
								{
									$text = $before_start_tag . '&#91;code&#93;';
									$text .= $between_tags . '&#91;/code&#93;';
								}
								else
								{
									if ($open_is_regexp)
									{
										$text = $before_start_tag . $start_tag;
									}
									else
									{
										$text = $before_start_tag . substr($start_tag, 0, $start_length - 1) . "]";
									}
									$text .= $between_tags . substr($close_tag_new, 0, $close_tag_new_length - 1) . "]";
								}
							}
	
							$text .= $after_end_tag;
	
							// Now.. we've screwed up the indices by changing the length of the string.
							// So, if there's anything in the stack, we want to resume searching just after it.
							// otherwise, we go back to the start.
							if (sizeof($stack) > 0)
							{
								$match = self::bbcode_array_pop($stack);
								$curr_pos = $match['pos'];
	//							bbcode_array_push($stack, $match);
	//							++$curr_pos;
							}
							else
							{
								$curr_pos = 1;
							}
						}
						else
						{
							// No matching start tag found. Increment pos, keep going.
							++$curr_pos;
						}
					}
					else
					{
						// No starting tag or ending tag.. Increment pos, keep looping.,
						++$curr_pos;
					}
				}
			}
		} // while
	
		return $text;
	
	}
	
	public static function bbencode_second_pass_code($text, $bbcode_tpl)
	{
		
		return $text;
	
		$code_start_html = $bbcode_tpl['code_open'];
		$code_end_html =  $bbcode_tpl['code_close'];
	
		// First, do all the 1st-level matches. These need an htmlspecialchars() run,
		// so they have to be handled differently.
		$match_count = preg_match_all("#\[code:1\](.*?)\[/code:1\]#si", $text, $matches);
	
		for ($i = 0; $i < $match_count; $i++)
		{
			$before_replace = $matches[1][$i];
			$after_replace = $matches[1][$i];
	
			// Replace 2 spaces with "&nbsp; " so non-tabbed code indents without making huge long lines.
			$after_replace = str_replace("  ", "&nbsp; ", $after_replace);
			// now Replace 2 spaces with " &nbsp;" to catch odd #s of spaces.
			$after_replace = str_replace("  ", " &nbsp;", $after_replace);
	
			// Replace tabs with "&nbsp; &nbsp;" so tabbed code indents sorta right without making huge long lines.
			$after_replace = str_replace("\t", "&nbsp; &nbsp;", $after_replace);
	
			// now Replace space occurring at the beginning of a line
			$after_replace = preg_replace("/^ {1}/m", '&nbsp;', $after_replace);
	
			$str_to_match = "[code:1]" . $before_replace . "[/code:1]";
	
			$replacement = $code_start_html;
			$replacement .= $after_replace;
			$replacement .= $code_end_html;
	
			$text = str_replace($str_to_match, $replacement, $text);
		}
	
		// Now, do all the non-first-level matches. These are simple.
		$text = str_replace("[code]", $code_start_html, $text);
		$text = str_replace("[/code]", $code_end_html, $text);
	
		return $text;
	
	}
	
	public static function make_clickable($text)
	{
	
		// pad it with a space so we can match things at the start of the 1st line.
		$ret = ' ' . $text;
	
		// matches an "xxxx://yyyy" URL at the start of a line, or after a space. 
		// xxxx can only be alpha characters. 
		// yyyy is anything up to the first space, newline, comma, double quote or < 
		$ret = preg_replace("#(^|[\n ])([\w]+?://[^ \"\n\r\t<]*)#is", "\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $ret); 
	
		// matches a "www|ftp.xxxx.yyyy[/zzzz]" kinda lazy URL thing 
		// Must contain at least 2 dots. xxxx contains either alphanum, or "-" 
		// zzzz is optional.. will contain everything up to the first space, newline, 
		// comma, double quote or <. 
		$ret = preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r<]*)#is", "\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>", $ret); 
	
		// matches an email@domain type address at the start of a line, or after a space.
		// Note: Only the followed chars are valid; alphanums, "-", "_" and or ".".
		$ret = preg_replace("#(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $ret);
	
		// Remove our padding..
		$ret = substr($ret, 1);
	
		return($ret);
	}
	
	public static function undo_make_clickable($text)
	{
		$text = preg_replace("#<!-- BBCode auto-link start --><a href=\"(.*?)\" target=\"_blank\">.*?</a><!-- BBCode auto-link end -->#i", "\\1", $text);
		$text = preg_replace("#<!-- BBcode auto-mailto start --><a href=\"mailto:(.*?)\">.*?</a><!-- BBCode auto-mailto end -->#i", "\\1", $text);
	
		return $text;
	}
	
	public static function undo_htmlspecialchars($input)
	{
		$input = preg_replace("/&gt;/i", ">", $input);
		$input = preg_replace("/&lt;/i", "<", $input);
		$input = preg_replace("/&quot;/i", "\"", $input);
		$input = preg_replace("/&amp;/i", "&", $input);
	
		return $input;
	}
	
	public static function replace_listitems($text, $uid = 0)
	{
		$text = str_replace("[*]", "[*:$uid]", $text);
	
		return $text;
	}
	
	public static function escape_slashes($input)
	{
		$output = str_replace('/', '\/', $input);
		return $output;
	}
	
	public static function bbcode_array_push(&$stack, $value)
	{
	   $stack[] = $value;
	   return(sizeof($stack));
	}
	
	public static function bbcode_array_pop(&$stack)
	{
	   $arrSize = count($stack);
	   $x = 1;
	
	   while(list($key, $val) = each($stack))
	   {
	      if($x < count($stack))
	      {
		 		$tmpArr[] = $val;
	      }
	      else
	      {
		 		$return_val = $val;
	      }
	      $x++;
	   }
	   $stack = $tmpArr;
	
	   return($return_val);
	}
	
	



	
}

?>