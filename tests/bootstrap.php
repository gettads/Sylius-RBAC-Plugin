<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

if (empty($_ENV['APP_ENV'])) {
    throw new RuntimeException("Environment variable 'APP_ENV' is not set");
}
