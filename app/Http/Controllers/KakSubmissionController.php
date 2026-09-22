<?php

namespace App\Http\Controllers;

use App\Models\KakSubmission;
use App\Services\KakDocumentGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class KakSubmissionController extends Controller
{
    protected KakDocumentGenerator $generator;

    public function __construct(KakDocumentGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Display listing of submissions.
     */
    public function index()
    {
        $submissions = KakSubmission::orderBy('created_at', 'desc')->paginate(10);
        return view('submissions.index', compact('submissions'));
    }

    /**
     * Show wizard form to fill in KAK.
     */
    public function create(Request $request)
    {
        $fieldMap = $this->getFieldMap();
        if (empty($fieldMap)) {
            return redirect()->route('submissions.index')
                ->with('error', 'Field map template belum tersedia. Harap jalankan php artisan kak:prepare-template.');
        }

        // Group fields by section
        $groupedSections = [];
        foreach ($fieldMap as $item) {
            $section = $item['section'] ?? 'Umum';
            $groupedSections[$section][] = $item;
        }

        // Retrieve draft data from session if any
        $draftData = Session::get('kak_form_data', []);
        $draftJudul = Session::get('kak_form_judul', '');

        return view('submissions.wizard', [
            'sections' => $groupedSections,
            'fieldMap' => $fieldMap,
            'draftData' => $draftData,
            'draftJudul' => $draftJudul,
        ]);
    }

    /**
     * Save progress of a wizard step into session (AJAX/Fetch).
     */
    public function storeStep(Request $request)
    {
        $incomingData = $request->input('data', []);
        if (isset($incomingData['tabel_waktu']) && is_string($incomingData['tabel_waktu'])) {
            $decoded = json_decode($incomingData['tabel_waktu'], true);
            if (is_array($decoded)) {
                $incomingData['tabel_waktu'] = $decoded;
            }
        }

        $existingData = Session::get('kak_form_data', []);
        $mergedData = array_merge($existingData, $incomingData);
        Session::put('kak_form_data', $mergedData);

        if ($request->filled('judul')) {
            Session::put('kak_form_judul', $request->input('judul'));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Progres tersimpan di sesi.',
            'total_saved' => count($mergedData),
        ]);
    }

    /**
     * Clear draft from session.
     */
    public function clearDraft()
    {
        Session::forget(['kak_form_data', 'kak_form_judul']);
        return redirect()->route('submissions.create')->with('info', 'Draf pengisian telah dibersihkan.');
    }

    /**
     * Finalize submission and generate document.
     */
    public function finalize(Request $request)
    {
        $request->validate([
            'output_format' => 'required|in:docx,pdf',
            'judul' => 'nullable|string|max:255',
        ]);

        $fieldMap = $this->getFieldMap();

        // Merge session data with submitted data
        $sessionData = Session::get('kak_form_data', []);
        $requestData = $request->input('data', []);
        if (isset($requestData['tabel_waktu']) && is_string($requestData['tabel_waktu'])) {
            $decoded = json_decode($requestData['tabel_waktu'], true);
            if (is_array($decoded)) {
                $requestData['tabel_waktu'] = $decoded;
            }
        }

        $allData = array_merge($sessionData, $requestData);

        $judul = trim($request->input('judul') ?: Session::get('kak_form_judul', ''));
        if (empty($judul)) {
            $asdep = $allData['field_001'] ?? '';
            $tahun = $allData['field_002'] ?? '';
            $judul = trim("KAK {$asdep} Tahun {$tahun}");
            if ($judul === 'KAK' || $judul === 'KAK Tahun' || empty($judul)) {
                $judul = 'KAK Pengajuan ' . date('d F Y');
            }
        }

        $outputFormat = $request->input('output_format', 'docx');

        // Create submission record
        $submission = KakSubmission::create([
            'judul' => $judul,
            'data' => $allData,
            'status' => 'draft',
            'output_format' => $outputFormat,
        ]);

        try {
            $this->generator->generate($submission, $outputFormat);

            // Clear session draf
            Session::forget(['kak_form_data', 'kak_form_judul']);

            return redirect()->route('submissions.show', $submission)
                ->with('success', "Dokumen KAK berhasil digenerate dalam format " . strtoupper($outputFormat) . "! File otomatis diunduh ke komputer Anda.")
                ->with('auto_download', true);
        } catch (\Exception $e) {
            return redirect()->route('submissions.show', $submission)
                ->with('warning', "Data KAK tersimpan, namun proses generate mengalami kendala: " . $e->getMessage());
        }
    }

    /**
     * Regenerate document with a different or same format.
     */
    public function regenerate(Request $request, KakSubmission $submission)
    {
        $request->validate([
            'output_format' => 'required|in:docx,pdf',
        ]);

        $newFormat = $request->input('output_format');

        try {
            $this->generator->generate($submission, $newFormat);

            return redirect()->route('submissions.show', $submission)
                ->with('success', "Dokumen berhasil digenerate ulang dalam format " . strtoupper($newFormat) . "! File baru otomatis diunduh ke komputer Anda.")
                ->with('auto_download', true);
        } catch (\Exception $e) {
            return redirect()->route('submissions.show', $submission)
                ->with('error', "Gagal generate ulang dokumen: " . $e->getMessage());
        }
    }

    /**
     * Display submission details and summary.
     */
    public function show(KakSubmission $submission)
    {
        $fieldMap = $this->getFieldMap();

        // Group values by section for structured preview
        $groupedData = [];
        $data = $submission->data ?? [];

        foreach ($fieldMap as $f) {
            $section = $f['section'] ?? 'Umum';
            $groupedData[$section][] = [
                'key' => $f['key'],
                'label' => $f['label'],
                'context' => $f['context'],
                'is_code' => $f['is_code'] ?? false,
                'placeholder' => $f['placeholder'] ?? '',
                'value' => $data[$f['key']] ?? '-',
                'is_textarea' => $f['is_textarea'] ?? false,
            ];
        }

        $tabelWaktu = $data['tabel_waktu'] ?? [];

        return view('submissions.show', compact('submission', 'groupedData', 'tabelWaktu'));
    }

    /**
     * Single download route that delivers the generated file with exact MIME type.
     */
    public function download(KakSubmission $submission)
    {
        $filePath = $submission->getAbsoluteFilePath();

        if (!$filePath || !file_exists($filePath)) {
            return redirect()->route('submissions.show', $submission)
                ->with('error', 'File dokumen hasil generate tidak ditemukan di server. Silakan klik generate ulang.');
        }

        $format = strtolower($submission->output_format ?: 'docx');
        $safeTitle = Str::slug($submission->display_judul ?: "KAK_{$submission->id}", '_');
        $downloadFilename = "{$safeTitle}.{$format}";

        if ($format === 'pdf') {
            $mimeType = 'application/pdf';
        } else {
            $mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        }

        return response()->download($filePath, $downloadFilename, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $downloadFilename . '"',
        ]);
    }

    /**
     * Delete submission and associated generated file.
     */
    public function destroy(KakSubmission $submission)
    {
        $filePath = $submission->getAbsoluteFilePath();
        if ($filePath && file_exists($filePath)) {
            @unlink($filePath);
        }

        $submission->delete();

        return redirect()->route('submissions.index')
            ->with('success', 'Submission KAK berhasil dihapus.');
    }

    /**
     * Helper to read field_map.json.
     */
    protected function getFieldMap(): array
    {
        $paths = [
            resource_path('templates/field_map.json'),
            storage_path('app/templates/field_map.json'),
        ];

        foreach ($paths as $p) {
            if (file_exists($p)) {
                $decoded = json_decode(file_get_contents($p), true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return [];
    }
}
