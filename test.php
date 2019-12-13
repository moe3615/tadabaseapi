<?php
require_once __DIR__ . '/vendor/autoload.php';
use Tadabase\Api;


$tb_api = new Api;
echo "<pre>";
print_r($tb_api->getRecords());
echo "</pre>";
