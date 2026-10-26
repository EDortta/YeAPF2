<?php

declare(strict_types=1);

require __DIR__ . '/routes.php';

dispatchTestRequest(
    strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
    parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
    $_GET,
    file_get_contents('php://input') ?: '',
    function_exists('getallheaders') ? getallheaders() : []
);
