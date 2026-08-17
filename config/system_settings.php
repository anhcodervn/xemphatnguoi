<?php

return [
    'defaults' => [
        'seo' => [
            'custom_header' => '',
            'robots_txt' => <<<'ROBOTS'
User-agent: *
Allow: /
Disallow: /dashboard
Disallow: /admin
Disallow: /auth
Disallow: /login
Disallow: /api
Disallow: /tra-cuu/
ROBOTS,
            'ads_txt' => 'google.com, pub-4352299256001618, DIRECT, f08c47fec0942fa0',
        ],
    ],
];
