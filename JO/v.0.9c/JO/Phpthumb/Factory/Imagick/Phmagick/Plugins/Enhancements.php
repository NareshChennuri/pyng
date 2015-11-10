<?php
/*
    +--------------------------------------------------------------------------------------------+
    |   DISCLAIMER - LEGAL NOTICE -                                                              |
    +--------------------------------------------------------------------------------------------+
    |                                                                                            |
    |  This program is free for non comercial use, see the license terms available at            |
    |  http://www.francodacosta.com/licencing/ for more information                              |
    |                                                                                            |
    |  This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; |
    |  without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. |
    |                                                                                            |
    |  USE IT AT YOUR OWN RISK                                                                   |
    |                                                                                            |
    |                                                                                            |
    +--------------------------------------------------------------------------------------------+

*/
/**
 * phMagick - Image enhancements functions
 *
 * @package    phMagick
 * @version    0.1.0
 * @author     Nuno Costa - sven@francodacosta.com
 * @copyright  Copyright (c) 2007
 * @license    http://www.francodacosta.com/phmagick/license/
 * @link       http://www.francodacosta.com/phmagick
 * @since      2008-03-13
 */
class JO_Phpthumb_Factory_Imagick_Phmagick_Plugins_Enhancements  {

	function denoise(JO_Phpthumb_Factory_Imagick_Phmagick $p,  $amount=1){
		$cmd   = $p->getBinary('convert');
		$cmd .= ' -noise '.$amount ;
		$cmd .= ' -background "none" "' . $p->getSource() .'"';
		$cmd .= ' "' . $p->getDestination() .'"';

		$p->execute($cmd);
		$p->setSource($p->getDestination());
		$p->setHistory($p->getDestination());
		return  $p ;
	}


	function sharpen(JO_Phpthumb_Factory_Imagick_Phmagick $p, $amount =10){
        $cmd   = $p->getBinary('convert');
        $cmd .= ' -sharpen 2x' .$amount ;
        $cmd .= ' -background "none" "' . $p->getSource() .'"';
        $cmd .= ' "' . $p->getDestination() .'"';

        $p->execute($cmd);
        $p->setSource($p->getDestination());
        $p->setHistory($p->getDestination());
        return  $p ;
    }

	function smooth(JO_Phpthumb_Factory_Imagick_Phmagick $p){
        $cmd   = $p->getBinary('convert');
        $cmd .= ' -despeckle -despeckle -despeckle ' ;
        $cmd .= ' -background "none" "' . $p->getSource() .'"';
        $cmd .= ' "' . $p->getDestination() .'"';

        $p->execute($cmd);
        $p->setSource($p->getDestination());
        $p->setHistory($p->getDestination());
        return  $p ;
	}

	function saturate(JO_Phpthumb_Factory_Imagick_Phmagick $p, $amount=200){
		$cmd   = $p->getBinary('convert');
        $cmd .= ' -modulate 100,' .$amount ;
        $cmd .= ' -background "none" "' . $p->getSource().'"' ;
        $cmd .= ' "' . $p->getDestination().'"' ;

        $p->execute($cmd);
        $p->setSource($p->getDestination());
        $p->setHistory($p->getDestination());
        return  $p ;
    }

      function contrast(JO_Phpthumb_Factory_Imagick_Phmagick $p,$amount=10){
        $cmd   = $p->getBinary('convert');
        $cmd .= ' -sigmoidal-contrast ' .$amount. 'x50%' ;
        $cmd .= ' -background "none" "' . $p->getSource().'"'  ;
        $cmd .= ' "' . $p->getDestination().'"'  ;

        $p->execute($cmd);
        $p->setSource($p->getDestination());
        $p->setHistory($p->getDestination());
        return  $p ;
      }


    function edges(JO_Phpthumb_Factory_Imagick_Phmagick $p,$amount=10){
        $cmd   = $p->getBinary('convert');
        $cmd .= ' -adaptive-sharpen 2x' .$amount ;
        $cmd .= ' -background "none" "' . $p->getSource() .'"';
        $cmd .= ' "' . $p->getDestination() .'"';

        $p->execute($cmd);
        $p->setSource($p->getDestination());
        $p->setHistory($p->getDestination());
        return  $p ;
    }

}
?>