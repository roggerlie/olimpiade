<?php

namespace App\Console\Commands;

use App\Models\PesertaUjian;
use App\Services\ScoringService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Safety net for the client-side timer: a student who closes their browser
 * (or loses connection) right at the deadline never gets the chance to hit
 * "Kumpulkan" — this scores and closes out their attempt anyway. Scheduled
 * every minute; see routes/console.php.
 */
#[Signature('exam:auto-submit')]
#[Description('Auto-submit exam attempts whose deadline has passed but were never manually submitted.')]
class AutoSubmitExpiredExams extends Command
{
    public function handle(ScoringService $scoring): int
    {
        $total = 0;

        PesertaUjian::query()
            ->whereNotNull('waktu_mulai')
            ->whereNull('waktu_selesai')
            ->with('ujian')
            ->chunkById(100, function ($attempts) use ($scoring, &$total): void {
                foreach ($attempts as $attempt) {
                    if ($attempt->waktuHabis()) {
                        $scoring->submit($attempt);
                        $total++;
                    }
                }
            });

        $this->info("Auto-submitted {$total} expired exam attempt(s).");

        return self::SUCCESS;
    }
}
