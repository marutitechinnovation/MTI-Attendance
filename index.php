<?php

// Shared-hosting fallback:
// If the web server document root points to the project root instead of /public,
// forward the request to CodeIgniter's front controller.

require __DIR__ . '/public/index.php';

