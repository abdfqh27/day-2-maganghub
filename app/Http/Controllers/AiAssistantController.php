<?php

namespace App\Http\Controllers;

use App\Services\Ai\KakAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AiAssistantController extends Controller
{
    protected KakAssistantService $assistantService;

    public function __construct(KakAssistantService $assistantService)
    {
        $this->assistantService = $assistantService;
    }

    /**
     * Handle contextual question from wizard assistant widget.
     */
    public function ask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|min:2|max:1000',
            'current_section' => 'nullable|string|max:255',
        ]);

        $question = $validated['question'];
        $currentSection = $validated['current_section'] ?? 'Umum';
        $sessionId = $request->session()->getId();

        try {
            $answer = $this->assistantService->ask($question, $currentSection, $sessionId);

            return response()->json([
                'status' => 'success',
                'answer' => $answer,
                'section' => $currentSection,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'answer' => 'Asisten AI sedang tidak tersedia, silakan lanjutkan mengisi formulir.',
                'message' => $e->getMessage(),
            ], 200);
        }
    }
}
