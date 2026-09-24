<?php

namespace App\Services\Ai;

use App\Models\KakSubmission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class KakQueryAssistantService
{
    protected BaseAiClient $aiClient;

    public function __construct(BaseAiClient $aiClient)
    {
        $this->aiClient = $aiClient;
    }

    /**
     * Parse natural language search query into a validated filter array.
     * AI only extracts parameters, never executes database queries directly.
     */
    public function parseQuery(string $naturalQuestion): array
    {
        $question = trim($naturalQuestion);
        if ($question === '') {
            return [
                'success' => false,
                'filter' => (new KakQueryFilterSchema())->toArray(),
                'chips' => [],
                'error' => 'Pertanyaan kosong.',
            ];
        }

        $systemPrompt = <<<PROMPT
Kamu adalah asisten AI ekstraksi parameter pencarian riwayat dokumen KAK (Kerangka Acuan Kerja).
Tugasmu adalah mengubah pertanyaan bahasa alami pengguna menjadi filter JSON dengan skema ketat berikut:

{
  "status": "draft" | "final" | null,
  "bulan": 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9 | 10 | 11 | 12 | "current" | null,
  "tahun": 2024 | 2025 | 2026 | null,
  "min_anggaran": 500000000 | null,
  "max_anggaran": 1000000000 | null
}

PANDUAN PEMETAAN:
- "draft" / "belum selesai" -> status: "draft"
- "final" / "selesai" / "disahkan" -> status: "final"
- "bulan ini" / "terbaru" -> bulan: "current"
- Nama bulan (Januari-Desember) -> ubah ke angka 1-12
- Anggaran jutaan/miliar: konversi ke angka penuh (contoh: "di atas 500 juta" -> min_anggaran: 500000000, "maksimal 1 M" -> max_anggaran: 1000000000).
- Field yang tidak disebutkan oleh pengguna WAJIB diisi null.
- JANGAN buat field lain di luar skema tersebut.
- HANYA keluarkan teks JSON murni yang valid, tanpa komentar, tanpa markdown formatting, tanpa penjelasan pembuka/penutup.
PROMPT;

        try {
            $client = $this->aiClient->getClient();
            $model = $this->aiClient->getModel();

            $response = $client->chat()->create([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => "Pertanyaan pencarian: {$question}"],
                ],
                'temperature' => 0.0,
                'max_tokens' => 200,
            ]);

            $rawContent = trim($response->choices[0]->message->content ?? '');

            // Strip markdown block fences if model included them
            $rawContent = preg_replace('/^```(?:json)?/i', '', $rawContent);
            $rawContent = preg_replace('/```$/', '', $rawContent);
            $rawContent = trim($rawContent);

            $decoded = json_decode($rawContent, true);

            if (!is_array($decoded)) {
                Log::warning('AI Query Assistant returned non-JSON response', ['content' => $rawContent]);
                return [
                    'success' => false,
                    'filter' => (new KakQueryFilterSchema())->toArray(),
                    'chips' => [],
                    'error' => 'Format balasan AI bukan JSON valid.',
                ];
            }

            // Strictly validate and sanitize through schema
            $schema = KakQueryFilterSchema::fromRaw($decoded);

            return [
                'success' => true,
                'filter' => $schema->toArray(),
                'chips' => $schema->toSummaryList(),
                'has_active' => $schema->hasActiveFilters(),
                'error' => null,
            ];
        } catch (Throwable $e) {
            Log::error('AI Query parseQuery failed: ' . $e->getMessage(), ['question' => $question]);

            // Graceful deterministic fallback
            return [
                'success' => false,
                'filter' => (new KakQueryFilterSchema())->toArray(),
                'chips' => [],
                'error' => 'Layanan pemahaman AI sedang mengalami kendala. ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Execute deterministic Eloquent search based strictly on validated filter schema.
     */
    public function searchWithFilter(array $filter, ?string $rawQuery = null): Collection
    {
        $query = KakSubmission::query();

        // 1. Status filter
        if (!empty($filter['status']) && in_array($filter['status'], ['draft', 'final'], true)) {
            $query->where('status', $filter['status']);
        }

        // 2. Bulan filter
        if (($filter['bulan'] ?? null) === 'current') {
            $query->whereMonth('created_at', now()->month);
        } elseif (!empty($filter['bulan']) && is_numeric($filter['bulan'])) {
            $query->whereMonth('created_at', (int)$filter['bulan']);
        }

        // 3. Tahun filter
        if (!empty($filter['tahun']) && is_numeric($filter['tahun'])) {
            $query->whereYear('created_at', (int)$filter['tahun']);
        }

        // 4. Min anggaran filter
        if (isset($filter['min_anggaran']) && is_numeric($filter['min_anggaran'])) {
            $query->where('total_anggaran', '>=', (float)$filter['min_anggaran']);
        }

        // 5. Max anggaran filter
        if (isset($filter['max_anggaran']) && is_numeric($filter['max_anggaran'])) {
            $query->where('total_anggaran', '<=', (float)$filter['max_anggaran']);
        }

        // Optional text search fallback on judul if rawQuery contains non-filter keywords
        if ($rawQuery) {
            $cleanKw = trim(preg_replace('/\b(kak|draft|final|bulan\s+ini|anggaran)\b/i', '', $rawQuery));
            if (strlen($cleanKw) >= 3) {
                $query->where(function ($q) use ($cleanKw) {
                    $q->where('judul', 'like', "%{$cleanKw}%");
                });
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
