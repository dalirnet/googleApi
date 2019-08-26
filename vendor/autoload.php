<?php

class MyLoader
{
    public static function register()
    {
        spl_autoload_register(["MyLoader", "loadLibrary"]);
    }

    public static function loadLibrary($path)
    {
        if (DIRECTORY_SEPARATOR == "/") {
            $path = str_replace("\\", "/", $path);
        }
        $file = __DIR__ . DIRECTORY_SEPARATOR . $path . ".php";
        if (file_exists($file)) {
            require $file;
        }
    }
}

MyLoader::register();


