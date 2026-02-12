<?php

return [
        'environments' => [
            'production' => [
                'supervisor-high' => [
                    'connection' => 'redis',
                    'queue' => ['high'],
                    'balance' => 'auto',
                    'processes' => 3,
                    'tries' => 3,
                ],
            ],
        ],
];