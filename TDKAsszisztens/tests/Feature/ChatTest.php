<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

test('guests are redirected to the login page', function () {
    $this->get('/chat')->assertRedirect('/login');
});

test('authenticated users can visit the chat page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/chat')->assertStatus(200);
});

test('users only see their own conversations', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $own = Conversation::factory()->for($user)->create(['title' => 'Saját beszélgetés']);
    $own->messages()->create(['role' => 'user', 'content' => 'Szia']);
    Conversation::factory()->for($other)->create(['title' => 'Másik beszélgetés']);

    $this->actingAs($user)
        ->get('/chat')
        ->assertInertia(fn ($page) => $page
            ->component('Chat')
            ->has('conversations', 1)
            ->where('conversations.0.title', 'Saját beszélgetés'));
});

test('conversations without messages are not listed', function () {
    $user = User::factory()->create();
    Conversation::factory()->for($user)->create(['title' => 'Üres beszélgetés']);

    $this->actingAs($user)
        ->get('/chat')
        ->assertInertia(fn ($page) => $page
            ->component('Chat')
            ->has('conversations', 0));
});

test('a new conversation is created when the first message is sent', function () {
    Http::fake(['http://localhost:8000/*' => Http::response(['response' => 'Ez a válasz.'], 200)]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/chat', ['message' => 'Mi a TDK?']);

    $conversation = $user->conversations()->sole();
    $response->assertRedirect("/chat/{$conversation->id}");
    expect($conversation->title)->toBe('Mi a TDK?');
    expect($conversation->messages()->oldest('id')->pluck('role')->all())
        ->toBe(['user', 'assistant']);
});

test('starting a conversation requires a message', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/chat')
        ->post('/chat')
        ->assertSessionHasErrors('message');

    expect($user->conversations()->count())->toBe(0);
});

test('sending a message stores both messages and calls the rag api', function () {
    Http::fake(['http://localhost:8000/*' => Http::response(['response' => 'Ez a válasz.'], 200)]);

    $user = User::factory()->create();
    $conversation = Conversation::factory()->for($user)->create(['title' => 'Új beszélgetés']);

    $this->actingAs($user)
        ->post("/chat/{$conversation->id}/messages", ['message' => 'Mi a TDK?'])
        ->assertRedirect();

    expect($conversation->messages()->oldest('id')->pluck('role')->all())
        ->toBe(['user', 'assistant']);
    expect($conversation->fresh()->title)->toBe('Mi a TDK?');

    Http::assertSent(fn (Request $request) => $request->url() === 'http://localhost:8000/chat'
        && $request['user_id'] === (string) $user->id
        && $request['message'] === 'Mi a TDK?');
});

test('previous messages are sent as context', function () {
    Http::fake(['http://localhost:8000/*' => Http::response(['response' => 'Ez a válasz.'], 200)]);

    $user = User::factory()->create();
    $conversation = Conversation::factory()->for($user)->create();

    $conversation->messages()->create(['role' => 'user', 'content' => 'Első kérdés']);
    $conversation->messages()->create(['role' => 'assistant', 'content' => 'Első válasz']);

    $this->actingAs($user)
        ->post("/chat/{$conversation->id}/messages", ['message' => 'Második kérdés']);

    Http::assertSent(fn (Request $request) => str_contains($request['message'], 'Korábbi beszélgetés')
        && str_contains($request['message'], 'Első kérdés')
        && str_contains($request['message'], 'Második kérdés'));
});

test('users cannot view or message conversations of others', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = Conversation::factory()->for($other)->create();

    $this->actingAs($user)->get("/chat/{$conversation->id}")->assertForbidden();
    $this->actingAs($user)
        ->post("/chat/{$conversation->id}/messages", ['message' => 'Szia'])
        ->assertForbidden();
});

test('deleting a conversation removes its messages', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->for($user)->create();
    $conversation->messages()->create(['role' => 'user', 'content' => 'Üzenet']);

    $this->actingAs($user)->delete("/chat/{$conversation->id}")->assertRedirect('/chat');

    $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
    $this->assertDatabaseMissing('messages', ['conversation_id' => $conversation->id]);
});

test('a rag failure on the first message keeps the written message', function () {
    Http::fake([
        'http://localhost:8000/*' => Http::response('Service unavailable', 503),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/chat', ['message' => 'Szia'])
        ->assertSessionHasErrors('message');

    $conversation = $user->conversations()->sole();
    expect($conversation->messages()->pluck('role')->all())->toBe(['user']);
});

test('a rag failure is reported back to the user', function () {
    Http::fake([
        'http://localhost:8000/*' => Http::response('Service unavailable', 503),
    ]);

    $user = User::factory()->create();
    $conversation = Conversation::factory()->for($user)->create();

    $this->actingAs($user)
        ->from("/chat/{$conversation->id}")
        ->post("/chat/{$conversation->id}/messages", ['message' => 'Szia'])
        ->assertSessionHasErrors('message');

    expect($conversation->messages()->where('role', 'assistant')->count())->toBe(0);
});
