<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=zzzcms;charset=utf8', 'root', 'rootrootroot123123123');
$rows = $pdo->query('SELECT lid,l_alias,sitetitle,siteurl FROM zzz_language LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_UNESCAPED_UNICODE) . PHP_EOL;
$c = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo 'tables=' . count($c) . PHP_EOL;
