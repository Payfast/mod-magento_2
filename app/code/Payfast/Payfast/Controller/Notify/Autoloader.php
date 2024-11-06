<?php

namespace Payfast\Payfast\Controller\Notify;

// Execute logic with side effects (require autoloader)
/**
 * Adds the autoload.php file from the common library
 *
 * @return void
 */
function requireAutoloader(): void
{
    require_once __DIR__ . '/../../../../../../vendor/autoload.php';
}

// Call the function to load the autoloader
requireAutoloader();
