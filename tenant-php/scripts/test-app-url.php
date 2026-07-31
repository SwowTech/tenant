<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Library\Support\AppUrl;

function assertEq(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL {$label}: expected=" . var_export($expected, true) . ' actual=' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
    echo "OK {$label}\n";
}

AppUrl::reset();
AppUrl::configure('http://127.0.0.1', 9501);
assertEq('scheme local', 'http', AppUrl::scheme());
assertEq('host local', '127.0.0.1', AppUrl::host());
assertEq('publicBase local port', 'http://127.0.0.1:9501', AppUrl::publicBase());
assertEq('tenant subdomain local', 'http://acme.localhost:9501', AppUrl::tenantAccessUrl('acme'));
assertEq('tenant empty domain', '', AppUrl::tenantAccessUrl(''));
assertEq('app open url local', 'http://acme.localhost:9501/swowtech/zzzcms/', AppUrl::appOpenUrl('swowtech/zzzcms', 'acme'));

AppUrl::reset();
AppUrl::configure('http://localhost', 9501);
assertEq('tenant subdomain localhost', 'http://acme.localhost:9501', AppUrl::tenantAccessUrl('acme'));

AppUrl::reset();
AppUrl::configure('https://api.example.com', 9501);
assertEq('publicBase prod no port', 'https://api.example.com', AppUrl::publicBase());
assertEq('tenant api host', 'https://acme.api.example.com', AppUrl::tenantAccessUrl('acme'));
assertEq('custom host + scheme', 'https://shop.customer.com', AppUrl::tenantAccessUrl('acme', 'shop.customer.com'));
assertEq('custom full url', 'https://shop.customer.com', AppUrl::tenantAccessUrl('acme', 'https://shop.customer.com'));

AppUrl::reset();
AppUrl::configure('http://127.0.0.1:9501', 9501); // 容错：去掉 URL 内端口
assertEq('strip port in APP_URL host', '127.0.0.1', AppUrl::host());
assertEq('strip port publicBase', 'http://127.0.0.1:9501', AppUrl::publicBase());

AppUrl::reset();
AppUrl::configure('https://example.com', 443);
assertEq('port 443 no append', 'https://example.com', AppUrl::publicBase());

echo "ALL PASS\n";
