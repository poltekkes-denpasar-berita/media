<?php
// CLI runner to trigger gen.php actions (simulate POST boom_discover)
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['boom_discover'] = '1';
chdir(__DIR__);
include __DIR__ . '/gen.php';
echo "gen.php executed\n";
