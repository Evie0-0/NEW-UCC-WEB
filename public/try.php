<?php
declare(strict_types=1);

use Repositories\UserRepository;

$boot = require_once __DIR__ . '/../bootstrap/app.php';

$repo = $boot['container']->get(UserRepository::class);
var_dump($repo->testLoadUser());
