<?php

$host = "localhost";
$port = "8888";
$dbname = "portals_analytic";
$user = "root";
$pwd = "root";

if(php_sapi_name() === 'cli')
{
    $host = "127.0.0.1";
    $port = "8889";
}

$db = new PDO('mysql:host='.$host.';port='.$port.';dbname='.$dbname.';charset=utf8', $user, $pwd);
