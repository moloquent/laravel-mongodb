<?php

return [

    'connections' => [

        'mongodb' => [
            'name'         => 'mongodb',
            'driver'       => 'mongodb',
            'host'         => '127.0.0.1',
            'database'     => 'unittest',
            'use_mongo_id'     => true,
        ],

        'mysql' => [
            'driver'    => 'mysql',
            'host'      => '127.0.0.1',
            'database'  => 'unittest',
            'username'  => 'root',
            'password'  => 'root',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ],
    ],

];
