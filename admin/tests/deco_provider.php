<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Compatibility entry point: provider contract is now Gemini, always mocked here.
require __DIR__ . '/gemini_provider.php';
