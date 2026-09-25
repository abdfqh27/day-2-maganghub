@extends('layouts.app')

@section('title', 'Riwayat Pengajuan Dokumen KAK')

@section('content')
<div x-data="aiHistorySearch()" class="max-w-6xl mx-auto space-y-6">

    <!-- Page Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 sm:p-8 rounded-3xl shadow-sm border border-slate-200">
        <div>
            <div class="inline-flex items-center space-x-2 text-xs font-semibold uppercase tracking-wider text-brand-600 bg-brand-50 px-3 py-1 rounded-full mb-2 border border-brand-100">
                <span>Arsip Digital</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Daftar Dokumen KAK</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola dan unduh riwayat Kerangka Acuan Kegiatan yang telah disubstitusikan ke template resmi.</p>
        </div>

        <div>
            <a href="{{ route('submissions.create') }}" 
               class="inline-flex items-center space-x-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-brand-600 to-indigo-600 text-white font-bold text-sm shadow-md hover:from-brand-700 hover:to-indigo-700 transition active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>+ Buat Dokumen KAK Baru</span>
            </a>
        </div>
    </div>

    <!-- AI Natural Language Search Card -->
    <div class="bg-gradient-to-br from-slate-900 via-brand-950 to-indigo-950 text-white p-6 sm:p-7 rounded-3xl shadow-md border border-slate-800">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-brand-400 to-indigo-500 flex items-center justify-center text-white shadow-inner font-bold text-xs">
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-white leading-tight">Pencarian Arsip Cerdas (AI Query)</h2>
                    <p class="text-xs text-slate-300">Tanyakan data submission dalam bahasa sehari-hari tanpa perlu filter manual yang rumit.</p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <span class="text-[11px] font-semibold text-brand-300 bg-brand-900/60 border border-brand-700/50 px-2.5 py-1 rounded-full flex items-center space-x-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Hybrid Deterministic AI</span>
                </span>
            </div>
        </div>

        <!-- Search Bar Form -->
        <form @submit.prevent="executeSearch()" class="relative flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text"
                       x-model="searchQuery"
                       :disabled="isLoading"
                       placeholder="Tanya dalam bahasa natural, misal: KAK draft bulan ini, KAK final tahun 2026..."
                       class="w-full pl-11 pr-10 py-3.5 bg-white/10 hover:bg-white/15 focus:bg-white text-white focus:text-slate-900 placeholder:text-slate-400 rounded-2xl border border-white/15 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/20 text-sm outline-none transition duration-200">
                
                <button type="button"
                        x-show="searchQuery"
                        x-cloak
                        @click="resetSearch()"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-white"
                        title="Kosongkan">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit"
                        :disabled="isLoading || !searchQuery.trim()"
                        class="w-full sm:w-auto px-6 py-3.5 bg-gradient-to-r from-brand-500 to-indigo-600 hover:from-brand-600 hover:to-indigo-700 disabled:opacity-50 text-white font-bold text-sm rounded-2xl shadow-lg transition active:scale-95 flex items-center justify-center space-x-2">
                    <template x-if="!isLoading">
                        <span class="flex items-center space-x-1.5">
                            <span>Cari dengan AI</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </span>
                    </template>
                    <template x-if="isLoading">
                        <span class="flex items-center space-x-2">
                            <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>Menganalisis...</span>
                        </span>
                    </template>
                </button>

                <button type="button"
                        x-show="isSearchActive"
                        x-cloak
                        @click="resetSearch()"
                        class="px-4 py-3.5 bg-white/10 hover:bg-white/20 text-slate-200 hover:text-white rounded-2xl text-xs font-semibold transition flex items-center space-x-1">
                    <span>Reset</span>
                </button>
            </div>
        </form>

        <!-- Quick Search Suggestion Pills -->
        <div class="mt-3 flex items-center space-x-2 overflow-x-auto scrollbar-none pb-1 text-xs">
            <span class="text-slate-400 font-medium flex-shrink-0">Contoh pertanyaan:</span>
            <button type="button" @click="quickSearch('carikan saya data di bulan ini')" class="px-3 py-1 bg-white/10 hover:bg-white/20 text-slate-200 rounded-lg whitespace-nowrap transition border border-white/5">
                📅 Data di bulan ini
            </button>
            <button type="button" @click="quickSearch('Dokumen final tahun 2026')" class="px-3 py-1 bg-white/10 hover:bg-white/20 text-slate-200 rounded-lg whitespace-nowrap transition border border-white/5">
                ✅ Final tahun 2026
            </button>
            <button type="button" @click="quickSearch('Anggaran di atas 500 juta')" class="px-3 py-1 bg-white/10 hover:bg-white/20 text-slate-200 rounded-lg whitespace-nowrap transition border border-white/5">
                💰 Anggaran di atas 500 juta
            </button>
            <button type="button" @click="quickSearch('Dokumen tentang anak')" class="px-3 py-1 bg-white/10 hover:bg-white/20 text-slate-200 rounded-lg whitespace-nowrap transition border border-white/5">
                👶 Tentang anak
            </button>
        </div>

        <!-- AI Filter Chips & Interpretation Banner -->
        <div x-show="isSearchActive" x-cloak class="mt-4 pt-4 border-t border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div class="flex items-center flex-wrap gap-2">
                <span class="font-bold text-amber-300 flex items-center space-x-1">
                    <span>Filter yang dipahami AI:</span>
                </span>

                <template x-for="(chip, idx) in activeChips" :key="idx">
                    <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-brand-500/30 text-brand-200 border border-brand-400/40">
                        <span x-text="chip.label"></span>
                    </span>
                </template>

                <template x-if="activeChips.length === 0">
                    <span class="text-slate-400 italic">Mencocokkan kata kunci teks dokumen</span>
                </template>
            </div>

            <div class="flex items-center space-x-2">
                <span class="text-emerald-400 font-bold" x-text="totalFound + ' dokumen ditemukan'"></span>
                <button type="button" @click="resetSearch()" class="text-slate-300 hover:text-white underline text-[11px]">Tampilkan Semua</button>
            </div>
        </div>
    </div>

    <!-- Table of Submissions -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        
        <!-- Standard Server-Rendered Submissions (Visible when AI search is inactive) -->
        <div x-show="!isSearchActive">
            @if($submissions->isEmpty())
                <div class="p-12 text-center space-y-4">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Belum Ada Dokumen KAK</h3>
                        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Silakan mulai dengan mengisi formulir wizard KAK pertama Anda. Data akan otomatis disubstitusikan ke template resmi.</p>
                    </div>
                    <div class="pt-2">
                        <a href="{{ route('submissions.create') }}" class="inline-flex items-center space-x-2 px-5 py-2.5 rounded-xl bg-brand-600 text-white font-bold text-xs shadow hover:bg-brand-700 transition">
                            <span>Mulai Isi KAK Sekarang</span>
                        </a>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                                <th class="py-3.5 px-6">ID</th>
                                <th class="py-3.5 px-6">Judul KAK</th>
                                <th class="py-3.5 px-6">Status & Anggaran</th>
                                <th class="py-3.5 px-6">Format</th>
                                <th class="py-3.5 px-6">Tanggal Dibuat</th>
                                <th class="py-3.5 px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach($submissions as $sub)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-4 px-6 font-mono text-xs text-slate-400">
                                        #{{ $sub->id }}
                                    </td>
                                    <td class="py-4 px-6 font-bold text-slate-900">
                                        <a href="{{ route('submissions.show', $sub) }}" class="hover:text-brand-600 transition block">
                                            {{ $sub->display_judul }}
                                        </a>
                                        <span class="text-[11px] font-normal text-slate-400 block mt-0.5">
                                            {{ count($sub->data ?? []) }} field terisi
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="space-y-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $sub->status === 'final' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                                {{ ucfirst($sub->status) }}
                                            </span>
                                            @if($sub->total_anggaran)
                                                <div class="text-xs font-mono font-medium text-slate-600">
                                                    Rp {{ number_format($sub->total_anggaran, 0, ',', '.') }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        @if($sub->output_format === 'pdf')
                                            <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-md text-[11px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                <span>PDF</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-md text-[11px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                <span>DOCX</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-xs text-slate-500">
                                        {{ $sub->created_at->translatedFormat('d M Y, H:i') }}
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="inline-flex items-center space-x-2">
                                            <a href="{{ route('submissions.show', $sub) }}" 
                                               class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                                                Detail
                                            </a>

                                            <a href="{{ route('submissions.download', $sub) }}" 
                                               class="px-3 py-1.5 rounded-lg text-xs font-bold text-white {{ $sub->output_format === 'pdf' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-brand-600 hover:bg-brand-700' }} transition shadow-xs">
                                                Unduh
                                            </a>

                                            <form action="{{ route('submissions.destroy', $sub) }}" method="POST" onsubmit="return confirm('Hapus submission ini? File yang sudah digenerate juga akan dihapus.');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Hapus">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($submissions->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100">
                        {{ $submissions->links() }}
                    </div>
                @endif
            @endif
        </div>

        <!-- Dynamic AI Query Results (Visible when AI search is active) -->
        <div x-show="isSearchActive" x-cloak>
            <template x-if="searchResults.length === 0">
                <div class="p-12 text-center space-y-4">
                    <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-500 mx-auto flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Tidak Ada Dokumen KAK yang Cocok</h3>
                        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto" x-text="'Tidak ditemukan pengajuan KAK dengan kriteria: ' + (filterSummary || searchQuery)"></p>
                    </div>
                    <div class="pt-2">
                        <button type="button" @click="resetSearch()" class="inline-flex items-center space-x-2 px-5 py-2.5 rounded-xl bg-slate-800 text-white font-bold text-xs shadow hover:bg-slate-900 transition">
                            <span>Kembalikan ke Semua Dokumen</span>
                        </button>
                    </div>
                </div>
            </template>

            <template x-if="searchResults.length > 0">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                                <th class="py-3.5 px-6">ID</th>
                                <th class="py-3.5 px-6">Judul KAK</th>
                                <th class="py-3.5 px-6">Status & Anggaran</th>
                                <th class="py-3.5 px-6">Format</th>
                                <th class="py-3.5 px-6">Tanggal Dibuat</th>
                                <th class="py-3.5 px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <template x-for="item in searchResults" :key="item.id">
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-4 px-6 font-mono text-xs text-slate-400" x-text="'#' + item.id"></td>
                                    <td class="py-4 px-6 font-bold text-slate-900">
                                        <a :href="item.detail_url" class="hover:text-brand-600 transition block" x-text="item.judul"></a>
                                        <span class="text-[11px] font-normal text-slate-400 block mt-0.5" x-text="item.total_fields + ' field terisi'"></span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="space-y-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold"
                                                  :class="item.status === 'final' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                                                  x-text="item.status.toUpperCase()"></span>
                                            <template x-if="item.total_anggaran">
                                                <div class="text-xs font-mono font-medium text-slate-600" x-text="item.total_anggaran"></div>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <template x-if="item.output_format === 'pdf'">
                                            <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-md text-[11px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                <span>PDF</span>
                                            </span>
                                        </template>
                                        <template x-if="item.output_format !== 'pdf'">
                                            <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-md text-[11px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                <span>DOCX</span>
                                            </span>
                                        </template>
                                    </td>
                                    <td class="py-4 px-6 text-xs text-slate-500" x-text="item.created_at_human"></td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="inline-flex items-center space-x-2">
                                            <a :href="item.detail_url" class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                                                Detail
                                            </a>
                                            <a :href="item.download_url" class="px-3 py-1.5 rounded-lg text-xs font-bold text-white transition shadow-xs"
                                               :class="item.output_format === 'pdf' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-brand-600 hover:bg-brand-700'">
                                                Unduh
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function aiHistorySearch() {
    return {
        searchQuery: '',
        isLoading: false,
        isSearchActive: false,
        searchResults: [],
        activeChips: [],
        filterSummary: '',
        totalFound: 0,

        quickSearch(q) {
            this.searchQuery = q;
            this.executeSearch();
        },

        executeSearch() {
            const q = this.searchQuery.trim();
            if (!q || this.isLoading) return;

            this.isLoading = true;

            fetch("{{ route('kak.history.aiSearch') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ question: q })
            })
            .then(res => res.json())
            .then(data => {
                this.isLoading = false;
                if (data.status === 'success') {
                    this.isSearchActive = true;
                    this.searchResults = data.submissions || [];
                    this.activeChips = data.chips || [];
                    this.filterSummary = data.filter_summary || '';
                    this.totalFound = data.count || 0;
                } else {
                    alert(data.message || 'Pencarian AI tidak dapat diproses.');
                }
            })
            .catch(err => {
                this.isLoading = false;
                alert('Terjadi kesalahan koneksi saat memproses pencarian AI.');
            });
        },

        resetSearch() {
            this.searchQuery = '';
            this.isSearchActive = false;
            this.searchResults = [];
            this.activeChips = [];
            this.filterSummary = '';
            this.totalFound = 0;
        }
    };
}
</script>
@endpush
