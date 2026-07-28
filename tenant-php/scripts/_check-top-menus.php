<?php

declare(strict_types=1);

$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=mineadmin', 'root', 'rootrootroot123123123');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== parent_id=0 ===\n";
$rows = $pdo->query(
    "SELECT id, parent_id, name, path, sort, status,
            JSON_UNQUOTE(JSON_EXTRACT(meta, '$.title')) AS title
     FROM menu WHERE parent_id=0 ORDER BY sort, id"
)->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo implode("\t", $r) . "\n";
}

echo "=== setting:cloud:store ===\n";
$s = $pdo->query("SELECT id, parent_id, name, path, sort, status FROM menu WHERE name='setting:cloud:store'")->fetch(PDO::FETCH_ASSOC);
var_export($s);
echo "\n";
