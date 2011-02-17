<?php
/* TODO
 - remove if statement to break - find a solution
 - validate masks are all ones
 - add in code for detecting subnets
 - mask and network validtation could be done via functions and used else where
*/

$network = ip2long("172.16.0.0"); /* this is the network address as a number */
$networkmask = 16; /* network mask inverted */
$subnetmask = 24; /* subnet mask  inverted*/







/* http://forums.devnetwork.net/viewtopic.php?f=1&t=114832&sid=485e918e5322f81877c585d701f9b9d1&start=15 */

function isValidIPv4Mask($mask)
{
        if ($mask == (int) 0xffffffff || !$mask) return false;

        while (!($mask & 1))
                $mask >>= 1;

        return $mask == (int) 0xffffffff;
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
	$network = $network& ~ $networkmask; /* make sure the network is infact a network address by xor'ing the network mask */
	$networkmask = ~ cidr2mask($networkmask); // inverted net mask
	$subnetmask = ~ cidr2mask($subnetmask); // inverted subnet mask
	if ($networkmask & $subnetmask){
		for ( $subnet = $network; $subnet < $network ^ $networkmask ^ $subnetmask ; $subnet += $subnetmask + 1) { 
			if( $subnet > ($network ^ $networkmask ^ $subnetmask)){
				break;
			} 
			printsubnet($subnet);
		} 
	} else {
		return ("CIDR validation failed");
	}
}

function printsubnet($subnet){
print long2ip($subnet);
print "\n";
}

print findnetworks($network, $networkmask, $subnetmask);
?>
