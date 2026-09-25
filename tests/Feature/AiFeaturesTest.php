<?php

namespace Tests\Feature;

use App\Models\KakSubmission;
use Tests\TestCase;

class AiFeaturesTest extends TestCase
{
    /**
     * Test wizard assistant ask endpoint.
     */
    public function test_ai_assistant_ask_endpoint(): void
    {
        $response = $this->postJson(route('kak.assistant.ask'), [
            'question' => 'apa itu GAP',
            'current_section' => '1. Identitas Program & KAK'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'answer',
            'section'
        ]);

        $this->assertEquals('success', $response->json('status'));
        $this->assertNotEmpty($response->json('answer'));
    }

    /**
     * Test AI history natural language search endpoint.
     */
    public function test_ai_history_search_endpoint(): void
    {
        $response = $this->postJson(route('kak.history.aiSearch'), [
            'question' => 'dokumen final tahun 2026'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'question',
            'filter',
            'chips',
            'filter_summary',
            'count',
            'submissions'
        ]);

        $this->assertEquals('success', $response->json('status'));
        $this->assertIsArray($response->json('submissions'));
    }

    /**
     * Test AI history search for current month: "carikan saya data di bulan ini".
     */
    public function test_ai_history_search_current_month(): void
    {
        $response = $this->postJson(route('kak.history.aiSearch'), [
            'question' => 'carikan saya data di bulan ini'
        ]);

        $response->assertStatus(200);
        $this->assertEquals('success', $response->json('status'));
        $this->assertEquals('current', $response->json('filter.bulan'));
        $this->assertGreaterThan(0, $response->json('count'));
    }

    /**
     * Test AI query filter schema whitelist sanitization.
     */
    public function test_filter_schema_whitelist_guard(): void
    {
        $schema = \App\Services\Ai\KakQueryFilterSchema::fromRaw([
            'status' => 'draft',
            'bulan' => 'current',
            'tahun' => 2026,
            'min_anggaran' => 500000000,
            'keyword' => 'tentang anak',
            'malicious_sql' => 'DROP TABLE users',
            'random_field' => 'should be ignored'
        ]);

        $array = $schema->toArray();

        $this->assertEquals('draft', $array['status']);
        $this->assertEquals('current', $array['bulan']);
        $this->assertEquals(2026, $array['tahun']);
        $this->assertEquals(500000000, $array['min_anggaran']);
        $this->assertEquals('anak', $array['keyword']);
        $this->assertArrayNotHasKey('malicious_sql', $array);
        $this->assertArrayNotHasKey('random_field', $array);
    }
}
