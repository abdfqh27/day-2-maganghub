<?php

namespace App\Http\Controllers;

use App\Models\KakSubmission;
use App\Services\Ai\KakQueryAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AiQueryController extends Controller
{
    protected KakQueryAssistantService $queryService;

    public function __construct(KakQueryAssistantService $queryService)
    {
        $this->queryService = $queryService;
    }

    /**
     * Search submissions using natural language query via AI intent extraction.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|min:2|max:500',
        ]);

        $question = $validated['question'];

        try {
            // 1. AI translates intent into deterministic filter schema
            $parsed = $this->queryService->parseQuery($question);
            $filter = $parsed['filter'] ?? [];
            $chips = $parsed['chips'] ?? [];

            // 2. Deterministic Eloquent executes the filter
            $submissions = $this->queryService->searchWithFilter($filter, $question);

            // Format items for frontend
            $items = $submissions->map(function (KakSubmission $sub) {
                return [
                    'id' => $sub->id,
                    'judul' => $sub->display_judul,
                    'status' => $sub->status,
                    'output_format' => strtolower($sub->output_format ?: 'docx'),
                    'total_anggaran' => $sub->total_anggaran ? 'Rp ' . number_format($sub->total_anggaran, 0, ',', '.') : null,
                    'total_fields' => count($sub->data ?? []),
                    'created_at_human' => $sub->created_at ? $sub->created_at->translatedFormat('d M Y, H:i') : '-',
                    'detail_url' => route('submissions.show', $sub),
                    'download_url' => route('submissions.download', $sub),
                ];
            });

            // Build human-friendly summary sentence
            $summaryParts = array_map(fn($c) => $c['label'], $chips);
            $filterSummary = !empty($summaryParts)
                ? 'Menampilkan hasil untuk: ' . implode(', ', $summaryParts)
                : 'Menampilkan semua hasil yang relevan';

            return response()->json([
                'status' => 'success',
                'question' => $question,
                'filter' => $filter,
                'chips' => $chips,
                'filter_summary' => $filterSummary,
                'count' => $items->count(),
                'submissions' => $items,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses pencarian: ' . $e->getMessage(),
                'submissions' => [],
                'chips' => [],
                'count' => 0,
            ], 200);
        }
    }
}
