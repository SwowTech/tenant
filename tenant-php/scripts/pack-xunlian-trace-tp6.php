<?php

declare(strict_types=1);

/**
 * 打包 swowtech/xunlian-trace-tp6 为 Cloud 市场上架 zip。
 *
 * Usage:
 *   php scripts/pack-xunlian-trace-tp6.php
 *   php scripts/pack-xunlian-trace-tp6.php --out=runtime/dist/foo.zip
 */
$basePath = dirname(__DIR__);
$appDir = $basePath . '/apps/swowtech/xunlian-trace-tp6';
$manifestFile = $appDir . '/app.json';

if (! is_file($manifestFile)) {
    fwrite(STDERR, "FAIL: app.json missing\n");
    exit(1);
}

/** @var array<string, mixed> $manifest */
$manifest = json_decode((string) file_get_contents($manifestFile), true);
if (! is_array($manifest) || ($manifest['name'] ?? '') !== 'swowtech/xunlian-trace-tp6') {
    fwrite(STDERR, "FAIL: invalid app.json\n");
    exit(1);
}

$version = (string) ($manifest['version'] ?? '0.0.0');
$out = $basePath . '/runtime/dist/swowtech-xunlian-trace-tp6-' . $version . '.zip';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--out=')) {
        $out = substr($arg, 6);
        if (! str_starts_with($out, '/') && ! preg_match('#^[A-Za-z]:[/\\\\]#', $out)) {
            $out = $basePath . '/' . ltrim(str_replace('\\', '/', $out), '/');
        }
    }
}

@mkdir(dirname($out), 0755, true);
@unlink($out);

$excludeExact = [
    'app.json', // 单独写入根，确保最新
    '.env',
    'composer.phar',
    'composer-setup.php',
    '.user.ini',
    '.travis.yml',
    '.gitignore',
];
$excludePrefixes = [
    'runtime/',
    'uploads/',
    'public/uploads/',
    'public/admin.bak/',
    '.git/',
    'node_modules/',
];

$zip = new ZipArchive();
if ($zip->open($out, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "FAIL: cannot create zip\n");
    exit(1);
}

$zip->addFromString('app.json', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

$appRoot = str_replace('\\', '/', realpath($appDir) ?: $appDir);
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($appDir, FilesystemIterator::SKIP_DOTS)
);
$count = 1;
foreach ($iterator as $file) {
    /** @var SplFileInfo $file */
    if (! $file->isFile()) {
        continue;
    }
    $full = str_replace('\\', '/', $file->getPathname());
    $rel = substr($full, strlen($appRoot) + 1);
    if ($rel === false || $rel === '') {
        continue;
    }
    if (in_array($rel, $excludeExact, true)) {
        continue;
    }
    $skip = false;
    foreach ($excludePrefixes as $prefix) {
        if (str_starts_with($rel, $prefix) || str_contains($rel, '/' . $prefix)) {
            $skip = true;
            break;
        }
    }
    if ($skip) {
        continue;
    }
    $zip->addFile($file->getPathname(), $rel);
    ++$count;
}
$zip->close();

$size = filesize($out) ?: 0;
$hash = hash_file('sha256', $out);
echo "OK pack {$out}\n";
echo "files≈{$count} size=" . round($size / 1048576, 1) . "MB sha256={$hash}\n";
echo "identifier={$manifest['name']} version={$version} edition=" . ($manifest['edition'] ?? '') . " family=" . ($manifest['family'] ?? '') . "\n";
