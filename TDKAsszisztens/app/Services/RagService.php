<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RagService
{
    public function chat(string $userId, string $message): string
    {
        $response = $this->client()->post('/chat', [
            'message' => $message,
            'user_id' => $userId,
        ]);

        if ($response->failed() || ! isset($response->json()['response'])) {
            Log::warning('RAG chat failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('A RAG szolgáltatás nem érhető el.');
        }

        return $response->json('response');
    }

    /**
     * @return array{message?: string, chunk_count?: int, error?: string}
     */
    public function upload(string $userId, UploadedFile $file): array
    {
        $response = $this->client()
            ->attach('file', $file->getContent(), $file->getClientOriginalName())
            ->post('/upload', ['user_id' => $userId]);

        if ($response->failed()) {
            Log::warning('RAG upload failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('A RAG szolgáltatás nem érhető el.');
        }

        return $response->json() ?? [];
    }

    /**
     * @return list<string>
     */
    public function documents(string $userId): array
    {
        try {
            $response = $this->client()->get("/documents/{$userId}");

            if ($response->failed()) {
                return [];
            }

            return $response->json('documents') ?? [];
        } catch (\Throwable $e) {
            Log::warning('RAG documents failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function deleteDocument(string $userId, string $filename): bool
    {
        $response = $this->client()->delete("/documents/{$userId}/".rawurlencode($filename));

        return $response->successful() && ! isset($response->json()['error']);
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(config('services.rag.url'))
            ->timeout((int) config('services.rag.timeout'))
            ->acceptJson();
    }
}
