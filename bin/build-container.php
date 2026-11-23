<?php
declare(strict_types=1);

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

$cacheDirectory = BASE_PATH . '/storage/cache/production';

if (!is_dir($cacheDirectory)) {
    if (!mkdir($cacheDirectory, 0755, true) && !is_dir($cacheDirectory)) {
        throw new RuntimeException('Failed to create cache directory.');
    }
}

$cachePath = $cacheDirectory . '/container.php';

$builder = require BASE_PATH . '/bootstrap/container.php';
$builder->compile();

$dumper = new PhpDumper($builder);

$cache = new ConfigCache($cachePath, true);
$content = $dumper->dump(['class' => 'UccProductionContainer']);
/** @var string $content */
$cache->write($content);
