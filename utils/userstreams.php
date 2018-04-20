<?php

chdir ('..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");
include ("backends/sql/backend.php");

debuglog("Doing User Radio Stuff","USERSTREAMS");

if (array_key_exists('populate', $_REQUEST)) {
    do_radio_list();
} else if (array_key_exists('remove', $_REQUEST)) {
    remove_user_radio_stream($_REQUEST['remove']);
    do_radio_list();
} else if (array_key_exists('order', $_REQUEST)) {
    save_radio_order($_REQUEST['order']);
} else if (array_key_exists('addfave', $_REQUEST)) {
    add_fave_station($_REQUEST);
    do_radio_list();
} else if (array_key_exists('updatename', $_REQUEST)) {
    update_radio_station_name($_REQUEST);
    do_radio_list();
}

function do_radio_list() {

    $playlists = get_user_radio_streams();

    foreach($playlists as $playlist) {
        print '<div class="clickable clickstream containerbox padright menuitem dropdown-container" name="'.$playlist['PlaylistUrl'].'" streamimg="'.$playlist['Image'].'" streamname="'.$playlist['StationName'].'">';
        print '<div class="smallcover fixed"><img class="smallcover" name="'.get_stream_imgkey($playlist['Stationindex']).'" src="'.$playlist['Image'].'" /></div>';
        print '<div class="expand stname" style="margin-left:4px">'.utf8_encode($playlist['StationName']).'</div>';
        print '<div class="fixed clickable clickradioremove clickicon" name="'.$playlist['Stationindex'].'"><i class="icon-cancel-circled playlisticon"></i></div>';
        print '</div>';
    }

}

?>
