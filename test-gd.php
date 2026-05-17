<?php
echo extension_loaded('gd') ? 'GD: OK' : 'GD: NOT FOUND';
echo PHP_EOL;
$info = gd_info();
echo 'Version: ' . $info['GD Version'] . PHP_EOL;
