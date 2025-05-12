<?php
spl_autoload_register(function ($class) {
    // Convertir namespace en chemin de fichier
    $map = [
        'App\\Database\\' => __DIR__ . '/class/Database/',
        'App\\Tags\\' => __DIR__ . '/class/Tags/',
        'App\\Utils\\' => __DIR__ . '/class/Utils'
    ];

    foreach ($map as $prefix => $base_dir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $class_name = substr($class, $len);
        $file = $base_dir . $class_name . '.php';

        // Si le fichier existe, le charger
        if (file_exists($file)) {
            require $file;
            return true;
        }
    }

    return false;
});
