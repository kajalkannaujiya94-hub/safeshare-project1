<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Prometheus\CollectorRegistry;
use Prometheus\Storage\APC;

class AppMetrics {
    private static $registry;
    private static $httpRequestsTotal;
    private static $httpRequestDuration;
    private static $uploadTotal;
    private static $loginTotal;

    public static function getRegistry() {
        if (!self::$registry) {
            self::$registry = new CollectorRegistry(new APC());
        }
        return self::$registry;
    }

    // Track HTTP requests
    public static function recordRequest($method, $endpoint, $status_code) {
        $counter = self::getRegistry()->getOrRegisterCounter(
            'app',
            'http_requests_total',
            'Total HTTP requests',
            ['method', 'endpoint', 'status']
        );
        $counter->incBy(1, [$method, $endpoint, $status_code]);
    }

    // Track response time
    public static function recordDuration($endpoint, $duration) {
        $histogram = self::getRegistry()->getOrRegisterHistogram(
            'app',
            'http_request_duration_seconds',
            'HTTP request duration',
            ['endpoint'],
            [0.1, 0.25, 0.5, 1.0, 2.5]
        );
        $histogram->observe($duration, [$endpoint]);
    }

    // Track uploads
    public static function recordUpload($status) {
        $counter = self::getRegistry()->getOrRegisterCounter(
            'app',
            'uploads_total',
            'Total file uploads',
            ['status']
        );
        $counter->incBy(1, [$status]);
    }

    // Track logins
    public static function recordLogin($status) {
        $counter = self::getRegistry()->getOrRegisterCounter(
            'app',
            'login_attempts_total',
            'Total login attempts',
            ['status']
        );
        $counter->incBy(1, [$status]);
    }

    // Add this to AppMetrics class
    public static function recordRegister($status) {
        $counter = self::getRegistry()->getOrRegisterCounter(
            'app',
            'register_attempts_total',
            'Total registration attempts',
            ['status']
        );
         $counter->incBy(1, [$status]);
    }
}