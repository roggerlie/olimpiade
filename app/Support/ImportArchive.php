<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Permanent copy of every file uploaded through an admin import (Bank Soal,
 * Peserta), kept as an audit trail of what was imported. Stored on the
 * private `local` disk (storage/app/private/imports/...), never web-reachable.
 *
 * Importers should read from the returned path instead of the Livewire
 * upload itself: Livewire's livewire-tmp copy is cleaned up after 24h.
 */
class ImportArchive
{
    /**
     * The stored name is `{Ymd_His}_{6 random}_{slugged original}.{ext}`, all
     * lowercase — safe on case-sensitive Linux filesystems, free of spaces and
     * characters Windows or shells choke on, and collision-free for uploads
     * landing in the same second.
     *
     * @return string Absolute filesystem path of the stored copy.
     */
    public static function store(UploadedFile $file, string $directory): string
    {
        $ekstensi = strtolower($file->getClientOriginalExtension());
        $namaAsli = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'import';

        $path = $file->storeAs(
            "imports/{$directory}",
            now()->format('Ymd_His').'_'.Str::lower(Str::random(6))."_{$namaAsli}.{$ekstensi}",
            'local',
        );

        return Storage::disk('local')->path($path);
    }
}
