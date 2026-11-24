<?php

use App\Models\User;

beforeEach(function () {
    // Debug: check the connection
    $conn = \Illuminate\Support\Facades\DB::connection('mongodb_testing');
    echo "\nTest connection config:\n";
    print_r($conn->getConfig());
    
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'role' => \App\Enums\UserRole::User->value,
        'is_active' => true,
    ]);
});

test('debug connection', function () {
    expect(true)->toBeTrue();
});