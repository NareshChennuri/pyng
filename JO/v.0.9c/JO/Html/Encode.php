<?php

class JO_Html_Encode {
	
	private static $parse_tag_regexp = '
		@
		\<\s*?(\w+)((?:\b(?:\'[^\']*\'|"[^"]*"|[^\>])*)?)\>
		((?:(?>[^\<]*)|(?R))*)
		\<\/\s*?\\1(?:\b[^\>]*)?\>
		|\<\s*(\w+)(\b(?:\'[^\']*\'|"[^"]*"|[^\>])*)?\/?\>
		@uxis';
	
	private static $parse_attr_regexp = '/(\w+)\s*(?:=\s*(?:"([^"]*)"|\'([^\']*)\'|(\w+)))?/usix';

	public static function html2array ( $html ) {
		if ( !preg_match_all( self::$parse_tag_regexp, $html = trim($html), $m, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) ) {
			return $html;
		}
		$i = 0;
		$ret = array();
		foreach ($m as $set) {
			if ( strlen( $val = trim( substr($html, $i, $set[0][1] - $i) ) ) )
				$ret[] = $val;
				$val = $set[1][1] < 0 ? array( 'tag' => strtolower($set[4][0]) ) : array( 'tag' => strtolower($set[1][0]), 'val' => self::html2array($set[3][0]) );
    			if ( preg_match_all( self::$parse_attr_regexp, isset($set[5]) && $set[2][1] < 0 ? $set[5][0] : $set[2][0] ,$attrs, PREG_SET_ORDER ) ) {
      				foreach ($attrs as $a) {
        				$val['attr'][$a[1]]=$a[count($a)-1];
      				}
    			}
    			$ret[] = $val;
    			$i = $set[0][1]+strlen( $set[0][0] );
  		}
  		$l = strlen($html);
  		if ( $i < $l )
    		if ( strlen( $val = trim( substr( $html, $i, $l - $i ) ) ) )
      			$ret[] = $val;
  		return $ret;
	}


	public static function array2html ( $a, $in = "" ) {
		if ( is_array($a) ) {
    		$s = "";
    		foreach ($a as $t)
      			if ( is_array($t) ) {
        			$attrs="";
        			if ( isset($t['attr']) )
          				foreach( $t['attr'] as $k => $v )
            				$attrs.=" ${k}=".( strpos( $v, '"' )!==false ? "'$v'" : "\"$v\"" );
        					$s.= $in."<".$t['tag'].$attrs.( isset( $t['val'] ) ? ">\n".self::array2html( $t['val'], $in."  " ).$in."</".$t['tag'] : "/" ).">\n";
      			} else
        			$s.= $in.$t."\n";
  		} else {
    		$s = empty($a) ? "" : $in.$a."\n";
  		}
  		return $s;
	}
	
}

?>