<?php

namespace Tests\Feature;

use App\Models\KakSubmission;
use Tests\TestCase;

class KakSubmissionTest extends TestCase
{
    /**
     * Test index page returns successful response.
     */
    public function test_index_page_is_accessible(): void
    {
        $response = $this->get(route('submissions.index'));
        $response->assertStatus(200);
        $response->assertSee('Daftar Dokumen KAK');
    }

    /**
     * Test wizard create page is accessible.
     */
    public function test_wizard_create_page_is_accessible(): void
    {
        $response = $this->get(route('submissions.create'));
        $response->assertStatus(200);
        $response->assertSee('Formulir Pengisian KAK Digital');
        $response->assertSee('Pilih Format Output Dokumen');
    }

    /**
     * Test saving wizard step progress to session.
     */
    public function test_saving_step_progress_to_session(): void
    {
        $response = $this->postJson(route('submissions.storeStep'), [
            'data' => [
                'field_001' => 'Koordinasi Kebijakan Pendidikan',
                'field_002' => '2026',
            ],
            'judul' => 'KAK Koordinasi Kebijakan Pendidikan 2026',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
        $this->assertEquals('Koordinasi Kebijakan Pendidikan', session('kak_form_data.field_001'));
    }

    /**
     * Test finalizing submission generates DOCX file and redirects to show page.
     */
    public function test_finalizing_submission_generates_docx_document(): void
    {
        $postData = [
            'output_format' => 'docx',
            'judul' => 'KAK Uji Coba Otomatisasi 2026',
            'data' => [
                'field_001' => 'Pemberdayaan Masyarakat dan Desa',
                'field_002' => '2026',
                'field_003' => 'Peningkatan Kapasitas SDM Desa',
                'field_157' => '22 September 2026',
                'field_158' => 'Pemberdayaan Masyarakat dan Desa',
                'field_159' => 'Drs. Supriyanto, M.M.',
                'field_160' => '19780415 200103 1 004',
            ],
        ];

        $response = $this->post(route('submissions.finalize'), $postData);

        $submission = KakSubmission::where('judul', 'KAK Uji Coba Otomatisasi 2026')->latest('id')->first();
        $this->assertNotNull($submission);

        $response->assertRedirect(route('submissions.show', $submission));

        // Verify generated file
        $this->assertEquals('docx', $submission->output_format);
        $this->assertNotNull($submission->generated_file_path);
        $this->assertTrue($submission->hasGeneratedFile());

        $filePath = $submission->getAbsoluteFilePath();
        $this->assertFileExists($filePath);
        $this->assertGreaterThan(100000, filesize($filePath));
    }

    /**
     * Test downloading generated document.
     */
    public function test_downloading_generated_document(): void
    {
        $submission = KakSubmission::latest()->first();
        $this->assertNotNull($submission);

        $response = $this->get(route('submissions.download', $submission));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }

    /**
     * Test regenerating submission with docx format.
     */
    public function test_regenerating_submission_with_docx(): void
    {
        $submission = KakSubmission::latest()->first();
        $this->assertNotNull($submission);

        $response = $this->post(route('submissions.regenerate', $submission), [
            'output_format' => 'docx',
        ]);

        $response->assertRedirect(route('submissions.show', $submission));
        $submission->refresh();
        $this->assertEquals('docx', $submission->output_format);
        $this->assertTrue($submission->hasGeneratedFile());
    }

    /**
     * Test wizard displays KODE / NOMOR badges, example cards, and tailored placeholders.
     */
    public function test_wizard_displays_code_badge_and_example_placeholders(): void
    {
        $response = $this->get(route('submissions.create'));
        $response->assertStatus(200);

        // Check for Code / Nomor badge
        $response->assertSee('KODE / NOMOR');

        // Check for interactive example button
        $response->assertSee('Gunakan Contoh');

        // Check for specific realistic placeholders
        $response->assertSee('4321.BMA.001 / RO.01');
        $response->assertSee('Pemenuhan Hak dan Perlindungan Anak');
        $response->assertSee('Petunjuk Pengisian Kolom Bertanda [KODE / NOMOR]');
    }

    /**
     * Test schedule matrix (tabel_waktu) submission updates DOCX XML table with checkmarks and green shading.
     */
    public function test_schedule_matrix_submission_and_xml_table_update(): void
    {
        $scheduleData = [
            'rak_title' => 'Peningkatan Layanan Gizi Masyarakat 2026',
            'subkomponen_1' => 'Koordinasi Terpadu Lintas Sektor',
            'kegiatan_1' => [1, 2],
            'kegiatan_2' => [3, 4, 5, 6],
            'kegiatan_3' => [7, 8],
            'kegiatan_4' => [11, 12],
            'has_subkomponen_2' => false,
        ];

        $postData = [
            'output_format' => 'docx',
            'judul' => 'KAK Jadwal Pelaksanaan Uji Coba 2026',
            'data' => [
                'field_001' => 'Kesehatan Masyarakat',
                'field_002' => '2026',
                'field_003' => 'Peningkatan Pelayanan Kesehatan',
                'field_157' => '22 September 2026',
                'field_158' => 'Kesehatan Masyarakat',
                'field_159' => 'Drs. Supriyanto, M.M.',
                'field_160' => '19780415 200103 1 004',
                'tabel_waktu' => json_encode($scheduleData),
            ],
        ];

        $response = $this->post(route('submissions.finalize'), $postData);
        $submission = KakSubmission::where('judul', 'KAK Jadwal Pelaksanaan Uji Coba 2026')->latest('id')->first();
        $this->assertNotNull($submission);

        $response->assertRedirect(route('submissions.show', $submission));

        // Test show view contains the visual matrix table
        $showResponse = $this->get(route('submissions.show', $submission));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('D. Waktu Pencapaian Keluaran (Matriks Jadwal 12 Bulan)');
        $showResponse->assertSee('Peningkatan Layanan Gizi Masyarakat 2026');
        $showResponse->assertSee('Koordinasi Terpadu Lintas Sektor');

        // Test the generated DOCX table has checkmarks and green shading
        $filePath = $submission->getAbsoluteFilePath();
        $this->assertFileExists($filePath);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($filePath) === true);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('93c47d', $xml);
        $this->assertStringContainsString('✓', $xml);
    }

    /**
     * Test auto download trigger is present in response when auto_download flag is set.
     */
    public function test_auto_download_triggered_on_show_page(): void
    {
        $submission = KakSubmission::latest()->first();
        $this->assertNotNull($submission);

        // Access show with auto_download=1 query parameter
        $response = $this->get(route('submissions.show', ['submission' => $submission, 'auto_download' => 1]));
        $response->assertStatus(200);

        // Assert presence of auto-download banner and download iframe
        $response->assertSee('File Otomatis Sedang Diunduh!');
        $response->assertSee(route('submissions.download', $submission));
        $response->assertSee('<iframe id="downloadIframe"', false);
    }
}
