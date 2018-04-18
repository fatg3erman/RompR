<?php
$outputdata = array();
@open_mpd_connection();
if ($is_connected) {
    $outputs = do_mpd_command("outputs", true);
    close_mpd($connection);
    foreach ($outputs as $i => $n) {
        if (is_array($n)) {
            foreach ($n as $a => $b) {
                debuglog($i." - ".$b.":".$a,"AUDIO OUTPUT");
                $outputdata[$a][$i] = $b;
            }
        } else {
            debuglog($i." - ".$n,"AUDIO OUTPUT");
            $outputdata[0][$i] = $n;
        }
    }
}
close_mpd($connection);

function printOutputCheckboxes() {
    global $outputdata;
    for ($i = 0; $i < count($outputdata); $i++) {
        print '<div class="styledinputs">';
        print '<input type="checkbox" id="outputbutton_'.$i.'"';
        if ($outputdata[$i]['outputenabled'] == 1) {
            print ' checked';
        }
        print '><label for="outputbutton_'.$i.'" onclick="outputswitch('.$i.')">'.
            $outputdata[$i]['outputname'].'</label>';
        print '</div>';
    }
}

?>