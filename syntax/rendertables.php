<?php
/**
 * DokuWiki Plugin ipmap (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Michael Wheeler <doku@michael-wheeler.org>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_ipmap_rendertables extends DokuWiki_Syntax_Plugin
{
	//Definitions of the type of plugin we are, etc.
	function getType()		{ return 'formatting'; }
	function getAllowedTypes()	{ return array('formatting','container', 'substition', 'disabled'); }   
	function getSort()		{ return 158; }

	function connectTo($mode)
	{
/*		$this->Lexer->addSpecialPattern('<ipmap>',$mode,'plugin_ipmap_rendertables'); */
/*		$this->Lexer->addEntryPattern('<ipmap.*?>(?=.*?</ipmap>)',$mode,'plugin_ipmap_rendertables'); */
		$this->Lexer->addEntryPattern('<ipmap.*>.*',$mode,'plugin_ipmap_rendertables');
	}

	function postConnect()
	{
		$this->Lexer->addExitPattern('</ipmap>','plugin_ipmap_rendertables');
	}

	function handle($match, $state, $pos, &$handler)
	{
		//This function should eventually do all the parsing of the incoming data, to save time (it can be cached).
		
		switch($state)
		{
		case DOKU_LEXER_ENTER:
			list($ip, $net,$subnet) = preg_split("/\//u", substr($match, 7, -1), 3);
			return array($state, array($ip, $net, $subnet,strip_tags($match)));
		
		case DOKU_LEXER_UNMATCHED:
			return array($state, $match);
		
		case DOKU_LEXER_EXIT:
			return array($state, '');
		
		default:
			return array();
		}
	}
	
	function render($mode, &$renderer, $data)
	{
		global $in;
		
		//Choose the output mode. XHTML is the most common.
		switch($mode)
		{
		case "xhtml":
			list($state,$match) = $data;
			switch($state)
			{
			case DOKU_LEXER_ENTER: 
				list($ip, $net, $subnet,$data) = $match;     
				$renderer->doc .= $renderer->render($this->_maketables($ip, $net, $subnet, $data)); 
				break;

			case DOKU_LEXER_UNMATCHED:
				break;

			case DOKU_LEXER_EXIT:
				break;
			}
			return true;
		
		default:	//Currently all other modes are unsupported by both DokuIPMap and DokuWiki.
			return false;
		}
	}
	
	function _maketables($ip, $net, $subnet, $data)
	{
		$subnet = explode(">",$subnet);
		$subnet = $subnet[0];
		
		$x = array(
			9 => 8,
			8 => 8,
			7 => 8,
			6 => 8,
			5 => 8,
			4 => 4,
			3 => 4,
			2 => 2
		);
		
		$y = array(
			9 => 64,
			8 => 32,
			7 => 16,
			6 => 8,
			5 => 4,
			4 => 4,
			3 => 2,
			2 => 2
		);
		
		$subnetsr = array();
		$subnets = explode("*",$data);

		//For each of the subnets in the network, extract information.
		foreach($subnets as &$value)
		{
			$subnetu = explode("-",$value);
			$subneta = explode("/",trim($subnetu[0]));
			$subnetsr[$subneta[0]] = array("Mask" => $subneta[1], "Desc" => trim($subnetu[1]));
		}

		$diff =  $subnet - $net;
		$rightbits = 32 - $subnet;

		for($z = 0; $z < ($rightbits); ++$z)
		{
			$rightb .= "1";
		}
		
        $dright = bindec($rightb);
        $width=$x[$diff];
        $height=$y[$diff];
        $dip = ip2long($ip);
        for($z = 0; $z < ($width); ++$z) {
            $endrow .= "^";
        }
$first = 1;
/* Are we the first cell in a row - dodgy.  */
        $output = "^  [[..:main|UP]]  " . $endrow."\n";
        for($i = 0; $i < ($width*$height); ++$i) {
              if (($dip + $i * ($dright + 1) > $lasts + $drights ) or ($lasts + $drights == 0)) {
              $ipout = long2ip($dip + $i * ($dright + 1));
              $desc = $subnetsr[$ipout]['Desc'];
              $mask = $subnetsr[$ipout]['Mask'];
              if ($mask){
                   $rightbs = "";
                   $rightbitss = 32 - $mask;
                   for($z = 0; $z < ($rightbitss); ++$z) {
                        $rightbs .= "1";
                   }
                   $drights = bindec($rightbs);
                   $sout = $mask; 
                   $lasts = $dip + $i * ($dright + 1); 
                   $lastsb =  $ipout;
              }  else {
                   $sout = "$subnet";
              }
              }     
                  
              
               
              if ($desc){
                  if ((long2ip($dip + $i * ($dright + 1)) == $ipout) or ($first == 1)){
                  $output .= "^  [[.:$ipout\_$sout:main|$ipout/$sout]]  \\\\  " . "$desc" . "    ";
                  $first = 0;
                  } else {
                          $output .= "|";
                  }
                  } else {
                    if ((long2ip($dip + $i * ($dright + 1)) == $ipout) or ($first == 1)){
                   $output .= "|  $ipout/$sout    ";
                   $first = 0;
                   } else {
                           $output .= "|";
                   }
 
                  }
              if (($i + 1)% $width == 0){
                   $output .= "|\n";    
                   $first = 1;
              }
        }
        return($output);
    }
}
// vim:ts=4:sw=4:et:enc=utf-8:
?>