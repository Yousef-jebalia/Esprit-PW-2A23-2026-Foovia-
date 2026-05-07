<?php

declare(strict_types=1);

if (!function_exists('foovia_app_base_url')) {
    function foovia_app_base_url(): string
    {
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $mvcPosition = strpos($scriptName, '/MVC/');

        if ($mvcPosition === false) {
            return '';
        }

        $basePath = rtrim(substr($scriptName, 0, $mvcPosition), '/');
        $segments = array_map(
            static fn (string $segment): string => $segment === '' ? '' : rawurlencode(rawurldecode($segment)),
            explode('/', $basePath)
        );

        return implode('/', $segments);
    }
}

if (!function_exists('foovia_url')) {
    function foovia_url(string $path = ''): string
    {
        $baseUrl = foovia_app_base_url();
        $cleanPath = ltrim($path, '/');

        return $cleanPath === '' ? ($baseUrl === '' ? '/' : $baseUrl) : $baseUrl . '/' . $cleanPath;
    }
}
