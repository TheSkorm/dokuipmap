<?php
/* TODO
 - remove if statement to break - find a solution
 - add in code for detecting subnets
 - mask and network validtation could be done via functions and used else where
*/

$network = ip2long("172.16.0.0"); /* this is the network address as a number */
$networkmask = 16; /* network mask inverted */
$subnetmask = 24; /* subnet mask  inverted*/



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
	if ($networkmask > $subnetmask) return ("CIDR validation failed - Netmask larger than subnetmask\n");
	$networkmask = cidr2mask($networkmask); // net mask checks and conversion
	$subnetmask = cidr2mask($subnetmask); //  subnet mask checks and conversion
	if (!($networkmask & $subnetmask)) return ("CIDR validation failed - to big or to small\n");
	$network = $network & $networkmask; /* make sure the network is infact a network address by xor'ing the network mask */
	$networkmask = ~ $networkmask ; // invert masks
	$subnetmask = ~ $subnetmask ;  //invert masks
	for ( $subnet = $network; $subnet <= ($network ^ $networkmask ^ $subnetmask ); $subnet += $subnetmask + 1) { 
		printsubnet($subnet);
	} 
}

function printsubnet($subnet){
	print long2ip($subnet); /* for now just print out the ip address */
	print "\n";
}

print findnetworks($network, $networkmask, $subnetmask);

?>
