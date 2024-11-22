<?php

require __DIR__ . '/vendor/autoload.php';

if (class_exists('Dotenv\Dotenv')) {
    echo "Dotenv\Dotenv class found!";
} else {
    echo "Dotenv\Dotenv class NOT found!";
}
