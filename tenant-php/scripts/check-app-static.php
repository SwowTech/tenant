<?php

declare(strict_types=1);

/**
 * 检查应用 admin SPA 关键静态资源是否在磁盘上（正式环境白屏排查）.
 *
 * 用法: php scripts/check-app-static.php [identifier]
 * 默认: swowtech/xunlian-trace-tp6
 */
$identifier = $argv[1] ?? 'swowtech/xunlian-trace-tp6';
$base = dirname(__DIR__) . '/apps/' . $identifier . '/public/admin';

$required = [
    'index.html',
];

if (! is_dir($base)) {
    fwrite(STDERR, "FAIL: admin dir missing: {$base}\n");
    exit(1);
}

$html = $base . '/index.html';
if (! is_file($html)) {
    fwrite(STDERR, "FAIL: index.html missing\n");
    exit(1);
}

$raw = (string) file_get_contents($html);
preg_match_all('#(?:src|href)="[^"]*/admin/([^"]+\.(?:js|css))"#', $raw, $m);
$assets = array_values(array_unique($m[1] ?? []));

$missing = [];
foreach ($assets as $rel) {
    if (! is_file($base . '/' . $rel)) {
        $missing[] = $rel;
    }
}

$total = count(scandir($base) ?: []) - 2;
echo "admin dir: {$base}\n";
echo "files: {$total}\n";
echo "index.html refs: " . count($assets) . "\n";

if ($missing === []) {
    echo "OK: all index.html js/css assets present\n";
    exit(0);
}

echo "MISSING (" . count($missing) . "):\n";
foreach ($missing as $rel) {
    echo "  - {$rel}\n";
}
fwrite(STDERR, "FAIL: incomplete admin build; git pull apps/.../public/admin and restart 9501\n");
exit(2);
