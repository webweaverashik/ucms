<?php

return [
    'node_path' => '/home/uniqueco/.nvm/versions/node/v22.16.0/bin/node',
    'npm_path'  => '/home/uniqueco/.nvm/versions/node/v22.16.0/bin/npm',

    // Add these critical options for shared hosting
    'options'   => [
        'args'              => [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--single-process',
            '--disable-dev-shm-usage',
        ],
        'ignoreHttpsErrors' => true,
    ],
];
