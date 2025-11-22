<?php
echo "<pre>";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? '') . PHP_EOL;
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? '') . PHP_EOL;
echo "getcwd(): " . getcwd() . PHP_EOL;
echo "__DIR__: " . __DIR__ . PHP_EOL;
echo "</pre>";
