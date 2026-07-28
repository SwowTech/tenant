<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

Hyperf\Di\ClassLoader::init();
$container = require dirname(__DIR__) . '/config/container.php';
/** @var Hyperf\DbConnection\Db $db */
$db = $container->get(Hyperf\DbConnection\Db::class);
$rows = $db::select('select id, domain, custom_domain from ops_tenant order by id desc limit 10');
foreach ($rows as $r) {
    echo $r->id . ' | ' . $r->domain . ' | [' . $r->custom_domain . ']' . PHP_EOL;
}
