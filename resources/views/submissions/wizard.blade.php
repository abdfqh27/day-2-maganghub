@extends('layouts.app')

@section('title', 'Formulir Pengisian KAK Digital')

@section('content')
<div x-data="kakWizard()" class="max-w-5xl mx-auto">

    <!-- Wizard Header & Title -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center space-x-2 text-xs font-semibold uppercase tracking-wider text-brand-600 bg-brand-50 px-3 py-1 rounded-full mb-2 border border-brand-100">
                <span>Multi-Step Wizard</span>
                <span>•</span>
                <span x-text="'Langkah ' + (currentStep + 1) + ' dari ' + totalSteps"></span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight" x-text="stepTitles[currentStep]"></h1>
            <p class="text-sm text-slate-500 mt-1">Lengkapi data Kerangka Acuan Kegiatan sesuai template digitalisasi dokumen resmi.</p>
        </div>

        <div class="flex items-center space-x-3">
            <span x-show="autoSavedMessage" x-transition class="text-xs font-semibold text-emerald-600 flex items-center space-x-1 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span x-text="autoSavedMessage"></span>
            </span>

            <form action="{{ route('submissions.clearDraft') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh draf isian form?');">
                @csrf
                <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-rose-600 bg-white hover:bg-rose-50 border border-slate-200 hover:border-rose-200 px-3 py-1.5 rounded-lg transition shadow-sm">
                    Reset Draf
                </button>
            </form>
        </div>
    </div>

    <!-- Progress Bar & Step Navigation Indicator -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 mb-8">
        <div class="flex items-center justify-between text-xs font-bold text-slate-600 mb-2">
            <span>Progress Pengisian</span>
            <span class="text-brand-600" x-text="progressPercentage + '%'"></span>
        </div>
        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
            <div class="bg-gradient-to-r from-brand-500 to-indigo-600 h-2.5 rounded-full transition-all duration-300 ease-out" :style="'width: ' + progressPercentage + '%'"></div>
        </div>

        <!-- Stepper Pill Tabs -->
        <div class="mt-4 flex items-center space-x-1.5 overflow-x-auto pb-1 scrollbar-thin">
            <template x-for="(title, idx) in stepTitles" :key="idx">
                <button type="button"
                        @click="goToStep(idx)"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition flex items-center space-x-1.5"
                        :class="{
                            'bg-brand-600 text-white shadow-sm': currentStep === idx,
                            'bg-slate-100 text-slate-700 hover:bg-slate-200': currentStep > idx,
                            'bg-slate-50 text-slate-400 hover:text-slate-600': currentStep < idx
                        }">
                    <span class="w-4 h-4 rounded-full inline-flex items-center justify-center text-[10px]"
                          :class="currentStep > idx ? 'bg-emerald-600 text-white' : (currentStep === idx ? 'bg-white text-brand-600 font-bold' : 'bg-slate-200 text-slate-600')"
                          x-text="currentStep > idx ? '✓' : (idx + 1)"></span>
                    <span x-text="title.replace(/^[0-9]+\.\s*/, '')"></span>
                </button>
            </template>
        </div>
    </div>

    <!-- Main Wizard Form -->
    <form id="kakForm" action="{{ route('submissions.finalize') }}" method="POST" @submit="handleFormSubmit($event)">
        @csrf
        <input type="hidden" name="data[tabel_waktu]" :value="JSON.stringify(formData.tabel_waktu)">

        @php
            $sectionKeys = array_keys($sections);
        @endphp

        <!-- Dynamic Steps based on Document Sections -->
        @foreach($sections as $sectionName => $fields)
            @php
                $stepIndex = array_search($sectionName, $sectionKeys);
                $hasCodeField = collect($fields)->contains('is_code', true);
                $isWaktuSection = str_contains($sectionName, 'Kurun Waktu Pelaksanaan');
            @endphp
            <div x-show="currentStep === {{ $stepIndex }}" x-cloak class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8">
                    <div class="border-b border-slate-100 pb-4 mb-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-bold text-slate-900">{{ $sectionName }}</h2>
                                <p class="text-xs text-slate-500 mt-1">Lengkapi kolom di bawah ini. Tanda <span class="text-rose-500 font-bold">*</span> menandakan field utama yang wajib diisi.</p>
                            </div>
                            <div class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 border border-blue-200 text-xs px-3 py-1.5 rounded-xl self-start sm:self-auto font-medium">
                                <span>💡 <strong>Tips:</strong> Klik tombol <em>"Gunakan Contoh"</em> pada setiap kolom untuk auto-fill contoh teks.</span>
                            </div>
                        </div>
                    </div>

                    @if($hasCodeField)
                        <div class="mb-6 p-3.5 bg-amber-50 border border-amber-200/90 rounded-2xl flex items-start gap-3 text-xs text-amber-900 shadow-2xs">
                            <span class="text-lg leading-none mt-0.5">🏷️</span>
                            <div class="leading-relaxed">
                                <p class="font-bold text-amber-950">Petunjuk Pengisian Kolom Bertanda [KODE / NOMOR]:</p>
                                <p class="mt-0.5 text-amber-800">Kolom dengan badge <span class="bg-amber-200/80 text-amber-900 font-extrabold px-1.5 py-0.5 rounded border border-amber-300">KODE / NOMOR</span> merupakan kode klasifikasi program, RO, KRO, atau indikator (sesuai tanda <code>(……)</code> pada template dokumen resmi KAK). Silakan isi dengan kode yang sesuai, atau klik tombol <em>Gunakan Contoh</em> untuk format standar.</p>
                            </div>
                        </div>
                    @endif

                    @if($isWaktuSection)
                        <!-- Interactive WAKTU PENCAPAIAN KELUARAN Schedule Table -->
                        <div class="mb-8 bg-white rounded-2xl border-2 border-brand-300/80 shadow-sm overflow-hidden">
                            <div class="bg-gradient-to-r from-brand-50 via-indigo-50/60 to-emerald-50/40 p-5 border-b border-brand-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="p-2 bg-brand-600 text-white rounded-xl shadow-xs">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        </span>
                                        <h3 class="text-base sm:text-lg font-black text-slate-900">D. WAKTU PENCAPAIAN KELUARAN (Tabel Matriks Jadwal)</h3>
                                    </div>
                                    <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
                                        Klik atau ceklis kotak bulan (1 s.d. 12) pelaksanaan kegiatan di bawah ini. Kolom yang diceklis otomatis bertanda (<strong class="text-emerald-700">✓</strong>) dan ter-highlight hijau (<code class="bg-emerald-100 text-emerald-800 px-1 py-0.5 rounded font-mono font-bold">#93c47d</code>) pada tabel dokumen Word & PDF hasil generate.
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button"
                                            @click="applyDefaultSchedule()"
                                            class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition active:scale-95 cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span>Jadwal Standar KAK</span>
                                    </button>
                                    <button type="button"
                                            @click="clearSchedule()"
                                            class="inline-flex items-center space-x-1 px-3 py-2 rounded-xl text-xs font-semibold bg-white hover:bg-slate-100 text-slate-600 border border-slate-300 transition cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        <span>Kosongkan</span>
                                    </button>
                                </div>
                            </div>

                            <div class="p-5">
                                <!-- Subkomponen 1 Title input -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5 p-4 bg-slate-50/80 rounded-xl border border-slate-200">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Judul RAK pada Tabel</label>
                                        <input type="text"
                                               x-model="formData.tabel_waktu.rak_title"
                                               placeholder="Contoh: Rekomendasi Alternatif Kebijakan Perlindungan Anak"
                                               class="w-full text-xs rounded-lg border-slate-200 bg-white px-3 py-2 focus:border-brand-500 focus:ring-brand-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama Subkomponen 1</label>
                                        <input type="text"
                                               x-model="formData.tabel_waktu.subkomponen_1"
                                               placeholder="Contoh: Koordinasi dan Sinkronisasi Kebijakan"
                                               class="w-full text-xs rounded-lg border-slate-200 bg-white px-3 py-2 focus:border-brand-500 focus:ring-brand-500">
                                    </div>
                                </div>

                                <!-- Table Responsive Container -->
                                <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-2xs">
                                    <table class="w-full text-left text-xs border-collapse min-w-[720px]">
                                        <thead>
                                            <tr class="bg-slate-100 text-slate-700 border-b border-slate-200">
                                                <th class="py-3 px-3 w-10 text-center font-extrabold">No.</th>
                                                <th class="py-3 px-4 min-w-[210px] font-extrabold">Tahapan Kegiatan</th>
                                                @php
                                                    $monthNames = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Ags', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
                                                @endphp
                                                @foreach($monthNames as $mNum => $mLabel)
                                                    <th class="py-2.5 px-1 w-10 text-center font-bold text-[11px] border-l border-slate-200 bg-slate-100/90">
                                                        <div class="leading-tight">{{ $mNum }}</div>
                                                        <div class="text-[9px] text-slate-500 uppercase font-semibold">{{ $mLabel }}</div>
                                                    </th>
                                                @endforeach
                                                <th class="py-3 px-3 text-center font-bold border-l border-slate-200 bg-slate-100 min-w-[150px]">Pilihan Cepat</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 bg-white">
                                            @php
                                                $activities = [
                                                    'kegiatan_1' => '1. Melaksanakan Identifikasi Permasalahan',
                                                    'kegiatan_2' => '2. Melaksanakan Sinkronisasi, Koordinasi, dan Pengendalian',
                                                    'kegiatan_3' => '3. Melaksanakan Monitoring dan Evaluasi',
                                                    'kegiatan_4' => '4. Menyusun Rekomendasi Kebijakan',
                                                ];
                                            @endphp
                                            @foreach($activities as $kegKey => $kegTitle)
                                                <tr class="hover:bg-slate-50/70 transition">
                                                    <td class="py-3 px-3 text-center font-bold text-slate-500">{{ $loop->iteration }}.</td>
                                                    <td class="py-3 px-4 font-semibold text-slate-800">
                                                        {{ preg_replace('/^[0-9]+\.\s*/', '', $kegTitle) }}
                                                    </td>
                                                    @for($m = 1; $m <= 12; $m++)
                                                        <td class="py-2 px-1 text-center border-l border-slate-100">
                                                            <button type="button"
                                                                    @click="toggleMonth('{{ $kegKey }}', {{ $m }})"
                                                                    :class="isMonthChecked('{{ $kegKey }}', {{ $m }}) 
                                                                        ? 'bg-emerald-600 text-white font-black border-emerald-600 shadow-2xs ring-2 ring-emerald-300/50' 
                                                                        : 'bg-slate-50 text-slate-300 hover:bg-slate-100 hover:text-slate-500 border-slate-200'"
                                                                    class="w-7 h-7 mx-auto rounded-lg border flex items-center justify-center text-xs transition active:scale-90 cursor-pointer"
                                                                    title="Bulan {{ $m }}: Klik untuk ceklis / hapus centang">
                                                                <span x-show="isMonthChecked('{{ $kegKey }}', {{ $m }})" class="font-extrabold text-sm">✓</span>
                                                                <span x-show="!isMonthChecked('{{ $kegKey }}', {{ $m }})" class="text-[10px] text-slate-400 font-mono">{{ $m }}</span>
                                                            </button>
                                                        </td>
                                                    @endfor
                                                    <td class="py-2 px-2 text-center border-l border-slate-100 bg-slate-50/50">
                                                        <div class="flex items-center justify-center gap-1 text-[10px]">
                                                            <button type="button" @click="setQuarter('{{ $kegKey }}', 1)" class="px-1.5 py-0.5 rounded bg-white hover:bg-slate-100 border border-slate-200 text-slate-600 font-bold transition">Q1</button>
                                                            <button type="button" @click="setQuarter('{{ $kegKey }}', 2)" class="px-1.5 py-0.5 rounded bg-white hover:bg-slate-100 border border-slate-200 text-slate-600 font-bold transition">Q2</button>
                                                            <button type="button" @click="setQuarter('{{ $kegKey }}', 3)" class="px-1.5 py-0.5 rounded bg-white hover:bg-slate-100 border border-slate-200 text-slate-600 font-bold transition">Q3</button>
                                                            <button type="button" @click="setQuarter('{{ $kegKey }}', 4)" class="px-1.5 py-0.5 rounded bg-white hover:bg-slate-100 border border-slate-200 text-slate-600 font-bold transition">Q4</button>
                                                            <button type="button" @click="setAllMonths('{{ $kegKey }}')" class="px-1.5 py-0.5 rounded bg-brand-50 hover:bg-brand-100 border border-brand-200 text-brand-700 font-bold transition">All</button>
                                                            <button type="button" @click="resetMonths('{{ $kegKey }}')" class="px-1.5 py-0.5 rounded bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 font-bold transition">✕</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Optional Subkomponen 2 Accordion -->
                                <div class="mt-5 pt-4 border-t border-slate-200">
                                    <label class="inline-flex items-center space-x-2 text-xs font-bold text-slate-700 cursor-pointer select-none">
                                        <input type="checkbox"
                                               x-model="formData.tabel_waktu.has_subkomponen_2"
                                               class="w-4 h-4 rounded text-brand-600 border-slate-300 focus:ring-brand-500">
                                        <span>Aktifkan Isian Subkomponen 2 pada Tabel (Opsional)</span>
                                    </label>

                                    <div x-show="formData.tabel_waktu.has_subkomponen_2" x-transition class="mt-4 space-y-3 p-4 bg-indigo-50/50 rounded-xl border border-indigo-100">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Subkomponen 2</label>
                                            <input type="text"
                                                   x-model="formData.tabel_waktu.subkomponen_2"
                                                   placeholder="Contoh: Pengendalian dan Evaluasi Kebijakan Tambahan"
                                                   class="w-full text-xs rounded-lg border-slate-200 bg-white px-3 py-2 focus:border-brand-500 focus:ring-brand-500">
                                        </div>

                                        <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-2xs">
                                            <table class="w-full text-left text-xs border-collapse min-w-[720px]">
                                                <thead>
                                                    <tr class="bg-indigo-100/70 text-indigo-900 border-b border-indigo-200">
                                                        <th class="py-2.5 px-3 w-10 text-center font-extrabold">No.</th>
                                                        <th class="py-2.5 px-4 min-w-[210px] font-extrabold">Tahapan Kegiatan (Subkomponen 2)</th>
                                                        @foreach($monthNames as $mNum => $mLabel)
                                                            <th class="py-2 px-1 w-10 text-center font-bold text-[11px] border-l border-indigo-200 bg-indigo-50/80">
                                                                <div>{{ $mNum }}</div>
                                                                <div class="text-[9px] text-indigo-500 uppercase">{{ $mLabel }}</div>
                                                            </th>
                                                        @endforeach
                                                        <th class="py-2.5 px-3 text-center font-bold border-l border-indigo-200 bg-indigo-100/70">Pilihan Cepat</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-200 bg-white">
                                                    @php
                                                        $sub2Activities = [
                                                            'sub2_kegiatan_1' => '1. Melaksanakan Identifikasi Permasalahan',
                                                            'sub2_kegiatan_2' => '2. Melaksanakan Sinkronisasi, Koordinasi, dan Pengendalian',
                                                            'sub2_kegiatan_3' => '3. Melaksanakan Monitoring dan Evaluasi',
                                                            'sub2_kegiatan_4' => '4. Menyusun Rekomendasi Kebijakan',
                                                        ];
                                                    @endphp
                                                    @foreach($sub2Activities as $kegKey => $kegTitle)
                                                        <tr class="hover:bg-slate-50/70 transition">
                                                            <td class="py-2.5 px-3 text-center font-bold text-slate-500">{{ $loop->iteration }}.</td>
                                                            <td class="py-2.5 px-4 font-semibold text-slate-800">
                                                                {{ preg_replace('/^[0-9]+\.\s*/', '', $kegTitle) }}
                                                            </td>
                                                            @for($m = 1; $m <= 12; $m++)
                                                                <td class="py-1.5 px-1 text-center border-l border-slate-100">
                                                                    <button type="button"
                                                                            @click="toggleMonth('{{ $kegKey }}', {{ $m }})"
                                                                            :class="isMonthChecked('{{ $kegKey }}', {{ $m }}) 
                                                                                ? 'bg-emerald-600 text-white font-black border-emerald-600 shadow-2xs' 
                                                                                : 'bg-slate-50 text-slate-300 hover:bg-slate-100 hover:text-slate-500 border-slate-200'"
                                                                            class="w-7 h-7 mx-auto rounded-lg border flex items-center justify-center text-xs transition active:scale-90 cursor-pointer">
                                                                        <span x-show="isMonthChecked('{{ $kegKey }}', {{ $m }})" class="font-extrabold text-sm">✓</span>
                                                                        <span x-show="!isMonthChecked('{{ $kegKey }}', {{ $m }})" class="text-[10px] text-slate-400 font-mono">{{ $m }}</span>
                                                                    </button>
                                                                </td>
                                                            @endfor
                                                            <td class="py-1.5 px-2 text-center border-l border-slate-100 bg-slate-50/50">
                                                                <div class="flex items-center justify-center gap-1 text-[10px]">
                                                                    <button type="button" @click="setQuarter('{{ $kegKey }}', 1)" class="px-1.5 py-0.5 rounded bg-white hover:bg-slate-100 border border-slate-200 text-slate-600 font-bold transition">Q1</button>
                                                                    <button type="button" @click="setQuarter('{{ $kegKey }}', 2)" class="px-1.5 py-0.5 rounded bg-white hover:bg-slate-100 border border-slate-200 text-slate-600 font-bold transition">Q2</button>
                                                                    <button type="button" @click="setQuarter('{{ $kegKey }}', 3)" class="px-1.5 py-0.5 rounded bg-white hover:bg-slate-100 border border-slate-200 text-slate-600 font-bold transition">Q3</button>
                                                                    <button type="button" @click="setQuarter('{{ $kegKey }}', 4)" class="px-1.5 py-0.5 rounded bg-white hover:bg-slate-100 border border-slate-200 text-slate-600 font-bold transition">Q4</button>
                                                                    <button type="button" @click="setAllMonths('{{ $kegKey }}')" class="px-1.5 py-0.5 rounded bg-brand-50 hover:bg-brand-100 border border-brand-200 text-brand-700 font-bold transition">All</button>
                                                                    <button type="button" @click="resetMonths('{{ $kegKey }}')" class="px-1.5 py-0.5 rounded bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 font-bold transition">✕</button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-6">
                        @foreach($fields as $f)
                            @php
                                $key = $f['key'];
                                $val = old("data.{$key}", $draftData[$key] ?? '');
                                $isTextarea = $f['is_textarea'] ?? false;
                                $isRequired = $f['required'] ?? false;
                                $isCode = $f['is_code'] ?? false;
                                $placeholderText = $f['placeholder'] ?? 'Isi data di sini...';
                            @endphp
                            <div class="space-y-2 p-5 rounded-2xl transition border {{ $isCode ? 'bg-amber-50/30 border-amber-200/80 hover:border-amber-300 hover:shadow-xs' : 'bg-slate-50/60 border-slate-200/80 hover:border-brand-200 hover:shadow-2xs' }}">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <label for="{{ $key }}" class="text-sm font-bold text-slate-800 flex flex-wrap items-center gap-2">
                                        <span>{{ $f['label'] }}</span>
                                        @if($isRequired)
                                            <span class="text-rose-500 text-sm font-black" title="Field Wajib">*</span>
                                            <span class="text-[10px] uppercase font-extrabold bg-rose-50 text-rose-600 border border-rose-200 px-1.5 py-0.5 rounded">Wajib</span>
                                        @endif
                                        @if($isCode)
                                            <span class="inline-flex items-center gap-1 bg-amber-100 text-amber-800 border border-amber-300 text-[10px] font-extrabold px-2 py-0.5 rounded-md shadow-2xs tracking-wide uppercase">
                                                <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                                KODE / NOMOR
                                            </span>
                                        @endif
                                    </label>
                                    <span class="text-[11px] font-mono text-slate-400 bg-white px-2 py-0.5 rounded border border-slate-200">{{ $key }}</span>
                                </div>

                                @if(!empty($f['context']) && $f['context'] !== '[......]')
                                    <p class="text-xs text-slate-500 italic bg-white/90 p-2.5 rounded-xl border border-slate-200/80 leading-relaxed">
                                        <span class="font-semibold text-slate-700 not-italic">Posisi di Dokumen:</span> "{{ $f['context'] }}"
                                    </p>
                                @endif

                                @if($isTextarea)
                                    <textarea id="{{ $key }}"
                                              name="data[{{ $key }}]"
                                              rows="4"
                                              x-model="formData['{{ $key }}']"
                                              placeholder="{{ $placeholderText }}"
                                              class="w-full text-sm rounded-xl border-slate-200 bg-white p-3.5 shadow-sm focus:border-brand-500 focus:ring-brand-500 transition placeholder:text-slate-400 placeholder:italic {{ $isCode ? 'font-mono' : '' }}"></textarea>
                                @else
                                    <input type="text"
                                           id="{{ $key }}"
                                           name="data[{{ $key }}]"
                                           x-model="formData['{{ $key }}']"
                                           placeholder="{{ $placeholderText }}"
                                           class="w-full text-sm rounded-xl border-slate-200 bg-white px-4 py-2.5 shadow-sm focus:border-brand-500 focus:ring-brand-500 transition placeholder:text-slate-400 placeholder:italic {{ $isCode ? 'font-mono font-medium' : '' }}">
                                @endif

                                <!-- Interactive Example Card -->
                                <div class="mt-2 p-2.5 rounded-xl border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs {{ $isCode ? 'bg-amber-100/50 border-amber-200/80 text-amber-950' : 'bg-slate-100/70 border-slate-200/80 text-slate-700' }}">
                                    <div class="flex items-start sm:items-center gap-2 min-w-0">
                                        <span class="font-bold shrink-0 flex items-center gap-1 {{ $isCode ? 'text-amber-800' : 'text-brand-700' }}">
                                            <span>💡</span> <span>Contoh:</span>
                                        </span>
                                        <span class="italic font-medium select-all truncate sm:whitespace-normal {{ $isCode ? 'font-mono text-amber-900 bg-amber-200/50 px-1.5 py-0.5 rounded border border-amber-300/60' : 'text-slate-700' }}" title="{{ $placeholderText }}">"{{ $placeholderText }}"</span>
                                    </div>
                                    <button type="button"
                                            @click="useExample('{{ $key }}', '{{ addslashes($placeholderText) }}')"
                                            title="Klik untuk mengisi kolom ini dengan contoh teks"
                                            class="shrink-0 self-end sm:self-auto inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold transition shadow-2xs cursor-pointer active:scale-95 {{ $isCode ? 'bg-amber-200 text-amber-900 hover:bg-amber-300' : 'bg-brand-50 text-brand-700 border border-brand-200 hover:bg-brand-100' }}">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                        <span>Gunakan Contoh</span>
                                    </button>
                                </div>

                                @error("data.{$key}")
                                    <p class="text-xs text-rose-600 font-semibold mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Final Step: Pilih Format Output & Finalisasi -->
        @php
            $finalStepIndex = count($sectionKeys);
        @endphp
        <div x-show="currentStep === {{ $finalStepIndex }}" x-cloak class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8">
                <div class="border-b border-slate-100 pb-4 mb-6">
                    <h2 class="text-xl font-bold text-slate-900">Langkah Terakhir: Pilih Format Dokumen</h2>
                    <p class="text-xs text-slate-500 mt-1">Pilih format unduhan yang Anda inginkan. Sistem hanya akan men-generate format yang Anda pilih agar proses lebih cepat dan ringan.</p>
                </div>

                <!-- Custom Judul Submission -->
                <div class="mb-8 p-5 bg-slate-50 rounded-2xl border border-slate-200/80">
                    <label for="judul" class="block text-sm font-bold text-slate-800 mb-1">
                        Judul Singkat Dokumen KAK <span class="text-slate-400 font-normal">(opsional)</span>
                    </label>
                    <input type="text"
                           id="judul"
                           name="judul"
                           x-model="judulDoc"
                           placeholder="Contoh: KAK Asisten Deputi Pemenuhan Hak Anak Tahun 2026"
                           class="w-full text-sm rounded-xl border-slate-200 bg-white px-4 py-2.5 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    <p class="text-xs text-slate-500 mt-1.5">Jika dikosongkan, judul akan otomatis dirangkai dari Asisten Deputi dan Tahun Anggaran yang telah Anda isi.</p>
                </div>

                <!-- Pilihan Format Dokumen: Word vs PDF -->
                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-800 mb-3">Pilih Format Output Dokumen <span class="text-rose-500">*</span></label>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Option 1: Word .docx -->
                        <label class="relative flex flex-col p-5 rounded-2xl border-2 cursor-pointer transition-all duration-200 select-none shadow-sm"
                               :class="outputFormat === 'docx' ? 'border-brand-600 bg-brand-50/50 ring-2 ring-brand-500/20' : 'border-slate-200 bg-white hover:border-slate-300'">
                            <input type="radio" name="output_format" value="docx" x-model="outputFormat" class="sr-only">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-black text-sm shadow-sm">
                                    DOCX
                                </div>
                                <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center"
                                     :class="outputFormat === 'docx' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300'">
                                    <svg x-show="outputFormat === 'docx'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                            </div>
                            <span class="text-base font-bold text-slate-900 block">Unduh sebagai Word (.docx)</span>
                            <span class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Dokumen Microsoft Word resmi hasil substitusi template. Cepat, ringan, dan siap diedit kembali sewaktu-waktu.
                            </span>
                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center text-xs font-semibold text-brand-700">
                                <span class="w-2 h-2 rounded-full bg-brand-600 mr-2"></span>
                                Direkomendasikan (Native Generator)
                            </div>
                        </label>

                        <!-- Option 2: PDF .pdf -->
                        <label class="relative flex flex-col p-5 rounded-2xl border-2 cursor-pointer transition-all duration-200 select-none shadow-sm"
                               :class="outputFormat === 'pdf' ? 'border-rose-600 bg-rose-50/50 ring-2 ring-rose-500/20' : 'border-slate-200 bg-white hover:border-slate-300'">
                            <input type="radio" name="output_format" value="pdf" x-model="outputFormat" class="sr-only">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center font-black text-sm shadow-sm">
                                    PDF
                                </div>
                                <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center"
                                     :class="outputFormat === 'pdf' ? 'border-rose-600 bg-rose-600 text-white' : 'border-slate-300'">
                                    <svg x-show="outputFormat === 'pdf'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                            </div>
                            <span class="text-base font-bold text-slate-900 block">Unduh sebagai PDF (.pdf)</span>
                            <span class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Dokumen PDF siap cetak dan pengesahan digital, dikonversi langsung melalui LibreOffice headless engine.
                            </span>
                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center text-xs font-semibold text-rose-700">
                                <span class="w-2 h-2 rounded-full bg-rose-600 mr-2"></span>
                                Memerlukan LibreOffice Server Engine
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Ringkasan Isian -->
                <div class="p-5 bg-amber-50/70 border border-amber-200/80 rounded-2xl">
                    <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 text-amber-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="text-xs text-amber-900 space-y-1">
                            <p class="font-bold">Informasi Penyimpanan & Substitusi Template:</p>
                            <p>• Total field pada template: <span class="font-bold">{{ count($fieldMap) }} field</span>.</p>
                            <p>• Field yang Anda isi akan disubstitusikan ke dokumen template asli dengan formatting font, ukuran, dan perataan yang persis sama.</p>
                            <p>• Field yang dibiarkan kosong akan otomatis ditampilkan sebagai tanda <code class="bg-white px-1 py-0.5 rounded border border-amber-300 font-bold">-</code> (bukan placeholder mentah).</p>
                            <p>• File dokumen (<span class="font-bold uppercase" x-text="outputFormat"></span>) akan <strong>otomatis langsung terunduh</strong> ke komputer / perangkat Anda.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Bottom Navigation Bar -->
        <div class="mt-8 bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-slate-200 flex items-center justify-between gap-4 sticky bottom-4 z-30">
            <div>
                <button type="button"
                        x-show="currentStep > 0"
                        @click="prevStep()"
                        class="inline-flex items-center space-x-2 px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 font-bold text-sm hover:bg-slate-50 transition active:scale-95 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    <span>Sebelumnya</span>
                </button>
            </div>

            <div class="flex items-center space-x-3">
                <!-- Stateful Simpan Sesi Button -->
                <button type="button"
                        @click="saveCurrentStepSession(true)"
                        :disabled="isSavingSession"
                        class="inline-flex items-center space-x-2 px-4 sm:px-5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 shadow-sm active:scale-95 cursor-pointer"
                        :class="sessionSavedSuccess 
                            ? 'bg-emerald-500 text-white border-2 border-emerald-600 shadow-emerald-500/25 shadow-md' 
                            : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300'">
                    <template x-if="!isSavingSession && !sessionSavedSuccess">
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    </template>
                    <template x-if="isSavingSession">
                        <svg class="animate-spin w-4 h-4 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    </template>
                    <template x-if="sessionSavedSuccess">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    </template>
                    <span x-text="isSavingSession ? 'Menyimpan Sesi...' : (sessionSavedSuccess ? '✓ Sesi Berhasil Disimpan!' : 'Simpan Sesi')"></span>
                </button>

                <!-- Next Button (Step 0 to totalSteps - 2) -->
                <button type="button"
                        x-show="currentStep < totalSteps - 1"
                        @click="nextStep()"
                        class="inline-flex items-center space-x-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 text-white font-bold text-sm shadow-md hover:from-brand-700 hover:to-indigo-700 transition active:scale-95">
                    <span x-text="currentStep === totalSteps - 2 ? 'Lanjut ke Pilih Format' : 'Seksi Berikutnya'"></span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>

                <!-- Final Submit Button (at final step) -->
                <button type="submit"
                        x-show="currentStep === totalSteps - 1"
                        class="inline-flex items-center space-x-2 px-7 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-extrabold text-sm shadow-lg hover:from-emerald-700 hover:to-teal-700 transition active:scale-95 cursor-pointer">
                    <svg x-show="!isSubmitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    <svg x-show="isSubmitting" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <span x-text="isSubmitting ? 'Memproses & Mengunduh Dokumen...' : 'Generate & Download Dokumen Sekarang'"></span>
                </button>
            </div>
        </div>

        <!-- Floating Notification Toast for Sesi Saved -->
        <div x-show="sessionSavedSuccess"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-4"
             class="fixed bottom-24 right-6 z-50 bg-emerald-600 text-white px-5 py-3 rounded-2xl shadow-xl border border-emerald-500 flex items-center space-x-3">
            <div class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center font-black text-white">
                ✓
            </div>
            <div>
                <h4 class="font-bold text-xs sm:text-sm">Sesi Berhasil Disimpan!</h4>
                <p class="text-[11px] text-emerald-100">Seluruh isian data formulir dan jadwal aman tersimpan di draf sesi.</p>
            </div>
        </div>
    </form>

    <!-- Floating Contextual AI Assistant Widget -->
    <div x-data="aiAssistantWidget()" class="relative z-50">
        <!-- Floating Trigger Button -->
        <div class="fixed bottom-6 right-6 flex items-center space-x-2">
            <button type="button"
                    @click="toggleChat()"
                    class="group relative inline-flex items-center space-x-2.5 px-4 py-3 bg-gradient-to-r from-brand-600 via-indigo-600 to-purple-600 hover:from-brand-700 hover:to-purple-700 text-white rounded-2xl shadow-xl hover:shadow-brand-500/30 transition-all duration-300 transform hover:scale-105 active:scale-95 focus:outline-none focus:ring-4 focus:ring-brand-500/20"
                    title="Tanya Asisten AI Kontekstual">
                <!-- Online Pulse Dot -->
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-300 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-400"></span>
                </span>
                <svg class="w-5 h-5 text-amber-200 group-hover:rotate-12 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-xs sm:text-sm font-bold tracking-tight">Tanya Asisten KAK</span>
            </button>
        </div>

        <!-- Chat Window Modal / Panel -->
        <div x-show="isOpen"
             x-cloak
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-8 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-8 scale-95"
             class="fixed bottom-20 right-4 sm:right-6 w-[calc(100vw-2rem)] sm:w-[420px] h-[560px] max-h-[calc(100vh-6rem)] bg-white rounded-3xl shadow-2xl border border-slate-200/80 flex flex-col overflow-hidden z-50">
            
            <!-- Chat Header -->
            <div class="px-5 py-4 bg-gradient-to-r from-slate-900 via-brand-900 to-indigo-900 text-white flex items-center justify-between shadow-sm">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-400 to-indigo-500 flex items-center justify-center text-white shadow-inner font-bold text-sm">
                        ✨
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-sm font-extrabold leading-none">Asisten AI KAK</h3>
                            <span class="text-[10px] uppercase font-bold tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 px-1.5 py-0.5 rounded-full">Aktif</span>
                        </div>
                        <p class="text-[11px] text-slate-300 mt-1 truncate max-w-[220px]" x-text="'Konteks: ' + getActiveSectionTitle()"></p>
                    </div>
                </div>

                <div class="flex items-center space-x-1">
                    <button type="button"
                            @click="clearMessages()"
                            class="p-1.5 text-slate-300 hover:text-white hover:bg-white/10 rounded-lg transition"
                            title="Hapus Riwayat Chat">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                    <button type="button"
                            @click="isOpen = false"
                            class="p-1.5 text-slate-300 hover:text-white hover:bg-white/10 rounded-lg transition"
                            title="Tutup Widget">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>

            <!-- Context Badge Notice -->
            <div class="bg-brand-50/80 border-b border-brand-100 px-4 py-2 flex items-center justify-between text-[11px] text-brand-800">
                <div class="flex items-center space-x-1.5 truncate">
                    <svg class="w-3.5 h-3.5 text-brand-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="truncate">Jawaban disesuaikan dengan bagian: <strong x-text="getActiveSectionTitle()"></strong></span>
                </div>
            </div>

            <!-- Chat Messages Scroll Container -->
            <div x-ref="messagesContainer" class="flex-1 p-4 overflow-y-auto space-y-3.5 bg-slate-50/50 text-xs">
                <template x-for="(msg, index) in messages" :key="index">
                    <div class="flex" :class="msg.sender === 'user' ? 'justify-end' : 'justify-start'">
                        <div class="flex items-start space-x-2 max-w-[85%]" :class="msg.sender === 'user' ? 'flex-row-reverse space-x-reverse' : 'flex-row'">
                            <!-- Avatar -->
                            <div class="w-6 h-6 rounded-lg flex items-center justify-center text-[11px] flex-shrink-0 mt-0.5"
                                 :class="msg.sender === 'user' ? 'bg-brand-600 text-white' : 'bg-gradient-to-br from-indigo-500 to-purple-600 text-white'">
                                <span x-text="msg.sender === 'user' ? 'Anda' : 'AI'"></span>
                            </div>

                            <!-- Bubble Message -->
                            <div class="p-3 rounded-2xl shadow-xs leading-relaxed"
                                 :class="msg.sender === 'user' 
                                     ? 'bg-brand-600 text-white rounded-tr-xs' 
                                     : (msg.isError 
                                         ? 'bg-rose-50 text-rose-800 border border-rose-200 rounded-tl-xs' 
                                         : 'bg-white text-slate-800 border border-slate-200/80 rounded-tl-xs')">
                                <div class="prose prose-xs max-w-none break-words" x-html="renderMessage(msg.text)"></div>
                                <div class="text-[9px] mt-1 text-right"
                                     :class="msg.sender === 'user' ? 'text-brand-200' : 'text-slate-400'"
                                     x-text="msg.time"></div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Typing Indicator Bubble -->
                <div x-show="isLoading" class="flex justify-start">
                    <div class="flex items-start space-x-2">
                        <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center text-[10px]">
                            AI
                        </div>
                        <div class="bg-white border border-slate-200 px-3.5 py-2.5 rounded-2xl rounded-tl-xs shadow-xs flex items-center space-x-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-pulse [animation-delay:0.2s]"></span>
                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse [animation-delay:0.4s]"></span>
                            <span class="text-[11px] text-slate-500 ml-1">Asisten sedang menganalisis dokumen...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Suggestions Chips -->
            <div class="px-3 py-2 bg-white border-t border-slate-100 flex items-center space-x-1.5 overflow-x-auto scrollbar-none text-[11px]">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex-shrink-0">Coba:</span>
                <button type="button" @click="askQuick('Apa itu GAP dalam KAK?')" class="px-2.5 py-1 bg-slate-100 hover:bg-brand-50 hover:text-brand-700 text-slate-600 rounded-lg whitespace-nowrap transition border border-slate-200/60">
                    💡 Apa itu GAP?
                </button>
                <button type="button" @click="askQuick('Bagaimana aturan kode RO dan KRO?')" class="px-2.5 py-1 bg-slate-100 hover:bg-brand-50 hover:text-brand-700 text-slate-600 rounded-lg whitespace-nowrap transition border border-slate-200/60">
                    📋 Kode RO / KRO
                </button>
                <button type="button" @click="askQuick('Bagaimana format pengisian RAB dan SBM?')" class="px-2.5 py-1 bg-slate-100 hover:bg-brand-50 hover:text-brand-700 text-slate-600 rounded-lg whitespace-nowrap transition border border-slate-200/60">
                    💰 Format RAB
                </button>
            </div>

            <!-- Chat Input Form -->
            <form @submit.prevent="submitQuestion()" class="p-3 bg-white border-t border-slate-200/80 flex items-center space-x-2">
                <input type="text"
                       x-model="inputQuestion"
                       :disabled="isLoading"
                       placeholder="Tanyakan istilah atau panduan isian step ini..."
                       class="flex-1 bg-slate-100 border border-slate-200 focus:bg-white focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-xs text-slate-800 rounded-xl px-3.5 py-2.5 transition outline-none disabled:opacity-60">

                <button type="submit"
                        :disabled="isLoading || !inputQuestion.trim()"
                        class="px-4 py-2.5 bg-brand-600 hover:bg-brand-700 disabled:bg-slate-300 text-white rounded-xl font-bold text-xs shadow-sm transition flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 transform rotate-90" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path></svg>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@php
    $defaultTabelWaktu = $draftData['tabel_waktu'] ?? [
        'rak_title' => $draftData['field_020'] ?? 'Rekomendasi Alternatif Kebijakan',
        'subkomponen_1' => 'Koordinasi dan Pelaksanaan Kebijakan',
        'kegiatan_1' => [1, 2, 3],
        'kegiatan_2' => [2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        'kegiatan_3' => [4, 7, 10],
        'kegiatan_4' => [11, 12],
        'has_subkomponen_2' => false,
        'subkomponen_2' => '',
        'sub2_kegiatan_1' => [],
        'sub2_kegiatan_2' => [],
        'sub2_kegiatan_3' => [],
        'sub2_kegiatan_4' => [],
    ];
@endphp

@push('scripts')
<script>
function kakWizard() {
    const initialTabelWaktu = @json($defaultTabelWaktu);
    const initialFormData = @json($draftData);
    if (!initialFormData.tabel_waktu) {
        initialFormData.tabel_waktu = initialTabelWaktu;
    }

    return {
        currentStep: 0,
        totalSteps: {{ count($sections) + 1 }},
        stepTitles: [
            @foreach($sections as $sName => $f)
                "{{ $sName }}",
            @endforeach
            "Pilih Format & Generate"
        ],
        outputFormat: 'docx',
        judulDoc: '{{ addslashes($draftJudul) }}',
        formData: initialFormData,
        autoSavedMessage: '',
        isSubmitting: false,
        isSavingSession: false,
        sessionSavedSuccess: false,

        init() {
            // Restore from localStorage backup if present and current data is empty
            try {
                const localBackup = localStorage.getItem('kak_draft_backup');
                if (localBackup) {
                    const parsed = JSON.parse(localBackup);
                    if (parsed && parsed.data) {
                        for (let k in parsed.data) {
                            if ((!this.formData[k] || this.formData[k] === '') && parsed.data[k]) {
                                this.formData[k] = parsed.data[k];
                            }
                        }
                    }
                    if (!this.judulDoc && parsed.judul) {
                        this.judulDoc = parsed.judul;
                    }
                }
            } catch (e) {
                console.warn('LocalStorage restore note:', e);
            }
        },

        get progressPercentage() {
            return Math.round(((this.currentStep + 1) / this.totalSteps) * 100);
        },

        goToStep(idx) {
            this.saveCurrentStepSession();
            this.currentStep = idx;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        nextStep() {
            this.saveCurrentStepSession();
            if (this.currentStep < this.totalSteps - 1) {
                this.currentStep++;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        prevStep() {
            this.saveCurrentStepSession();
            if (this.currentStep > 0) {
                this.currentStep--;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        handleFormSubmit(e) {
            // Save to localStorage before submit proceeds
            try {
                localStorage.setItem('kak_draft_backup', JSON.stringify({
                    data: this.formData,
                    judul: this.judulDoc
                }));
            } catch (err) {}

            // Delay setting isSubmitting so the browser dispatches native form submit without being aborted
            setTimeout(() => {
                this.isSubmitting = true;
            }, 60);

            return true;
        },

        saveCurrentStepSession(manual = false) {
            // Always persist locally
            try {
                localStorage.setItem('kak_draft_backup', JSON.stringify({
                    data: this.formData,
                    judul: this.judulDoc
                }));
            } catch (e) {}

            if (manual) {
                this.isSavingSession = true;
                this.sessionSavedSuccess = false;
            }

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch("{{ route('submissions.storeStep') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    data: this.formData,
                    judul: this.judulDoc
                })
            })
            .then(res => res.json())
            .then(data => {
                if (manual) {
                    this.isSavingSession = false;
                    this.sessionSavedSuccess = true;
                    this.autoSavedMessage = '✓ Sesi & Draf Tersimpan!';
                    setTimeout(() => { 
                        this.sessionSavedSuccess = false; 
                        this.autoSavedMessage = '';
                    }, 3500);
                }
            })
            .catch(err => {
                console.warn('Gagal menyimpan sesi:', err);
                if (manual) {
                    this.isSavingSession = false;
                    this.sessionSavedSuccess = true; // Still saved in localStorage
                    setTimeout(() => { 
                        this.sessionSavedSuccess = false; 
                    }, 3500);
                }
            });
        },

        useExample(key, placeholder) {
            // Strip leading "Contoh:" or "Contoh kode:"
            let cleanVal = placeholder.replace(/^Contoh(\s+kode)?\s*:\s*/i, '').trim();
            this.formData[key] = cleanVal;
            this.autoSavedMessage = 'Contoh diterapkan untuk ' + key;
            setTimeout(() => { this.autoSavedMessage = ''; }, 2500);
            this.saveCurrentStepSession(false);
        },

        // --- Table Schedule Management Methods ---
        toggleMonth(kegKey, monthNum) {
            if (!this.formData.tabel_waktu) {
                this.formData.tabel_waktu = {};
            }
            if (!Array.isArray(this.formData.tabel_waktu[kegKey])) {
                this.formData.tabel_waktu[kegKey] = [];
            }
            const arr = this.formData.tabel_waktu[kegKey];
            const idx = arr.indexOf(monthNum);
            if (idx > -1) {
                arr.splice(idx, 1);
            } else {
                arr.push(monthNum);
                arr.sort((a, b) => a - b);
            }
            this.saveCurrentStepSession(false);
        },

        isMonthChecked(kegKey, monthNum) {
            if (!this.formData.tabel_waktu || !Array.isArray(this.formData.tabel_waktu[kegKey])) {
                return false;
            }
            return this.formData.tabel_waktu[kegKey].includes(monthNum);
        },

        setQuarter(kegKey, qNum) {
            if (!this.formData.tabel_waktu) {
                this.formData.tabel_waktu = {};
            }
            const qMonths = {
                1: [1, 2, 3],
                2: [4, 5, 6],
                3: [7, 8, 9],
                4: [10, 11, 12]
            }[qNum] || [];
            
            let arr = Array.isArray(this.formData.tabel_waktu[kegKey]) ? [...this.formData.tabel_waktu[kegKey]] : [];
            const hasAll = qMonths.every(m => arr.includes(m));
            if (hasAll) {
                arr = arr.filter(m => !qMonths.includes(m));
            } else {
                qMonths.forEach(m => { if (!arr.includes(m)) arr.push(m); });
            }
            arr.sort((a, b) => a - b);
            this.formData.tabel_waktu[kegKey] = arr;
            this.saveCurrentStepSession(false);
        },

        setAllMonths(kegKey) {
            if (!this.formData.tabel_waktu) {
                this.formData.tabel_waktu = {};
            }
            let arr = Array.isArray(this.formData.tabel_waktu[kegKey]) ? this.formData.tabel_waktu[kegKey] : [];
            if (arr.length === 12) {
                this.formData.tabel_waktu[kegKey] = [];
            } else {
                this.formData.tabel_waktu[kegKey] = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
            }
            this.saveCurrentStepSession(false);
        },

        resetMonths(kegKey) {
            if (!this.formData.tabel_waktu) {
                this.formData.tabel_waktu = {};
            }
            this.formData.tabel_waktu[kegKey] = [];
            this.saveCurrentStepSession(false);
        },

        applyDefaultSchedule() {
            if (!this.formData.tabel_waktu) {
                this.formData.tabel_waktu = {};
            }
            this.formData.tabel_waktu.kegiatan_1 = [1, 2, 3];
            this.formData.tabel_waktu.kegiatan_2 = [2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
            this.formData.tabel_waktu.kegiatan_3 = [4, 7, 10];
            this.formData.tabel_waktu.kegiatan_4 = [11, 12];
            this.autoSavedMessage = 'Jadwal standar KAK diterapkan!';
            setTimeout(() => { this.autoSavedMessage = ''; }, 2500);
            this.saveCurrentStepSession(false);
        },

        clearSchedule() {
            if (!this.formData.tabel_waktu) {
                this.formData.tabel_waktu = {};
            }
            this.formData.tabel_waktu.kegiatan_1 = [];
            this.formData.tabel_waktu.kegiatan_2 = [];
            this.formData.tabel_waktu.kegiatan_3 = [];
            this.formData.tabel_waktu.kegiatan_4 = [];
            this.autoSavedMessage = 'Jadwal dikosongkan!';
            setTimeout(() => { this.autoSavedMessage = ''; }, 2500);
            this.saveCurrentStepSession(false);
        }
    };
}

function aiAssistantWidget() {
    return {
        isOpen: false,
        inputQuestion: '',
        isLoading: false,
        messages: [
            {
                sender: 'assistant',
                text: 'Halo! Saya asisten AI untuk memandu penyusunan dokumen KAK resmi. Anda dapat menanyakan istilah perencanaan (seperti GAP, DIPA, RO/KRO) atau panduan format pengisian untuk step formulir yang sedang Anda buka saat ini.',
                time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                isError: false
            }
        ],

        toggleChat() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.$nextTick(() => this.scrollToBottom());
            }
        },

        getActiveSectionTitle() {
            if (this.stepTitles && typeof this.currentStep !== 'undefined' && this.stepTitles[this.currentStep]) {
                return this.stepTitles[this.currentStep];
            }
            return 'Formulir KAK';
        },

        askQuick(question) {
            this.inputQuestion = question;
            this.submitQuestion();
        },

        submitQuestion() {
            const q = this.inputQuestion.trim();
            if (!q || this.isLoading) return;

            const nowTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            // Add user message
            this.messages.push({
                sender: 'user',
                text: q,
                time: nowTime,
                isError: false
            });

            const activeSection = this.getActiveSectionTitle();
            this.inputQuestion = '';
            this.isLoading = true;
            this.$nextTick(() => this.scrollToBottom());

            fetch("{{ route('kak.assistant.ask') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    question: q,
                    current_section: activeSection
                })
            })
            .then(res => res.json())
            .then(data => {
                this.isLoading = false;
                const ansTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                if (data.status === 'success') {
                    this.messages.push({
                        sender: 'assistant',
                        text: data.answer,
                        time: ansTime,
                        isError: false
                    });
                } else {
                    this.messages.push({
                        sender: 'assistant',
                        text: data.answer || 'Asisten AI sedang tidak tersedia, silakan lanjutkan mengisi formulir.',
                        time: ansTime,
                        isError: true
                    });
                }
                this.$nextTick(() => this.scrollToBottom());
            })
            .catch(err => {
                this.isLoading = false;
                const errTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                this.messages.push({
                    sender: 'assistant',
                    text: 'Asisten AI sedang tidak dapat dijangkau. Jangan khawatir, proses pengisian formulir KAK Anda tetap berjalan normal.',
                    time: errTime,
                    isError: true
                });
                this.$nextTick(() => this.scrollToBottom());
            });
        },

        clearMessages() {
            this.messages = [
                {
                    sender: 'assistant',
                    text: 'Riwayat percakapan telah dibersihkan. Ada yang ingin Anda tanyakan terkait bagian ' + this.getActiveSectionTitle() + '?',
                    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                    isError: false
                }
            ];
        },

        scrollToBottom() {
            if (this.$refs.messagesContainer) {
                this.$refs.messagesContainer.scrollTop = this.$refs.messagesContainer.scrollHeight;
            }
        },

        renderMessage(text) {
            if (!text) return '';
            // Basic markdown converter: bold, line breaks, bullet points
            let escaped = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // Bold **text**
            escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900">$1</strong>');
            
            // Bullet points • or -
            escaped = escaped.replace(/^[•\-]\s+(.*)$/gm, '<div class="flex items-start space-x-1.5 my-0.5"><span class="text-brand-600 font-bold">•</span><span>$1</span></div>');

            // Line breaks
            escaped = escaped.replace(/\n\n/g, '<div class="h-2"></div>');
            escaped = escaped.replace(/\n/g, '<br>');

            return escaped;
        }
    };
}
</script>
@endpush
