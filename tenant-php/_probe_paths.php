<?php

$j = json_decode(file_get_contents(__DIR__ . '/storage/swagger/http.json'), true);
foreach (array_keys($j['paths'] ?? []) as $p) {
    if (str_contains($p, 'api') || str_contains($p, 'tenant')) {
        echo $p, PHP_EOL;
    }
}
