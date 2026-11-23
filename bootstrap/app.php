<?php
declare(strict_types=1);

use Core\Database;
use Core\ViewRenderer;
use Dotenv\Dotenv;
use Helpers\Helper;
use Monolog\ErrorHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouteCollection;
define
('BASE_PATH', dirname(__DIR__));
$logsDirectory = BASE_PATH . '/storage/logs';

if (!is_dir($logsDirectory)) {
    if (!mkdir($logsDirectory, 0755, true) && !is_dir($logsDirectory)) {
        throw new RuntimeException('Failed to create logs directory.');
    }
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', $logsDirectory . '/php-error.log');

require_once BASE_PATH . '/vendor/autoload.php';

Dotenv::createImmutable(BASE_PATH)->load();

$config = [
    'app' => require BASE_PATH . '/config/app.php',
    'database' => require BASE_PATH . '/config/database.php',
];

date_default_timezone_set($config['app']['timezone']);

$errorLogger = new Logger('error');
$errorLogger->pushHandler(
    new RotatingFileHandler(
        filename: $logsDirectory . '/error.log',
        maxFiles: 30,
        level: Level::Notice
    )
);

ErrorHandler::register($errorLogger);

$appLogger = new Logger('app');
$appLogger->pushHandler(
    new RotatingFileHandler(
        filename: $logsDirectory . '/app.log',
        maxFiles: 30,
        level: Level::Info
    )
);

$env = $config['app']['env'];

if (!in_array($env, ['development', 'production'], true)) {
    throw new RuntimeException('Invalid application environment.');
}

$autoRebuild = $env !== 'production';

$cacheDirectory = BASE_PATH . '/storage/cache/' . $env;
$cachePath = $cacheDirectory . '/container.php';
$containerClass = 'Ucc' . ucfirst($env) . 'Container';

if ($autoRebuild) {
    if (!is_dir($cacheDirectory)) {
        if (!mkdir($cacheDirectory, 0755, true) && !is_dir($cacheDirectory)) {
            throw new RuntimeException('Failed to create cache directory.');
        }
    }

    $cache = new ConfigCache($cachePath, true);

    if (!$cache->isFresh()) {
        $builder = require BASE_PATH . '/bootstrap/container.php';
        $builder->compile();

        $dumper = new PhpDumper($builder);

        $content = $dumper->dump(['class' => $containerClass]);
        /** @var string $content */
        $cache->write(
            $content,
            $builder->getResources()
        );
    }
} else {
    // The compiled container for production must exist before deployment
    if (!is_file($cachePath) || !is_readable($cachePath)) {
        throw new RuntimeException('Production container cache is missing or unreadable. Build it before deployment.');
    }
}

require_once $cachePath;

$container = new $containerClass();
$container->set(Database::class, new Database(
$config['database']));
$container->set('logger.app', $appLogger);
$container->set('logger.error', $errorLogger);

$container->set(ViewRenderer::class, new ViewRenderer(
    BASE_PATH . '/resources/views',
    $config['app']['base_path'],
    $config['app']['site'],
    $container->get(Helper::class),
    $container->get(RequestStack::class)
));

$webRoutes = require BASE_PATH . '/routes/web.php';
$apiRoutes = require BASE_PATH . '/routes/api.php';

$apiRoutes->addPrefix('/api');

$routes = new RouteCollection();
$routes->addCollection($webRoutes);
$routes->addCollection($apiRoutes);

$container->set(RouteCollection::class, $routes);

return $container;
