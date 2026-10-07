<?php

return ['paths' => ['api/*'], 'allowed_methods' => ['GET', 'POST', 'OPTIONS'], 'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000'))), 'allowed_origins_patterns' => [], 'allowed_headers' => ['Content-Type', 'Accept', 'Authorization'], 'exposed_headers' => [], 'max_age' => 0, 'supports_credentials' => false];
