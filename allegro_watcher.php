<?php

/**
 * Bootstrap file
 */


require __DIR__ . '/vendor/autoload.php';
require 'src/Console.php';

(new Console())->run();