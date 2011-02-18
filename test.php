<?php
/* TODO
 - remove if statement to break - find a solution
 - add in code for detecting subnets
 - mask and network validtation could be done via functions and used else where
*/

$network = ip2long("172.16.0.0"); /* this is the network address as a number */
$networkmask = 16; /* network mask inverted */
$subnetmask = 24; /* subnet mask  inverted*/
$KnownNetworks  = array();
// all network keys should be checked using an XOR of the mask first.
$KnownNetworks[ip2long("172.16.7.0")] = cidr2mask(24);
$KnownNetworks[ip2long("172.16.16.0")] = cidr2mask(23);
print_r($KnownNetworks);
print_r(array_keys($KnownNetworks));



function cidr2mask($cidr)
{
	if (($cidr > 0) & ($cidr <= 32)){
		$mask = 4294967296 - pow(2, 32-$cidr);
		return $mask;
	} else {
		return false;
	}
}




function printbin($value){
	$format = '%0' . (PHP_INT_SIZE * 8) . "b\n"; 
	printf($format, $value);
}


/*
start at the network address,
stop when the subnet is larger than the network + the networkmask ,
increment by the subnetmask (+1 to move it into the subnet area)
*/
function findnetworks($network, $networkmask, $subnetmask){
	$subnets = array();
	if ($networkmask > $subnetmask) return ("CIDR validation failed - Netmask larger than subnetmask\n");
	$networkmask = cidr2mask($networkmask); // net mask checks and conversion
	$subnetmask = cidr2mask($subnetmask); //  subnet mask checks and conversion
	if (!($networkmask & $subnetmask)) return ("CIDR validation failed - to big or to small\n");
	$network = $network & $networkmask; /* make sure the network is infact a network address by xor'ing the network mask */
	$networkmask = ~ $networkmask ; // invert masks
	$subnetmask = ~ $subnetmask ;  //invert masks
	for ( $subnet = $network; $subnet <= ($network ^ $networkmask ^ $subnetmask ); $subnet += $subnetmask + 1) { 
		$subnets[] = $subnet;
	} 
	return $subnets;
}

function printsubnet($subnets,$knownnetworks){
	$lastmatch = array(0 => 0,1 => 0); //0=ip address, 1=subnetmask
	foreach ($subnets as $subnet){ //run through each subnet
		if ($lastmatch[0] or $lastmatch[1]){ //Did we spot a known subnet on the last loop?
			if (($subnet & $lastmatch[1])==$lastmatch[0]){ // check if the subnet fits within the last spotted subnet and subnet mask
				print long2ip($lastmatch[0]);
				print "* \n";
			} else { // if it's not, it means we have gone passed out subnet mask and need to reset
				$lastmatch = array(0 => 0,1 => 0);
                                print long2ip($subnet);
                                print "\n";
			}
		}  else {
			if(in_array($subnet, array_keys($knownnetworks))){  //is the subnet known?
				print long2ip($subnet);
				print "* \n";
				$lastmatch[0] = $subnet;
				$lastmatch[1] = $knownnetworks[$subnet];
			} else {						//not known subnet
                                print long2ip($subnet);
                                print "\n";

			}
		}
	}
}
print printsubnet(findnetworks($network, $networkmask, $subnetmask),$KnownNetworks);

?>
