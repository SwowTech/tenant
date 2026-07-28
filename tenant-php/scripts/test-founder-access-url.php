<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Library\Support\AppUrl;

AppUrl::reset();
AppUrl::configure('https://api.example.com', 9501);
$row = ['domain' => 'acme', 'custom_domain' => ''];
$url = AppUrl::tenantAccessUrl($row['domain'], $row['custom_domain']);
if ($url !== 'https://acme.api.example.com') {
    fwrite(STDERR, "FAIL $url\n");
    exit(1);
}
echo "OK founder access_url shape\n";
