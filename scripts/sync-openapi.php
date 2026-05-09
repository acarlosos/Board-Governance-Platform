<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

require __DIR__ . '/../vendor/autoload.php';

$root = dirname(__DIR__);
$yamlPath = $root . '/docs/openapi/v1.yaml';
$jsonPath = $root . '/public/openapi/v1.json';

if (! file_exists($yamlPath)) {
    fwrite(STDERR, "YAML not found: {$yamlPath}\n");
    exit(1);
}

$data = Yaml::parseFile($yamlPath);

if (! is_array($data)) {
    fwrite(STDERR, "Invalid YAML structure.\n");
    exit(1);
}

$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

if ($json === false) {
    fwrite(STDERR, "JSON encode failed.\n");
    exit(1);
}

file_put_contents($jsonPath, $json . "\n");

fwrite(STDOUT, "Wrote: {$jsonPath}\n");

