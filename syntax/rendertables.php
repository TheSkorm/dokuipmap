<?php
/**
	DokuWiki Plugin ipmap (Syntax Component)

	@license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
	@author  Michael Wheeler <doku@michael-wheeler.org>

	Copyright 2010 Michael Wheeler.
	
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
		//Add the pattern that matches the entire table, to trigger our plugin.
		$this->Lexer->addEntryPattern('<ipmap.*>.*</ipmap>',$mode,'plugin_ipmap_rendertables');
		
		//Add the pattern that matches the end of the table ("</ipmap>").
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
		
		case DOKU_LEXER_MATCHED:
			return array($state, $match);
		
		case DOKU_LEXER_EXIT:
			return array($state, '');
		
		case DOKU_LEXER_UNMATCHED:
		case DOKU_LEVER_SPECIAL:
			return array();
		
		default:	//This is basically in case of an error. All valid states should be handled above.
			return array();
		}
	}
	
	function render($mode, &$renderer, $data)
	{
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
			return(true);

		case "metadata":	//There is also a metadata output mode. It does nothing, so we do nothing.
			return(true);
		
		default:		//Currently all other modes are unsupported by both DokuIPMap and DokuWiki.
			return(false);
		}
	}
	
	/**
	@param ip The base IP address of the network.
	@param net The size of the network that IP address is in.
	@param subnet The size of the subnets that the table should be broken up into.
	@param data The raw data from the matching process.
	*/
	function _maketables($ip, $net, $subnet, $data)
	{
		$subnet = explode(">",$subnet);
		$subnet = $subnet[0];
		
		$subnetsr = array();
		$subnets = explode("*",$data);

		//For each of the subnets in the network, extract information.
		foreach($subnets as &$value)
		{
			$subnetu = explode("-",$value);
			$subneta = explode("/",trim($subnetu[0]));
			$subnetsr[$subneta[0]] = array("Mask" => $subneta[1], "Desc" => trim($subnetu[1]));
		}
		
		$rightbits = 32 - $subnet;

		for($z = 0; $z < ($rightbits); ++$z)
		{
			$rightb .= "1";
		}
		
		$dright = bindec($rightb);
		
		//Calculate the required size of the table.
		$sizeDifference = $subnet - $net;
		list($width, $height) = $this->calculateTableSize($sizeDifference);
		
		$dip = ip2long($ip);
		
		//Oh dear Michael, what have we done here?
		for($z = 0; $z < ($width); ++$z)
		{
			$endrow .= "^";
		}
		
		$first = 1;
		
		//Output the first line in the table, which is a link to the namespace above.
		//We get a link to the appropriate page from the configuration: http://www.dokuwiki.org/config:startpage
		$output = "^  [[..:".$conf['start']."|UP]]  " . $endrow."\n";
		
		for($i = 0; $i < ($width*$height); ++$i)
		{
			if (($dip + $i * ($dright + 1) > $lasts + $drights ) or ($lasts + $drights == 0))
			{
				$ipout = long2ip($dip + $i * ($dright + 1));
				$desc = $subnetsr[$ipout]['Desc'];
				$mask = $subnetsr[$ipout]['Mask'];
				
				if ($mask)
				{
					$rightbs = "";
					$rightbitss = 32 - $mask;
					
					for($z = 0; $z < ($rightbitss); ++$z)
					{
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
			
			if ($desc)
			{
				if ((long2ip($dip + $i * ($dright + 1)) == $ipout) or ($first == 1))
				{
					$output .= "^  [[.:$ipout\_$sout:main|$ipout/$sout]]  \\\\  " . "$desc" . "    ";
					$first = 0;
				} else {
					$output .= "|";
				}
			} else {
				if ((long2ip($dip + $i * ($dright + 1)) == $ipout) or ($first == 1))
				{
					$output .= "|  $ipout/$sout    ";
					$first = 0;
				} else {
					$output .= "|";
				}
			}
			
			if (($i + 1)% $width == 0)
			{
				$output .= "|\n";    
				$first = 1;
			}
		}
        	
		return($output);
	}
	
	/**
	Calculates the size of the required XHTML table, given the difference in size between the subnets, in bits.
	@param sizeDifference The difference in size between the network and the subnets, in bits.
	@return The size of the table as an array, horizontal size (X) then vertical size(Y).
	*/
	function calculateTableSize($sizeDifference)
	{
		$xSize = 0;
		$ySize = 0;
		
		//If the size difference is small, we use a smaller table that doesn't take up the full width of the page.
		if($sizeDifference>=5)
		{
			//Calculate automatically, based on width of 8.
			$xSize = 8;
			
			//We base the vertical length of the table on the number of cells needed divided by the width. Magic!
			$ySize = (pow(2,$sizeDifference))/$xSize;
		} else {
			//Calculate manually.
			switch($sizeDifference)
			{
			case 1:		//A 1x2 table (2 cells, not commonly used in IPv4 due to need for network and broadcast addresses).
				$xSize = 1;
				$ySize = 2;
				break;
			
			case 2:		//A 2x2 table (4 cells).
				$xSize = 2;
				$ySize = 2;
				break;
				
			case 3:		//A 4x2 table (8 cells).
				$xSize = 4;
				$ySize = 2;
				break;
				
			case 4:		//A 4x4 table (16 cells).
				$xSize = 4;
				$ySize = 4;
				break;
			
			default:	//If we got here, something has gone terribly wrong.
				die("Something impossible happened in calculateTableSize(). I suggest filing a bug report with Alan Turing.");
			}
		}
		
		return(array($xSize,$ySize));
	}
	
	function parseFirstLine($lineData)
	{
		return(0);
	}
	
	/**
	@param lineData The entire line of data, as input from the raw data.
	@return An array containing information about the subnet.
	*/
	function parseSubnetLine($lineData)
	{
		return(0);
	}
	
	function generateTable_XHTML()
	{
		return(0);
	}
}
?>
