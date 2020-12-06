function displaySettings(event) {
	var host = $(event.target).attr('value');
	$('[name="mpd_host"]').val(multihosts[host].host);
	$('[name="mpd_port"]').val(multihosts[host].port);
	$('[name="mpd_password"]').val(multihosts[host].password);
	$('[name="unix_socket"]').val(multihosts[host].socket);
}