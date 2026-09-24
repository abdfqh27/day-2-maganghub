<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class KakAssistantService
{
    protected BaseAiClient $aiClient;
    protected KnowledgeRetriever $retriever;

    public function __construct(BaseAiClient $aiClient, KnowledgeRetriever $retriever)
    {
        $this->aiClient = $aiClient;
        $this->retriever = $retriever;
    }

    /**
     * Ask contextual question to the wizard AI assistant.
     */
    public function ask(string $question, string $currentSection, ?string $sessionId = null): string
    {
        $question = trim($question);
        if ($question === '') {
            return 'Silakan ajukan pertanyaan terkait pengisian formulir KAK.';
        }

        // 1. Retrieve relevant context deterministically
        $context = $this->retriever->retrieveContext($question, $currentSection);

        // 2. Build scenario-tuned system prompt
        $systemPrompt = <<<PROMPT
Kamu adalah asisten pengisian dokumen KAK (Kerangka Acuan Kerja / Kegiatan) resmi untuk staf instansi pemerintah (khususnya Kemenko PMK).
Tugasmu adalah membantu pengguna memahami istilah teknis perencanaan (seperti GAP, DIPA, RO/KRO, SBM, RAB), format pengisian formulir wizard, serta tata cara penyusunan KAK yang benar.

ATURAN UTAMA:
1. Jawab secara ringkas, lugas, santun, dan dalam Bahasa Indonesia baku yang mudah dipahami.
2. Utamakan informasi dari KONTEKS REFERENSI yang disertakan di bawah.
3. Jika pertanyaan berkaitan dengan istilah atau cara pengisian yang tidak tercantum dalam konteks dan kamu tidak yakin, sampaikan secara jujur bahwa informasi tersebut belum terdaftar dalam glosarium sistem dan jangan mengarang.
4. Jawaban maksimal 2-3 paragraf ringkas atau poin-poin singkat agar mudah dibaca di widget chat formulir.

KONTEKS REFERENSI RESMI:
{$context}
PROMPT;

        $userMessage = "Pengguna sedang mengisi bagian wizard: [{$currentSection}]\n\nPertanyaan: {$question}";

        $answer = '';

        try {
            $client = $this->aiClient->getClient();
            $model = $this->aiClient->getModel();

            $response = $client->chat()->create([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'temperature' => 0.3,
                'max_tokens' => 600,
            ]);

            $answer = trim($response->choices[0]->message->content ?? '');

            if ($answer === '') {
                $answer = 'Mohon maaf, asisten belum dapat menghasilkan jawaban yang sesuai. Silakan ulangi pertanyaan Anda.';
            }
        } catch (Throwable $e) {
            Log::error('AI Assistant API Error: ' . $e->getMessage(), [
                'exception' => $e,
                'question' => $question,
                'section' => $currentSection,
            ]);

            $answer = 'Asisten AI sedang tidak tersedia atau mengalami kendala koneksi. Anda tetap dapat melanjutkan pengisian formulir wizard secara mandiri.';
        }

        // 3. Save log to database
        try {
            DB::table('ai_assistant_logs')->insert([
                'session_id' => $sessionId ?: session()->getId(),
                'section' => $currentSection,
                'question' => $question,
                'answer' => $answer,
                'created_at' => now(),
            ]);
        } catch (Throwable $dbEx) {
            Log::warning('Failed saving AI assistant log: ' . $dbEx->getMessage());
        }

        return $answer;
    }
}
