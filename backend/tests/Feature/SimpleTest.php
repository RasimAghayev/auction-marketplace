<?php

use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;

uses(LaravelTestCase::class);

test('simple test', function () {
    die("SIMPLE TEST REACHED");
    $this->assertTrue(true);
});