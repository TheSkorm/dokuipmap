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

	function handle($match, $state, $pos, Doku_Handler $handler)
	{
		//This function should eventually do all the parsing of the incoming data, to save time (it can be cached).
		
		switch($state)
		{
		case DOKU_LEXER_ENTER:
			$firstLine = strtok($match,"\n");
			list($baseIP, $networkSize, $subnetSize) = $this->parseFirstLine($firstLine);
			
			return array($state, array($baseIP, $networkSize, $subnetSize, strip_tags($match)));
		
		case DOKU_LEXER_EXIT:
			return array($state, '');
		
		case DOKU_LEXER_MATCHED:
		case DOKU_LEXER_UNMATCHED:
		case DOKU_LEVER_SPECIAL:
			return array();
		
		default:	//This is basically in case of an error. All valid states should be handled above.
			return array();
		}
	}
	
	function render($mode, Doku_Renderer $renderer, $data)
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
//				$renderer->doc .= $renderer->render($this->_maketables($ip, $net, $subnet, $data)); 
$network = ip2long($ip);

				$renderer->doc .= $renderer->render($this->_maketables($this->_findnetworks($network, $net, $subnet),
$this->parseSubnetData($data),$this->calculateTableSize($subnet-$net)));
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
/*
start at the network address,
stop when the subnet is larger than the network + the networkmask ,
increment by the subnetmask (+1 to move it into the subnet area)
*/

	function _findnetworks($network, $networkmask, $subnetmask){
        	$subnets = array();
	        if ($networkmask > $subnetmask) return ("CIDR validation failed - Netmask larger than subnetmask\n");
	        $networkmask = $this->cidr2mask($networkmask); // net mask checks and conversion
	        $subnetmask = $this->cidr2mask($subnetmask); //  subnet mask checks and conversion
	        if (!($networkmask & $subnetmask)) return ("CIDR validation failed - to big or to small\n");
	        $network = $network & $networkmask; /* make sure the network is infact a network address by xor'ing the network mask */
	        $networkmask = ~ $networkmask ; // invert masks
        	$subnetmask = ~ $subnetmask ;  //invert masks
	        for ( $subnet = $network; $subnet <= ($network ^ $networkmask ^ $subnetmask ); $subnet += $subnetmask + 1) { 
	                $subnets[] = $subnet;
	        } 
        	return $subnets;
	}
	
	/**
	*/
	function _maketables($subnets,$knownnetworks,$tablewidth) 
	{
	$output = "^  [[..:main|UP]]  "; 
/* Hi Jack,

I'm sorry for making a mess of the code below. I'm sure you can fix it up with some case statements or something.
*/
				// This is to create the header row
	for ($i = 1; $i <= $tablewidth[0]; $i++) {
		$output .= "^";
	}
	$output .= "\n";
        $lastmatch = array(0 => 0,1 => 0); //0=ip address, 1=subnetmask
	$loopwidth = 0; //this is how far the loop has gone
        foreach ($subnets as $subnet){ //run through each subnet
                if ($lastmatch[0] or $lastmatch[1]){ //Did we spot a known subnet on the last loop?
                        if (($subnet & $lastmatch[1])==$lastmatch[0]){ // check if the subnet fits within the last spotted s$
				if ($loopwidth <> 0){ //check to see if we are the first cell in a row so we can repeat the message
                                	$output .= "^"; //merge this cell
				}else {
					$output .= $this->generateLink(long2ip($lastmatch[0]), //ip
								$knownnetworks[$lastmatch[0]][0], //mask
								$knownnetworks[$lastmatch[0]][1]); //desc
	//				$output .= "^   [[" . long2ip($lastmatch[0])."/".$this->mask2cidr($knownnetworks[$lastmatch[0]][0]) . "]] \\\\ " . $knownnetworks[$lastmatch[0]][1]."   ";
				}
                        } else { // if it's not, it means we have gone passed out subnet mask and need to reset
				if(in_array($subnet, array_keys($knownnetworks))){  //is the subnet known?
					$output .= $this->generateLink(long2ip($subnet),
								$knownnetworks[$subnet][0],
								$knownnetworks[$subnet][1]);

//                                	$output .= "^   " . long2ip($subnet) ."/".$this->mask2cidr($knownnetworks[$subnet][0]) . " \\\\ " . $knownnetworks[$subnet][1]."   ";
	                                $lastmatch[0] = $subnet;
        	                        $lastmatch[1] = $knownnetworks[$subnet][0];
				} else { //else we reset everything
	                                $lastmatch = array(0 => 0,1 => 0);
        	                        $output .= "|  " . long2ip($subnet) . "   ";
				}
                        }
                }  else {
                        if(in_array($subnet, array_keys($knownnetworks))){  //is the subnet known?
                                        $output .= $this->generateLink(long2ip($subnet), //ip
                                                                $knownnetworks[$subnet][0], //mask
                                                                $knownnetworks[$subnet][1]); //desc
  //                              $output .= "^   [[".long2ip($subnet)."|" . long2ip($subnet)."/".$this->mask2cidr($knownnetworks[$subnet][0]) . "]] \\\\ " . $knownnetworks[$subnet][1]."   ";
                                $lastmatch[0] = $subnet;
                                $lastmatch[1] = $knownnetworks[$subnet][0];
                        } else {                                                //not known subnet
                            $output .=  "|   " . long2ip($subnet) ."   "; //just print the IP
                        }
                }
		$loopwidth++;	
		if ($loopwidth > $tablewidth[0]-1){
			$output .= "|\n";
			$loopwidth = 0;
		}
        }
		return $output;
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
	
	/**
	Parses the "<ipmap 0.0.0.0/X/Y>" line.
	@param lineData the first line.
	@return An array containing the relevant data in order of appearance.
	*/
	function parseFirstLine($lineData)
	{
		//echo $lineData;
		
		list($baseIP, $networkSize, $subnetSize) = preg_split("/\//u", substr($lineData, 7, -1), 3);
		$subnetSize = strtok($subnetSize,">");
		
		//echo $baseIP." ".$networkSize." ".$subnetSize;
		
		return(array($baseIP,$networkSize,$subnetSize));
	}
	
	/**
	@param data Raw data list.
	@return An array containing information about the subnets.
	*/
	function parseSubnetData($data)
	{
		$knownsubnets = array ();
		$lines = explode("\n",$data);
		foreach ($lines as $line){
			if ($line){ //remove blank lines
				list($ipsubnet,$description) = explode("-",$line,2); //172.2.7.2/16  and description
				$ipsubnet = trim($ipsubnet," *"); //clean up the ip address ( remove the * )
				list($ip, $cidr) = explode ("/",$ipsubnet);
				$knownsubnets[ ip2long($ip) & $this->cidr2mask($cidr) ] = array($this->cidr2mask($cidr),$description); //and'd to ensure correct network


			}
		}

		return($knownsubnets);
	}
	function generateLink($ip,$subnetmask,$descrption){
		$cidr = $this->mask2cidr($subnetmask);
		return("^  [[.:$ip\_$cidr/main|$ip/$cidr]]  \\\\ $descrption  ");
	}	
	function generateTable_XHTML()
	{
		return(0);
	}
	function cidr2mask($cidr)
	{
        	if (($cidr > 0) & ($cidr <= 32)){
                	$mask = 4294967296 - pow(2, 32-$cidr);
        	        return $mask;
        	} else {
                	return false;
        	} 
	}
	function mask2cidr($mask){
		$cidr = (32 * log(2)-log(4294967296-$mask))/(log(2));
		return($cidr);
	}


}
?>
