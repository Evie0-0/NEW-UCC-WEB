<?php

require_once __DIR__  . '../bootstrap/app.php';

$config = require_once '../config/app.php';

echo $config['env'] ?? '';
