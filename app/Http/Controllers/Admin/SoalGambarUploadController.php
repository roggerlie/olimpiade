<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankSoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Image upload target for TinyMCE's `images_upload_handler` (see
 * resources/js/soal-editor.js) — lets an admin drag/paste/browse an image
 * straight into a soal's rich-text content instead of a separate dropzone.
 * Stores to the same `soal/{bankSoalId}` path the old per-slot gambar
 * uploads used (App\Models\Soal::resolveGambarPath()), just via a plain
 * HTTP endpoint instead of a Livewire upload, since TinyMCE talks to it
 * with a normal `fetch()` + FormData rather than Livewire's own protocol.
 */
class SoalGambarUploadController extends Controller
{
    public function store(Request $request, BankSoal $bankSoal): JsonResponse
    {
        $data = $request->validate([
            'gambar' => ['required', 'image', 'max:2048'],
        ]);

        $path = $data['gambar']->store("soal/{$bankSoal->id}", 'public');

        return response()->json(['location' => Storage::disk('public')->url($path)]);
    }
}
