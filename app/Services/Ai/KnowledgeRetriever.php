<?php

namespace App\Services\Ai;

class KnowledgeRetriever
{
    protected ?array $fieldMap = null;
    protected ?array $glossary = null;
    protected array $cache = [];

    /**
     * Stopwords bahasa Indonesia sederhana yang diabaikan saat menghitung skor kecocokan.
     */
    protected array $stopWords = [
        'apa', 'itu', 'apakah', 'bagaimana', 'kenapa', 'mengapa', 'kapan', 'siapa',
        'yang', 'dan', 'di', 'ke', 'dari', 'untuk', 'pada', 'dengan', 'adalah',
        'yaitu', 'ini', 'itu', 'tolong', 'bisa', 'mohon', 'jelaskan', 'berikan',
        'saya', 'kami', 'anda', 'kamu', 'tentang', 'mengenai'
    ];

    /**
     * Retrieve relevant knowledge context based on keyword match & similarity scoring.
     */
    public function retrieveContext(string $query, ?string $currentSection = null): string
    {
        $cacheKey = md5(strtolower(trim($query)) . '|' . ($currentSection ?? ''));
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $keywords = $this->extractKeywords($query);
        $fieldMap = $this->loadFieldMap();
        $glossary = $this->loadGlossary();

        $glossaryResults = $this->searchGlossary($keywords, $query, $glossary);
        $fieldResults = $this->searchFieldMap($keywords, $currentSection, $fieldMap);

        $contextParts = [];

        if (!empty($glossaryResults)) {
            $contextParts[] = "--- REFERENSI GLOSARIUM KAK & PEMERINTAHAN ---";
            foreach (array_slice($glossaryResults, 0, 3) as $item) {
                $term = $item['term'];
                $aliases = !empty($item['alias']) ? ' (' . implode(', ', $item['alias']) . ')' : '';
                $definition = $item['definition'];
                $contextParts[] = "• {$term}{$aliases}: {$definition}";
            }
        }

        if (!empty($fieldResults)) {
            $contextParts[] = "--- PANDUAN PENGISIAN FIELD / FORMULIR WIZARD ---";
            foreach (array_slice($fieldResults, 0, 4) as $item) {
                $sec = $item['section'] ?? 'Bagian';
                $label = $item['label'] ?? '';
                $ctx = $item['context'] ?? '';
                $hint = $item['placeholder'] ?? '';
                $part = "• [{$sec}] {$label}";
                if ($ctx) {
                    $part .= " | Kalimat dalam dokumen: \"{$ctx}\"";
                }
                if ($hint) {
                    $part .= " | Contoh/Format: {$hint}";
                }
                $contextParts[] = $part;
            }
        }

        if (empty($contextParts)) {
            $fallback = "Sistem Digitalisasi KAK: Aplikasi untuk menyusun dokumen Kerangka Acuan Kegiatan resmi instansi pemerintah, dengan validasi input, format SBM (Standar Biaya Masukan), analisis gender (GAP), rincian output (RO/KRO), dan substitusi otomatis ke format Word (.docx) & PDF.";
            if ($currentSection) {
                $fallback .= " Pengguna saat ini sedang mengisi bagian: {$currentSection}.";
            }
            $contextParts[] = $fallback;
        }

        $result = implode("\n", $contextParts);
        $this->cache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Extract meaningful keywords from query.
     */
    protected function extractKeywords(string $query): array
    {
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', strtolower($query));
        $words = preg_split('/\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY);

        $filtered = array_filter($words, function ($w) {
            return strlen($w) >= 2 && !in_array($w, $this->stopWords);
        });

        return array_values(array_unique($filtered));
    }

    /**
     * Score & search glossary items.
     */
    protected function searchGlossary(array $keywords, string $rawQuery, array $glossary): array
    {
        $scored = [];
        $rawLower = strtolower($rawQuery);

        foreach ($glossary as $item) {
            $score = 0;
            $termLower = strtolower($item['term'] ?? '');
            $aliases = array_map('strtolower', $item['alias'] ?? []);
            $defLower = strtolower($item['definition'] ?? '');

            // Exact match term
            if ($termLower !== '' && (str_contains($rawLower, $termLower) || $rawLower === $termLower)) {
                $score += 100;
            }

            // Alias match
            foreach ($aliases as $alias) {
                if ($alias !== '' && str_contains($rawLower, $alias)) {
                    $score += 80;
                    break;
                }
            }

            // Keyword matches in term, alias, and definition
            foreach ($keywords as $kw) {
                if (str_contains($termLower, $kw)) {
                    $score += 40;
                }
                foreach ($aliases as $alias) {
                    if (str_contains($alias, $kw)) {
                        $score += 30;
                    }
                }
                if (str_contains($defLower, $kw)) {
                    $score += 15;
                }
            }

            if ($score > 0) {
                $item['_score'] = $score;
                $scored[] = $item;
            }
        }

        usort($scored, fn($a, $b) => $b['_score'] <=> $a['_score']);
        return $scored;
    }

    /**
     * Score & search field_map.json items.
     */
    protected function searchFieldMap(array $keywords, ?string $currentSection, array $fieldMap): array
    {
        $scored = [];
        $currSecLower = $currentSection ? strtolower($currentSection) : null;

        foreach ($fieldMap as $item) {
            $score = 0;
            $secLower = strtolower($item['section'] ?? '');
            $labelLower = strtolower($item['label'] ?? '');
            $contextLower = strtolower($item['context'] ?? '');
            $placeholderLower = strtolower($item['placeholder'] ?? '');

            // Boost if matches current section
            if ($currSecLower && str_contains($secLower, $currSecLower)) {
                $score += 25;
            }

            foreach ($keywords as $kw) {
                if (str_contains($labelLower, $kw)) {
                    $score += 40;
                }
                if (str_contains($contextLower, $kw)) {
                    $score += 25;
                }
                if (str_contains($placeholderLower, $kw)) {
                    $score += 15;
                }
                if (str_contains($secLower, $kw)) {
                    $score += 10;
                }
            }

            if ($score > 0) {
                $item['_score'] = $score;
                $scored[] = $item;
            }
        }

        usort($scored, fn($a, $b) => $b['_score'] <=> $a['_score']);
        return $scored;
    }

    /**
     * Load field map JSON safely.
     */
    protected function loadFieldMap(): array
    {
        if ($this->fieldMap !== null) {
            return $this->fieldMap;
        }

        $paths = [
            resource_path('templates/field_map.json'),
            storage_path('app/templates/field_map.json'),
        ];

        foreach ($paths as $p) {
            if (file_exists($p)) {
                $decoded = json_decode(file_get_contents($p), true);
                if (is_array($decoded)) {
                    $this->fieldMap = $decoded;
                    return $this->fieldMap;
                }
            }
        }

        $this->fieldMap = [];
        return $this->fieldMap;
    }

    /**
     * Load glossary JSON safely.
     */
    protected function loadGlossary(): array
    {
        if ($this->glossary !== null) {
            return $this->glossary;
        }

        $path = resource_path('data/glossary.json');
        if (file_exists($path)) {
            $decoded = json_decode(file_get_contents($path), true);
            if (is_array($decoded)) {
                $this->glossary = $decoded;
                return $this->glossary;
            }
        }

        $this->glossary = [];
        return $this->glossary;
    }
}
