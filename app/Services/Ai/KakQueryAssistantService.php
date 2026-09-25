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
     * Uses a robust hybrid deterministic parser + AI intent extraction model.
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

        // 1. Deterministic baseline extraction (fast, 100% resilient rule-based layer)
        $deterministic = $this->extractDeterministicFilter($question);

        // 2. AI Prompt tuned for semantic parameter and topic extraction
        $currentMonthName = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ][now()->month] ?? 'September';
        $currentYear = now()->year;

        $systemPrompt = <<<PROMPT
Kamu adalah asisten AI ekstraksi parameter pencarian riwayat dokumen KAK (Kerangka Acuan Kerja).
Waktu saat ini: Bulan {$currentMonthName} (bulan ke-{now()->month}), Tahun {$currentYear}.

Tugasmu adalah mengubah pertanyaan bahasa alami pengguna menjadi filter JSON dengan skema ketat berikut:
{
  "status": "draft" | "final" | null,
  "bulan": 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9 | 10 | 11 | 12 | "current" | null,
  "tahun": 2024 | 2025 | 2026 | null,
  "min_anggaran": 500000000 | null,
  "max_anggaran": 1000000000 | null,
  "keyword": "topik spesifik" | null
}

PANDUAN PEMETAAN:
- "draft" / "belum selesai" -> status: "draft"
- "final" / "selesai" / "disahkan" -> status: "final"
- "bulan ini" / "sekarang" / "saat ini" / "terbaru" -> bulan: "current"
- "bulan lalu" / "kemarin" -> bulan sebelumnya (angka)
- Nama bulan (Januari-Desember) -> ubah ke angka 1-12
- Anggaran jutaan/miliar: konversi ke angka penuh (contoh: "di atas 500 juta" -> min_anggaran: 500000000, "maksimal 1 M" -> max_anggaran: 1000000000).
- "keyword": HANYA diisi jika pengguna mencari topik/tema spesifik tertentu (contoh: "tentang anak" -> keyword: "anak", "kebijakan pendidikan" -> keyword: "pendidikan"). JANGAN masukkan kata percakapan umum seperti "carikan", "tampilkan", "lihat", "tolong", "semua", "data", "dokumen", "kak", "di bulan ini", "draft" ke dalam keyword. Jika tidak ada topik khusus, isi null.
- Field yang tidak disebutkan oleh pengguna WAJIB diisi null.
- HANYA keluarkan teks JSON murni yang valid, tanpa komentar, tanpa markdown formatting, tanpa penjelasan pembuka/penutup.
PROMPT;

        $aiParsed = null;

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
            if (is_array($decoded)) {
                $aiParsed = $decoded;
            }
        } catch (Throwable $e) {
            Log::warning('AI Query Assistant API call failed, using deterministic layer: ' . $e->getMessage(), [
                'question' => $question,
            ]);
        }

        // 3. Merge AI output with deterministic baseline (deterministic fills gaps or acts as safe fallback)
        $mergedRaw = $deterministic;
        if (is_array($aiParsed)) {
            foreach ($aiParsed as $k => $v) {
                if ($v !== null && $v !== '') {
                    $mergedRaw[$k] = $v;
                }
            }
        }

        // 4. Strictly validate and sanitize through schema whitelist
        $schema = KakQueryFilterSchema::fromRaw($mergedRaw);

        return [
            'success' => true,
            'filter' => $schema->toArray(),
            'chips' => $schema->toSummaryList(),
            'has_active' => $schema->hasActiveFilters(),
            'error' => null,
        ];
    }

    /**
     * Deterministic rule-based extraction for resilient hybrid operation.
     */
    protected function extractDeterministicFilter(string $question): array
    {
        $filter = [
            'status' => null,
            'bulan' => null,
            'tahun' => null,
            'min_anggaran' => null,
            'max_anggaran' => null,
            'keyword' => null,
        ];

        $q = strtolower(trim($question));

        // Status
        if (preg_match('/\b(draft|draf|rancangan|belum\s+selesai)\b/i', $q)) {
            $filter['status'] = 'draft';
        } elseif (preg_match('/\b(final|selesai|disahkan|sudah\s+selesai)\b/i', $q)) {
            $filter['status'] = 'final';
        }

        // Bulan
        if (preg_match('/\b(bulan\s+ini|saat\s+ini|sekarang)\b/i', $q)) {
            $filter['bulan'] = 'current';
        } elseif (preg_match('/\b(bulan\s+lalu|kemarin)\b/i', $q)) {
            $filter['bulan'] = now()->month - 1 ?: 12;
        } else {
            $monthMap = [
                'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
                'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
                'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
            ];
            foreach ($monthMap as $mName => $mNum) {
                if (preg_match('/\b' . $mName . '\b/i', $q)) {
                    $filter['bulan'] = $mNum;
                    break;
                }
            }
        }

        // Tahun
        if (preg_match('/\b(20[2-3][0-9])\b/', $q, $matches)) {
            $filter['tahun'] = (int)$matches[1];
        } elseif (preg_match('/\b(tahun\s+ini)\b/i', $q)) {
            $filter['tahun'] = (int)now()->year;
        }

        // Min Anggaran: "di atas 500 juta", "minimal 100 jt", "> 500jt"
        if (preg_match('/(?:di\s*atas|lebih\s*dari|minimal|min|>\s*=?)\s*(?:rp\.?\s*)?([0-9\.\,]+)\s*(juta|jt|miliar|milyar|m)?/i', $q, $m)) {
            $num = (float)str_replace(['.', ','], ['', '.'], $m[1]);
            $unit = strtolower($m[2] ?? '');
            if (in_array($unit, ['juta', 'jt'])) $num *= 1000000;
            elseif (in_array($unit, ['miliar', 'milyar', 'm'])) $num *= 1000000000;
            $filter['min_anggaran'] = $num;
        }

        // Max Anggaran: "di bawah 1 m", "maksimal 800 juta", "< 500jt"
        if (preg_match('/(?:di\s*bawah|kurang\s*dari|maksimal|maks|max|<\s*=?)\s*(?:rp\.?\s*)?([0-9\.\,]+)\s*(juta|jt|miliar|milyar|m)?/i', $q, $m)) {
            $num = (float)str_replace(['.', ','], ['', '.'], $m[1]);
            $unit = strtolower($m[2] ?? '');
            if (in_array($unit, ['juta', 'jt'])) $num *= 1000000;
            elseif (in_array($unit, ['miliar', 'milyar', 'm'])) $num *= 1000000000;
            $filter['max_anggaran'] = $num;
        }

        // Keyword topic: e.g. "tentang X", "topik X", "terkait X"
        if (preg_match('/(?:tentang|topik|terkait|judul)\s+([a-zA-Z0-9\s]+)/i', $question, $m)) {
            $cleanKw = trim(preg_replace('/\b(di\s+bulan\s+ini|bulan\s+ini|tahun\s+\d+|draft|final|anggaran.*)\b/i', '', $m[1]));
            if (mb_strlen($cleanKw) >= 2) {
                $filter['keyword'] = $cleanKw;
            }
        }

        return $filter;
    }

    /**
     * Execute deterministic Eloquent search based strictly on validated filter schema.
     */
    public function searchWithFilter(array $filter): Collection
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

        // 6. Specific topic/keyword filter on judul
        if (!empty($filter['keyword'])) {
            $kw = trim($filter['keyword']);
            $query->where(function ($q) use ($kw) {
                $q->where('judul', 'like', "%{$kw}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
