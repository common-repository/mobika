<?php
spl_autoload_register(function ($className) {
    $prefix = 'Mobika\\';
    if (strpos($className, $prefix) !== 0) {
        return;
    }
    $filename = str_replace('\\', DIRECTORY_SEPARATOR, substr($className, strlen($prefix))) . '.php';
    $filepath = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $filename;
    if (!is_readable($filepath)) {
        return;
    }
    require $filepath;
});
