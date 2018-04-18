<?php
include('includes/vars.php');
include("includes/functions.php");
$request = $_SERVER['REQUEST_URI'];
header("HTTP/1.1 404 Not Found");
?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="css/layout-january.css" />
<link rel="stylesheet" type="text/css" href="themes/Darkness.css" />
<title>Badgers!</title>
</head>
<body>
<br><br><br>
<h2 align="center">404 Error!</h2>
<br><br>
<h2 align="center">It's all gone horribly wrong</h2>
<br><br>
<?php
print '<h3 align="center">The document &quot;'.$request."&quot; doesn't exist. Are you sure you know what you're doing?</h3>";
?>
</body>
</html>