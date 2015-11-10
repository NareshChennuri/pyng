<?php

class JO_Minify_Html {

	public function minify($buffer)
	{
		/*
		// remove HTML comments (not containing IE conditional comments).
		$content = preg_replace_callback(
				'/<!--([\\s\\S]*?)-->/'
				,array($this, '_commentCB')
				,$content);
		
		// trim each line.
		// @todo take into account attribute values that span multiple lines.
		$content = preg_replace('/^\\s+|\\s+$/m', '', $content);
		
		
		// remove ws outside of all elements
		$content = preg_replace(
				'/>(\\s(?:\\s*))?([^<]+)(\\s(?:\s*))?</'
				,'>$1$2$3<'
				,$content);
		
		// use newlines before 1st attribute in open tags (to limit line lengths)
		$content = preg_replace('/(<[a-z\\-]+)\\s+([^>]+>)/i', "$1\n$2", $content);
		
		return $content;*/
		$search = array(
				'/\>[^\S ]+/s', //strip whitespaces after tags, except space
				'/[^\S ]+\</s', //strip whitespaces before tags, except space
				'/(\s)+/s'  // shorten multiple whitespace sequences
		);
		$replace = array(
				'>',
				'<',
				'\\1'
		);
		$buffer = preg_replace($search, $replace, $buffer);
		return $buffer;
		
	}
    
    protected function _commentCB($m)
    {
        return (0 === strpos($m[1], '[') || false !== strpos($m[1], '<!['))
            ? $m[0]
            : '';
    }
	
}

?>