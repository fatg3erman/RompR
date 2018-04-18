<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>RompR</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=0" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<script type="text/javascript" src="jquery/jquery-2.1.4.min.js"></script>
<script type="text/javascript" src="ui/functions.js"></script>
<script language="javascript">
$(document).ready(function() {
	var ws = getWindowSize();
	var mw = Math.max(ws.x, ws.y);
	if (!mw || mw <= 1024) {
		setCookie('skin','phone',3650);
	} else {
		setCookie('skin','desktop',3650);
	}
	location.reload(true);
});
</script>
</head>
<body>
</body>
</html>
