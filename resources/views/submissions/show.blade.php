@extends('layouts.app')

@section('title', 'Hasil Dokumen KAK - ' . $submission->display_judul)
@section('page_title', 'Detail Dokumen KAK')

@section('content')
<div x-data="{ showRegenerateModal: false, selectedNewFormat: '{{ $submission->output_format === 'docx' ? 'pdf' : 'docx' }}' }" class="space-y-6">

    <!-- Breadcrumb & Navigation -->
    <div class="flex items-center justify-between">
        <a href="{{ route('submissions.index') }}" class="inline-flex items-center space-x-2 text-xs font-bold text-slate-500 hover:text-brand-600 transition">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            <span>Kembali ke Riwayat Dokumen</span>
        </a>

        <div class="flex items-center space-x-2">
            <span class="text-xs text-slate-400">ID Dokumen:</span>
            <span class="text-xs font-mono font-bold bg-white text-slate-700 px-2 py-0.5 rounded-lg border border-slate-200">#{{ $submission->id }}</span>
        </div>
    </div>

    @if(session('auto_download') || request('auto_download'))
        <!-- Auto Download Notice Banner (No inline emoji, clean Font Awesome icon) -->
        <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md shadow-emerald-600/10 flex items-center justify-between gap-4 border border-emerald-500/50">
            <div class="flex items-center space-x-3.5">
                <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center font-bold text-white shrink-0 shadow-inner">
                    <i class="fa-solid fa-circle-down text-lg"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-xs sm:text-sm leading-tight">File Otomatis Sedang Diunduh!</h3>
                    <p class="text-[11px] text-emerald-100 mt-0.5">
                        Dokumen KAK berformat <strong>{{ strtoupper($submission->output_format) }}</strong> otomatis dikirimkan ke peramban Anda.
                    </p>
                </div>
            </div>
            <a href="{{ route('submissions.download', $submission) }}" class="shrink-0 inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-white text-emerald-800 font-bold text-xs shadow-xs hover:bg-emerald-50 active:scale-95 transition">
                <i class="fa-solid fa-download text-xs"></i>
                <span>Unduh Manual</span>
            </a>
        </div>

        <!-- Hidden IFrame to initiate file download automatically -->
        <iframe id="downloadIframe" src="{{ route('submissions.download', $submission) }}" class="hidden" style="display:none; width:0; height:0; border:0;"></iframe>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    window.location.href = "{{ route('submissions.download', $submission) }}";
                }, 150);
            });
        </script>
    @endif

    <!-- Header Card with Single Download Button & Status -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="p-6 sm:p-7 bg-gradient-to-br from-slate-900 via-slate-800 to-brand-950 text-white relative">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                <div class="space-y-2 max-w-3xl">
                    <div class="flex items-center space-x-2.5">
                        @if($submission->output_format === 'pdf')
                            <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-rose-600 text-white tracking-wider uppercase shadow-xs">
                                <i class="fa-solid fa-file-pdf"></i>
                                <span>Format PDF (.pdf)</span>
                            </span>
                        @else
                            <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-blue-600 text-white tracking-wider uppercase shadow-xs">
                                <i class="fa-solid fa-file-word"></i>
                                <span>Format Word (.docx)</span>
                            </span>
                        @endif

                        <span class="text-xs text-slate-300">
                            Dibuat pada {{ $submission->created_at->translatedFormat('d F Y, H:i') }} WIB
                        </span>
                    </div>

                    <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white leading-tight">
                        {{ $submission->display_judul }}
                    </h1>

                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                        Dokumen Kerangka Acuan Kerja telah disubstitusikan ke template resmi Kemenko PMK dengan seluruh kolom data yang telah Anda lengkapi.
                    </p>
                </div>

                <!-- Action Controls: Single Adaptive Download Button & Format Switcher -->
                <div class="shrink-0 flex flex-col sm:flex-row md:flex-col gap-2.5">
                    @if($submission->output_format === 'pdf')
                        <a href="{{ route('submissions.download', $submission) }}" 
                           class="inline-flex items-center justify-center space-x-2 px-5 py-3 rounded-xl bg-gradient-to-r from-rose-600 to-red-600 text-white font-extrabold text-xs sm:text-sm shadow-md shadow-rose-600/30 hover:from-rose-500 hover:to-red-500 active:scale-95 transition">
                            <i class="fa-solid fa-download text-sm"></i>
                            <span>Unduh Berkas PDF</span>
                        </a>
                    @else
                        <a href="{{ route('submissions.download', $submission) }}" 
                           class="inline-flex items-center justify-center space-x-2 px-5 py-3 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 text-white font-extrabold text-xs sm:text-sm shadow-md shadow-brand-500/30 hover:from-brand-500 hover:to-indigo-500 active:scale-95 transition">
                            <i class="fa-solid fa-download text-sm"></i>
                            <span>Unduh Berkas Word</span>
                        </a>
                    @endif

                    <button type="button" 
                            @click="showRegenerateModal = true"
                            class="inline-flex items-center justify-center space-x-2 px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-bold transition">
                        <i class="fa-solid fa-arrows-rotate text-xs"></i>
                        <span>Generate Ulang Format Lain</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Meta Information Bar -->
        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex flex-wrap items-center justify-between text-xs text-slate-500 gap-3">
            <div class="flex items-center space-x-4">
                <span>File Path: <code class="bg-white px-2 py-0.5 rounded border border-slate-200 text-slate-700 font-mono text-[11px]">{{ $submission->generated_file_path ?: 'Belum tergenerate' }}</code></span>
                <span>Ukuran: <strong class="text-slate-700 font-semibold">{{ $submission->getAbsoluteFilePath() && file_exists($submission->getAbsoluteFilePath()) ? number_format(filesize($submission->getAbsoluteFilePath()) / 1024, 1) . ' KB' : '-' }}</strong></span>
            </div>
            <div>
                <a href="{{ route('submissions.create') }}" class="font-bold text-brand-600 hover:text-brand-700 inline-flex items-center space-x-1">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Buat Dokumen KAK Baru</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Matriks Jadwal Pelaksanaan / Waktu Pencapaian Keluaran -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="px-6 py-4.5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4 bg-slate-50/70">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">
                    <i class="fa-solid fa-calendar-days text-sm"></i>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-bold text-slate-900">D. Waktu Pencapaian Keluaran (Matriks Jadwal 12 Bulan)</h2>
                    <p class="text-xs text-slate-500">Tabel jadwal kegiatan yang telah disinkronisasikan dan terceklis pada dokumen KAK (Word / PDF)</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-xs"></i>
                    <span>Terceklis di Dokumen Template</span>
                </span>
            </div>
        </div>

        <div class="p-6">
            <div class="overflow-x-auto border border-slate-200 rounded-xl shadow-2xs">
                <table class="w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100/80 text-slate-700 font-extrabold border-b border-slate-200">
                            <th class="py-3 px-3 w-12 text-center border-r border-slate-200">No.</th>
                            <th class="py-3 px-4 min-w-[280px] border-r border-slate-200">Kegiatan</th>
                            <th colspan="12" class="py-2 px-2 text-center border-b border-slate-200 bg-slate-200/60 text-slate-800 font-bold">
                                Bulan Pelaksanaan (1 s.d. 12)
                            </th>
                        </tr>
                        <tr class="bg-slate-50 text-[11px] font-bold text-slate-600 border-b border-slate-200">
                            <th class="border-r border-slate-200"></th>
                            <th class="border-r border-slate-200"></th>
                            @for($m = 1; $m <= 12; $m++)
                                <th class="py-1.5 px-2 text-center w-9 border-r border-slate-200 {{ $m === 12 ? 'border-r-0' : '' }} bg-slate-100/50">
                                    {{ $m }}
                                </th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-medium">
                        <!-- Row RAK -->
                        <tr class="bg-slate-100/60 font-bold text-slate-800">
                            <td class="py-2.5 px-3 text-center border-r border-slate-200 font-mono"></td>
                            <td colspan="13" class="py-2.5 px-4 font-bold text-slate-800">
                                RAK: <span class="text-brand-700">{{ $tabelWaktu['rak_title'] ?? ($submission->data['field_020'] ?? 'RAK ...') }}</span>
                            </td>
                        </tr>

                        <!-- Row Subkomponen 1 -->
                        <tr class="bg-slate-50/80 font-bold text-slate-800">
                            <td class="py-2 px-3 text-center border-r border-slate-200"></td>
                            <td colspan="13" class="py-2 px-4 text-slate-700 font-semibold italic">
                                Subkomponen: {{ $tabelWaktu['subkomponen_1'] ?? ($submission->data['field_018'] ?? 'Subkomponen 1') }}
                            </td>
                        </tr>

                        @php
                            $sub1Activities = [
                                1 => ['nama' => 'Melaksanakan Identifikasi Permasalahan', 'key' => 'kegiatan_1', 'default' => [1, 2, 3]],
                                2 => ['nama' => 'Melaksanakan Sinkronisasi, Koordinasi, dan Pengendalian', 'key' => 'kegiatan_2', 'default' => [2, 3, 4, 5, 6, 7, 8, 9, 10, 11]],
                                3 => ['nama' => 'Melaksanakan Monitoring dan Evaluasi', 'key' => 'kegiatan_3', 'default' => [4, 7, 10]],
                                4 => ['nama' => 'Menyusun Rekomendasi Kebijakan', 'key' => 'kegiatan_4', 'default' => [11, 12]],
                            ];
                        @endphp

                        @foreach($sub1Activities as $no => $act)
                            @php
                                $checkedMonths = $tabelWaktu[$act['key']] ?? $act['default'];
                                if (!is_array($checkedMonths)) {
                                    $checkedMonths = [];
                                }
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-2.5 px-3 text-center border-r border-slate-200 text-slate-500 font-mono">{{ $no }}</td>
                                <td class="py-2.5 px-4 border-r border-slate-200 text-slate-800 font-medium">{{ $act['nama'] }}</td>
                                @for($m = 1; $m <= 12; $m++)
                                    @php $checked = in_array($m, $checkedMonths); @endphp
                                    <td class="py-2 px-1 text-center border-r border-slate-200 {{ $m === 12 ? 'border-r-0' : '' }} {{ $checked ? 'bg-[#93c47d]/30 font-bold text-emerald-900' : 'text-slate-300' }}">
                                        @if($checked)
                                            <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-[#93c47d] text-emerald-950 font-bold text-[10px] shadow-2xs">
                                                <i class="fa-solid fa-check"></i>
                                            </span>
                                        @else
                                            <span class="text-slate-300 font-light">&middot;</span>
                                        @endif
                                    </td>
                                @endfor
                            </tr>
                        @endforeach

                        @if(!empty($tabelWaktu['has_subkomponen_2']))
                            <!-- Row Subkomponen 2 -->
                            <tr class="bg-indigo-50/50 font-bold text-slate-800 border-t border-slate-200">
                                <td class="py-2 px-3 text-center border-r border-slate-200"></td>
                                <td colspan="13" class="py-2 px-4 text-indigo-900 font-semibold italic">
                                    Subkomponen: {{ $tabelWaktu['subkomponen_2'] ?? 'Subkomponen 2' }}
                                </td>
                            </tr>

                            @php
                                $sub2Activities = [
                                    1 => ['nama' => 'Melaksanakan Identifikasi Permasalahan', 'key' => 'sub2_kegiatan_1'],
                                    2 => ['nama' => 'Melaksanakan Sinkronisasi, Koordinasi, dan Pengendalian', 'key' => 'sub2_kegiatan_2'],
                                    3 => ['nama' => 'Melaksanakan Monitoring dan Evaluasi', 'key' => 'sub2_kegiatan_3'],
                                    4 => ['nama' => 'Menyusun Rekomendasi Kebijakan', 'key' => 'sub2_kegiatan_4'],
                                ];
                            @endphp

                            @foreach($sub2Activities as $no => $act)
                                @php
                                    $checkedMonths = $tabelWaktu[$act['key']] ?? [];
                                    if (!is_array($checkedMonths)) {
                                        $checkedMonths = [];
                                    }
                                @endphp
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="py-2.5 px-3 text-center border-r border-slate-200 text-slate-500 font-mono">{{ $no }}</td>
                                    <td class="py-2.5 px-4 border-r border-slate-200 text-slate-800 font-medium">{{ $act['nama'] }}</td>
                                    @for($m = 1; $m <= 12; $m++)
                                        @php $checked = in_array($m, $checkedMonths); @endphp
                                        <td class="py-2 px-1 text-center border-r border-slate-200 {{ $m === 12 ? 'border-r-0' : '' }} {{ $checked ? 'bg-[#93c47d]/30 font-bold text-emerald-900' : 'text-slate-300' }}">
                                            @if($checked)
                                                <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-[#93c47d] text-emerald-950 font-bold text-[10px] shadow-2xs">
                                                    <i class="fa-solid fa-check"></i>
                                                </span>
                                            @else
                                                <span class="text-slate-300 font-light">&middot;</span>
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between text-xs text-slate-500 gap-2">
                <div class="flex items-center space-x-4">
                    <span class="flex items-center space-x-1.5">
                        <span class="w-3.5 h-3.5 rounded bg-[#93c47d] inline-block border border-emerald-600/30"></span>
                        <span class="text-slate-600 font-medium">Bulan Aktif Pelaksanaan</span>
                    </span>
                    <span class="flex items-center space-x-1.5">
                        <span class="w-3.5 h-3.5 rounded bg-white inline-block border border-slate-200"></span>
                        <span class="text-slate-400">Tidak Ada Kegiatan</span>
                    </span>
                </div>
                <div class="italic text-[11px] text-slate-400">
                    Sesuai dengan tabel D. Waktu Pencapaian Keluaran pada dokumen template.
                </div>
            </div>
        </div>
    </div>

    <!-- Data Preview Section (Grouped by Document Section) -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base sm:text-lg font-bold text-slate-900">Rekapitulasi Data Isian KAK</h2>
                <p class="text-xs text-slate-500">Rincian nilai yang disubstitusikan ke dalam merge field dokumen template.</p>
            </div>
            <span class="text-xs font-semibold bg-white text-slate-600 px-3 py-1 rounded-full border border-slate-200 shadow-2xs">
                {{ count($submission->data ?? []) }} Nilai Tersimpan
            </span>
        </div>

        @foreach($groupedData as $sectionTitle => $fields)
            <div x-data="{ open: false }" class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
                <button type="button" 
                        @click="open = !open" 
                        class="w-full px-6 py-4 flex items-center justify-between text-left hover:bg-slate-50 transition border-b border-slate-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-2.5 h-2.5 rounded-full bg-brand-500"></div>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-800">{{ $sectionTitle }}</h3>
                        <span class="text-xs text-slate-400">({{ count($fields) }} kolom)</span>
                    </div>
                    <div class="flex items-center space-x-2 text-slate-400">
                        <span class="text-xs font-semibold" x-text="open ? 'Tutup' : 'Lihat Detail'"></span>
                        <i class="fa-solid fa-chevron-down text-xs transform transition-transform" :class="open ? 'rotate-180' : ''"></i>
                    </div>
                </button>

                <div x-show="open" class="p-6 bg-slate-50/50 divide-y divide-slate-100">
                    <div class="grid grid-cols-1 gap-4">
                        @foreach($fields as $item)
                            @php
                                $isCode = $item['is_code'] ?? false;
                            @endphp
                            <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-2xs space-y-1.5 {{ $isCode ? 'bg-amber-50/20 border-amber-200/60' : '' }}">
                                <div class="flex items-center justify-between text-xs gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-800">{{ $item['label'] }}</span>
                                        @if($isCode)
                                            <span class="inline-flex items-center gap-1 bg-amber-100 text-amber-800 border border-amber-300 text-[9px] font-extrabold px-1.5 py-0.2 rounded uppercase tracking-wider">
                                                KODE / NOMOR
                                            </span>
                                        @endif
                                    </div>
                                    <span class="font-mono text-[10px] text-slate-400 bg-slate-50 px-1.5 py-0.5 rounded border border-slate-200">{{ $item['key'] }}</span>
                                </div>
                                <div class="text-xs text-slate-500 italic">
                                    Konteks Dokumen: "{{ $item['context'] }}"
                                </div>
                                <div class="pt-2 mt-1 border-t border-slate-100">
                                    @if($item['value'] === '-' || empty($item['value']))
                                        <span class="text-xs text-slate-400 italic">- (kosong / default)</span>
                                    @else
                                        <div class="text-xs text-slate-900 font-medium whitespace-pre-line bg-slate-50 p-2.5 rounded-lg border border-slate-200 {{ $isCode ? 'font-mono text-amber-950 font-bold' : '' }}">
                                            {{ $item['value'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Modal Regenerate Format -->
    <div x-show="showRegenerateModal" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="showRegenerateModal = false" class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 space-y-4 border border-slate-200">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900">Generate Ulang Format</h3>
                <button type="button" @click="showRegenerateModal = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
                Anda dapat membuat ulang dokumen KAK dengan format lain tanpa harus mengisi ulang data. Berkas sebelumnya akan digantikan oleh format baru yang Anda pilih.
            </p>

            <form action="{{ route('submissions.regenerate', $submission) }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-2.5">
                    <label class="flex items-center p-3 rounded-xl border-2 cursor-pointer transition"
                           :class="selectedNewFormat === 'docx' ? 'border-brand-600 bg-brand-50' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="output_format" value="docx" x-model="selectedNewFormat" class="sr-only">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs mr-3">
                            <i class="fa-solid fa-file-word text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-xs font-bold text-slate-900 block">Word (.docx)</span>
                            <span class="text-[11px] text-slate-500">Format Microsoft Word yang dapat diedit kembali</span>
                        </div>
                        <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                             :class="selectedNewFormat === 'docx' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300'">
                            <span x-show="selectedNewFormat === 'docx'" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                        </div>
                    </label>

                    <label class="flex items-center p-3 rounded-xl border-2 cursor-pointer transition"
                           :class="selectedNewFormat === 'pdf' ? 'border-rose-600 bg-rose-50' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="output_format" value="pdf" x-model="selectedNewFormat" class="sr-only">
                        <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-xs mr-3">
                            <i class="fa-solid fa-file-pdf text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-xs font-bold text-slate-900 block">PDF (.pdf)</span>
                            <span class="text-[11px] text-slate-500">Format PDF siap cetak via LibreOffice headless</span>
                        </div>
                        <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                             :class="selectedNewFormat === 'pdf' ? 'border-rose-600 bg-rose-600 text-white' : 'border-slate-300'">
                            <span x-show="selectedNewFormat === 'pdf'" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                        </div>
                    </label>
                </div>

                <div class="pt-2 flex items-center justify-end space-x-2">
                    <button type="button" @click="showRegenerateModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 text-white text-xs font-bold shadow-xs hover:from-brand-700 hover:to-indigo-700 transition">
                        Proses Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
