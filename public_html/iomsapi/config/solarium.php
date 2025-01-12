<?php
return [
    'endpoint' => [
        'localhost' => [
            'host' => env('SOLR_HOST', 'localhost'),
            'port' => env('SOLR_PORT', '8983'),
            'path' => env('SOLR_PATH', '/solr/'),
            'core' => env('SOLR_CORE', 'smileycar')
        ],
        'carhotspot' => [
            'host' => env('SOLR_HOST', 'localhost'),
            'port' => env('SOLR_PORT', '8983'),
            'path' => env('SOLR_PATH', '/solr/'),
            'core' => env('SOLR_CORE', 'carhotspot')
        ]
    ]
];
