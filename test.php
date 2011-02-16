<?php
/* TODO
 - remove if statement to break - find a solution
 - validate masks are all ones
 - add in code for detecting subnets
 - mask and network validtation could be done via functions and used else where
*/
$network = 2886729729; /* this is the network address as a number */
$networkmask = 65535; /* network mask */
$subnetmask = 255; /* subnet mask */

$network = $network& ~ $networkmask; /* make sure the network is infact a network address by xor'ing the network mask */

$format = '%0' . (PHP_INT_SIZE * 8) . "b\n"; /*only used for debuging formatting */
/*  debugging shiz
print "Network\n";
printf($format, $network);
print "increment until\n";
printf($format, $network ^ $networkmask ^ $subnetmask);
print "increment by\n";
printf($format, $subnetmask + 1 );
print "subnetmask\n";
printf($format, $subnetmask );
*/

/*
start at the network address,
stop when the subnet is larger than the network + the networkmask ,
increment by the subnetmask (+1 to move it into the subnet area)
*/
for ( $subnet = $network; $subnet < $network ^ $networkmask ^ $subnetmask ; $subnet += $subnetmask + 1) { 
if( $subnet > ($network ^ $networkmask ^ $subnetmask)){
break;
} 

print "\n";
printf($format,$subnet);
printf($format,$network ^ $networkmask ^ $subnetmask);

} 

?>
