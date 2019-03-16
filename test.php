<?php

class mything {
	public function my_thing() {
		print "ARSE";
	}
}

$classname = 'mything';

class myotherthing extends $classname {
	public function my_other_thing() {
		print "BISCUITS";
	}
}

$a = new myotherthing();
$a->my_thing();
$a->my_other_thing();


?>
