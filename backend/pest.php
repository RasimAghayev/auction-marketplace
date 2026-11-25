<?php

use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;

// Set testing environment variables BEFORE bootstrapping Laravel
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'mongodb_testing';
$_ENV['MONGODB_URI_TESTING'] = 'mongodb://admin:4Y4fEgzujaOswRlFJcKF0nC2VpZ0ZZUB@mongodb:27017/?authSource=admin';
$_ENV['MONGODB_DATABASE_TESTING'] = 'auction_marketplace_testing';
$_ENV['MONGODB_USERNAME_TESTING'] = 'admin';
$_ENV['MONGODB_PASSWORD_TESTING'] = '4Y4fEgzujaOswRlFJcKF0nC2VpZ0ZZUB';
$_ENV['MONGODB_AUTH_SOURCE_TESTING'] = 'admin';
$_ENV['MONGODB_REPLICA_SET_TESTING'] = '';
$_ENV['CACHE_DRIVER'] = 'array';
$_ENV['MAIL_MAILER'] = 'array';
$_ENV['QUEUE_CONNECTION'] = 'sync';
$_ENV['SESSION_DRIVER'] = 'array';
$_ENV['BCRYPT_ROUNDS'] = '4';
$_ENV['TELESCOPE_ENABLED'] = 'false';

putenv('APP_ENV=testing');
putenv('DB_CONNECTION=mongodb_testing');
putenv('MONGODB_URI_TESTING=mongodb://admin:4Y4fEgzujaOswRlFJcKF0nC2VpZ0ZZUB@mongodb:27017/?authSource=admin');
putenv('MONGODB_REPLICA_SET_TESTING=');
putenv('CACHE_DRIVER=array');
putenv('MAIL_MAILER=array');
putenv('QUEUE_CONNECTION=sync');
putenv('SESSION_DRIVER=array');
putenv('BCRYPT_ROUNDS=4');
putenv('TELESCOPE_ENABLED=false');

// Create the application
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Set up the test case
uses(LaravelTestCase::class)->in('Feature');
uses(LaravelTestCase::class)->in('Unit');