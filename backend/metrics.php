<?php
require_once __DIR__ . '/vendor/autoload.php';

use Prometheus\CollectorRegistry;
use Prometheus\Storage\APC;
use Prometheus\RenderTextFormat;

// Use APCu for storage (fast, in-memory)
$registry = new CollectorRegistry(new APC());

$renderer = new RenderTextFormat();
$result = $renderer->render($registry->getMetricFamilySamples());

header('Content-Type: ' . RenderTextFormat::MIME_TYPE);
echo $result;