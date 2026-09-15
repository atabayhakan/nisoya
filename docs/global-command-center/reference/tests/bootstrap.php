<?php

$root = dirname(__DIR__, 4);
$loader = require $root.'/vendor/autoload.php';
$loader->addPsr4('App\\', dirname(__DIR__).'/app', true);
