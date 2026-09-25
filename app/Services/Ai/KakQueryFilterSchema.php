<?php

namespace App\Services\Ai;

class KakQueryFilterSchema
{
    public const ALLOWED_FIELDS = [
        'status',
        'bulan',
        'tahun',
        'min_anggaran',
        'max_anggaran',
        'keyword',
    ];

    public ?string $status = null;
    public string|int|null $bulan = null;
    public ?int $tahun = null;
    public ?float $minAnggaran = null;
    public ?float $maxAnggaran = null;
    public ?string $keyword = null;

    /**
     * Build and sanitize schema from raw decoded AI array.
     * Rejects/ignores any unknown fields.
     */
    public static function fromRaw(array $raw): self
    {
        $schema = new self();

        // 1. Status whitelist: draft | final
        if (isset($raw['status']) && is_string($raw['status'])) {
            $statusVal = strtolower(trim($raw['status']));
            if (in_array($statusVal, ['draft', 'final'], true)) {
                $schema->status = $statusVal;
            }
        }

        // 2. Bulan: 1-12 | 'current'
        if (isset($raw['bulan'])) {
            $bulanVal = $raw['bulan'];
            if ($bulanVal === 'current' || $bulanVal === 'sekarang' || $bulanVal === 'ini') {
                $schema->bulan = 'current';
            } elseif (is_numeric($bulanVal)) {
                $intBulan = (int)$bulanVal;
                if ($intBulan >= 1 && $intBulan <= 12) {
                    $schema->bulan = $intBulan;
                }
            } elseif (is_string($bulanVal)) {
                $monthMap = [
                    'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
                    'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
                    'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
                ];
                $low = strtolower(trim($bulanVal));
                if (isset($monthMap[$low])) {
                    $schema->bulan = $monthMap[$low];
                }
            }
        }

        // 3. Tahun: numeric 4-digit (e.g. 2020 - 2035)
        if (isset($raw['tahun']) && is_numeric($raw['tahun'])) {
            $thn = (int)$raw['tahun'];
            if ($thn >= 2000 && $thn <= 2100) {
                $schema->tahun = $thn;
            }
        }

        // 4. Min Anggaran: numeric >= 0
        if (isset($raw['min_anggaran']) && is_numeric($raw['min_anggaran'])) {
            $min = (float)$raw['min_anggaran'];
            if ($min >= 0) {
                $schema->minAnggaran = $min;
            }
        }

        // 5. Max Anggaran: numeric >= 0
        if (isset($raw['max_anggaran']) && is_numeric($raw['max_anggaran'])) {
            $max = (float)$raw['max_anggaran'];
            if ($max >= 0) {
                $schema->maxAnggaran = $max;
            }
        }

        // 6. Keyword / Topik spesifik (sanitasi kata pengantar percakapan)
        if (!empty($raw['keyword']) && is_string($raw['keyword'])) {
            $kw = trim($raw['keyword']);
            // Buang kata pengantar umum percakapan
            $filler = '/\b(carikan|tampilkan|lihat|tolong|semua|data|dokumen|kak|draft|draf|final|bulan\s+ini|tahun\s+ini|bulan\s+lalu|anggaran)\b/i';
            $cleanedKw = trim(preg_replace($filler, '', $kw));
            $cleanedKw = trim(preg_replace('/^(tentang|topik|terkait|judul)\s+/i', '', $cleanedKw));
            if (mb_strlen($cleanedKw) >= 2) {
                $schema->keyword = $cleanedKw;
            }
        }

        return $schema;
    }

    /**
     * Check if any filter field is active.
     */
    public function hasActiveFilters(): bool
    {
        return $this->status !== null
            || $this->bulan !== null
            || $this->tahun !== null
            || $this->minAnggaran !== null
            || $this->maxAnggaran !== null
            || $this->keyword !== null;
    }

    /**
     * Convert to array matching allowed fields.
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
            'min_anggaran' => $this->minAnggaran,
            'max_anggaran' => $this->maxAnggaran,
            'keyword' => $this->keyword,
        ];
    }

    /**
     * Generate human readable filter summary in Indonesian for UI chips.
     */
    public function toSummaryList(): array
    {
        $chips = [];

        if ($this->status !== null) {
            $chips[] = [
                'field' => 'status',
                'label' => 'Status: ' . ucfirst($this->status),
                'value' => $this->status,
            ];
        }

        if ($this->bulan !== null) {
            $namaBulan = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ];
            $blnLabel = $this->bulan === 'current'
                ? 'Bulan Ini (' . ($namaBulan[now()->month] ?? now()->format('F')) . ')'
                : 'Bulan: ' . ($namaBulan[$this->bulan] ?? $this->bulan);

            $chips[] = [
                'field' => 'bulan',
                'label' => $blnLabel,
                'value' => $this->bulan,
            ];
        }

        if ($this->tahun !== null) {
            $chips[] = [
                'field' => 'tahun',
                'label' => 'Tahun: ' . $this->tahun,
                'value' => $this->tahun,
            ];
        }

        if ($this->minAnggaran !== null && $this->maxAnggaran !== null) {
            $chips[] = [
                'field' => 'anggaran',
                'label' => 'Anggaran: Rp ' . number_format($this->minAnggaran, 0, ',', '.') . ' - Rp ' . number_format($this->maxAnggaran, 0, ',', '.'),
                'value' => "{$this->minAnggaran}-{$this->maxAnggaran}",
            ];
        } elseif ($this->minAnggaran !== null) {
            $chips[] = [
                'field' => 'min_anggaran',
                'label' => 'Min Anggaran: Rp ' . number_format($this->minAnggaran, 0, ',', '.'),
                'value' => $this->minAnggaran,
            ];
        } elseif ($this->maxAnggaran !== null) {
            $chips[] = [
                'field' => 'max_anggaran',
                'label' => 'Max Anggaran: Rp ' . number_format($this->maxAnggaran, 0, ',', '.'),
                'value' => $this->maxAnggaran,
            ];
        }

        if ($this->keyword !== null) {
            $chips[] = [
                'field' => 'keyword',
                'label' => 'Topik: "' . $this->keyword . '"',
                'value' => $this->keyword,
            ];
        }

        return $chips;
    }
}
