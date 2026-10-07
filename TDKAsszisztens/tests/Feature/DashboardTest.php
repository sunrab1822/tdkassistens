<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('the dashboard route redirects to the chat page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertRedirect('/chat');
});

test('authenticated users are redirected from the welcome page to the chat', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/');
    $response->assertRedirect('/chat');
});
