<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit8b102dcecc3e506fe77fc7d2569159d8
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'ShortCode\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ShortCode\\' => 
        array (
            0 => __DIR__ . '/..' . '/ajaxray/short-code/src/ShortCode',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit8b102dcecc3e506fe77fc7d2569159d8::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit8b102dcecc3e506fe77fc7d2569159d8::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}