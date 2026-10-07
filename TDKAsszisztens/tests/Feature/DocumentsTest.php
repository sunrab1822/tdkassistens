<?php

use App\Models\User;
use App\Services\RagService;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

test('guests cannot upload documents', function () {
    $this->post('/documents', [
        'file' => UploadedFile::fake()->create('test.pdf', 100, 'application/pdf'),
    ])->assertRedirect('/login');
});

test('an uploaded file is forwarded to the rag api', function () {
    Http::fake([
        'http://localhost:8000/*' => Http::response(['message' => 'Fájl sikeresen feldolgozva!', 'chunk_count' => 5], 200),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/documents', [
            'file' => UploadedFile::fake()->create('dolgozat.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasNoErrors();

    Http::assertSent(fn (Request $request) => $request->url() === 'http://localhost:8000/upload'
        && $request->hasFile('file')
        && str_contains($request->body(), 'dolgozat.pdf')
        && str_contains($request->body(), (string) $user->id));
});

test('unsupported file types are rejected', function () {
    Http::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/documents', [
            'file' => UploadedFile::fake()->create('jegyzet.txt', 10, 'text/plain'),
        ])
        ->assertSessionHasErrors('file');

    Http::assertNothingSent();
});

test('a rag upload error is reported back to the user', function () {
    Http::fake([
        'http://localhost:8000/*' => Http::response(['error' => 'Csak PDF és Kép támogatott!'], 200),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/documents', [
            'file' => UploadedFile::fake()->create('rossz.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('file');
});

test('deleting a document is forwarded to the rag api', function () {
    Http::fake([
        'http://localhost:8000/*' => Http::response(['message' => 'Törölve.'], 200),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete('/documents/'.rawurlencode('teszt fájl.pdf'))
        ->assertSessionHasNoErrors();

    Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
        && $request->url() === 'http://localhost:8000/documents/'.$user->id.'/'.rawurlencode('teszt fájl.pdf'));
});

test('the rag service lists a users documents', function () {
    Http::fake([
        'http://localhost:8000/*' => Http::response(['documents' => ['a.pdf', 'b.png']], 200),
    ]);

    $documents = (new RagService)->documents('42');

    expect($documents)->toBe(['a.pdf', 'b.png']);

    Http::assertSent(fn (Request $request) => $request->url() === 'http://localhost:8000/documents/42');
});
