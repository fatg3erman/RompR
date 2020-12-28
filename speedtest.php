<?php

## In order of speed, fastest first

# if ($c)
# if ($c === true)
# if ($c === 1) WHEN $c IS A STRING
# $c = ', '
# if ($c == 1)
# if (!$c)
# if ($c == true)
# if ($c === 1) WHEN $c IS AN INTEGER

$results = [];

$c = 0;
$t = microtime(true);
for ($i = 0; $i < 100000000; $i++) {
	if ($c == 1) $b = 2;
}
$end = microtime(true);

$results["=="] = $end - $t;

$t = microtime(true);
for ($i = 0; $i < 100000000; $i++) {
	if ($c === 1) $b = 2;
}
$end = microtime(true);

$results["==="] = $end - $t;

$c = 'hello';
$t = microtime(true);
for ($i = 0; $i < 100000000; $i++) {
	if ($c === 1) $b = 2;
}
$end = microtime(true);

$results["=== with different types"] = $end - $t;

$c = false;
$t = microtime(true);
for ($i = 0; $i < 100000000; $i++) {
	if ($c) $b = 2;
}
$end = microtime(true);

$results["true"] = $end - $t;

$c = false;
$t = microtime(true);
for ($i = 0; $i < 100000000; $i++) {
	if ($c == true) $b = 2;
}
$end = microtime(true);

$results["== true"] = $end - $t;

$c = false;
$t = microtime(true);
for ($i = 0; $i < 100000000; $i++) {
	if ($c === true) $b = 2;
}
$end = microtime(true);

$results["=== true"] = $end - $t;

$c = true;
$t = microtime(true);
for ($i = 0; $i < 100000000; $i++) {
	if (!$c) $b = 2;
}
$end = microtime(true);

$results["!"] = $end - $t;

$c = '';
$t = microtime(true);
for ($i = 0; $i < 100000000; $i++) {
	$c = ', ';
}
$end = microtime(true);

$results["string assignment"] = $end - $t;

$c = 10;
$t = microtime(true);
for ($i = 0; $i < 100000000; $i++) {
	if ($c === null) $b = 2;
}
$end = microtime(true);

$results["=== null"] = $end - $t;

$c = 10;
$t = microtime(true);
for ($i = 0; $i < 100000000; $i++) {
	if ($c == null) $b = 2;
}
$end = microtime(true);

$results["== null"] = $end - $t;

$c = 'cake';
$t = microtime(true);
for ($i = 0; $i < 100000000; $i++) {
	if ($c === null || $c === '') $b = 2;
}
$end = microtime(true);

$results["=== null || === ''"] = $end - $t;

$c = 'cake';
$t = microtime(true);
for ($i = 0; $i < 100000000; $i++) {
	if (!$c) $b = 2;
}
$end = microtime(true);

$results["!c"] = $end - $t;

asort($results);
foreach ($results as $k => $v) {
	print $k."\t".$v."\n";
}
?>
