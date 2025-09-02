<?php
require_once 'class.DatabaseConnector.php';
putenv("DB_MODE=remote");

$config = [
    'host' => '850cl9.myd.infomaniak.com',
    'dbname' => '850cl9_mysquash',
    'username' => '850cl9_dbdump',
    'password' => 'NXaFuZxFf_6WpTuFkqDw8',
    'remote_script_url' => 'https://ranking.squash.ch/ClubRankingRanking/query.php'
];

$db = new DatabaseConnector($config);