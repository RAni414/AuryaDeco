<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../../includes/deco-service.php';
require __DIR__ . '/../../includes/deco-simulation.php';
putenv('OPENAI_IMAGE_API_KEY=unit-test-placeholder');
if (deco_simulation_enabled()) throw new RuntimeException('Paid image service must remain disabled.');
try {
    deco_simulation([], '', '');
    throw new RuntimeException('Simulation must not run.');
} catch (DecoError $e) {
    if ($e->status !== 503) throw $e;
    echo "PASS paid simulation disabled even with a legacy image key\n";
} finally { putenv('OPENAI_IMAGE_API_KEY'); }
