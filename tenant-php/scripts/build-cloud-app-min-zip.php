<?php

declare(strict_types=1);

$root = __DIR__ . '/fixtures';
$src = $root . '/cloud-app-min';
$zipPath = $root . '/cloud-app-min.zip';

if (! is_dir($src)) {
    fwrite(STDERR, "missing source dir: {$src}\n");
    exit(1);
}

if (is_file($zipPath)) {
    unlink($zipPath);
}

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
    fwrite(STDERR, "zip open fail\n");
    exit(1);
}
$zip->addFile($src . '/mine.json', 'mine.json');
$zip->addFile($src . '/src/ConfigProvider.php', 'src/ConfigProvider.php');
$zip->close();

echo 'zip=' . $zipPath . PHP_EOL;
echo 'sha256=' . hash_file('sha256', $zipPath) . PHP_EOL;
