<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Services\RagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class DocumentController extends Controller
{
    public function store(StoreDocumentRequest $request, RagService $rag): RedirectResponse
    {
        try {
            $result = $rag->upload((string) $request->user()->id, $request->file('file'));
        } catch (RuntimeException) {
            return back()->withErrors(['file' => 'A feldolgozó szolgáltatás jelenleg nem érhető el.']);
        }

        if (isset($result['error'])) {
            return back()->withErrors(['file' => $result['error']]);
        }

        return back();
    }

    public function destroy(Request $request, string $filename, RagService $rag): RedirectResponse
    {
        if (! $rag->deleteDocument((string) $request->user()->id, $filename)) {
            return back()->withErrors(['file' => 'A fájl törlése nem sikerült.']);
        }

        return back();
    }
}
