<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit74ae319619ca49afda668a289cb3cfbd
{
    public static $prefixLengthsPsr4 = array (
        'l' => 
        array (
            'libphonenumber\\' => 15,
        ),
        'M' => 
        array (
            'MaxMind\\WebService\\' => 19,
            'MaxMind\\Exception\\' => 18,
            'MaxMind\\Db\\' => 11,
        ),
        'G' => 
        array (
            'Giggsey\\Locale\\' => 15,
            'GeoIp2\\' => 7,
        ),
        'F' => 
        array (
            'Firebase\\JWT\\' => 13,
        ),
        'C' => 
        array (
            'Composer\\CaBundle\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'libphonenumber\\' => 
        array (
            0 => __DIR__ . '/vendor' . '/giggsey/libphonenumber-for-php/src',
        ),
        'MaxMind\\WebService\\' => 
        array (
            0 => __DIR__ . '/vendor' . '/maxmind/web-service-common/src/WebService',
        ),
        'MaxMind\\Exception\\' => 
        array (
            0 => __DIR__ . '/vendor' . '/maxmind/web-service-common/src/Exception',
        ),
        'MaxMind\\Db\\' => 
        array (
            0 => __DIR__ . '/vendor' . '/maxmind-db/reader/src/MaxMind/Db',
        ),
        'Giggsey\\Locale\\' => 
        array (
            0 => __DIR__ . '/vendor' . '/giggsey/locale/src',
        ),
        'GeoIp2\\' => 
        array (
            0 => __DIR__ . '/vendor' . '/geoip2/geoip2/src',
        ),
        'Firebase\\JWT\\' => 
        array (
            0 => __DIR__ . '/..',
        ),
        'Composer\\CaBundle\\' => 
        array (
            0 => __DIR__ . '/..',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit74ae319619ca49afda668a289cb3cfbd::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit74ae319619ca49afda668a289cb3cfbd::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
