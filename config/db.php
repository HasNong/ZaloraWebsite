<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/firebase_helpers.php';

use Kreait\Firebase\Factory;

$factory = (new Factory)
    ->withServiceAccount(__DIR__ . '/../private/zaloramalalay-6eb75-firebase-adminsdk-fbsvc-4c7fa7c3f5.json')
    ->withDatabaseUri('https://zaloramalalay-6eb75-default-rtdb.asia-southeast1.firebasedatabase.app/');

$database = $factory->createDatabase();
