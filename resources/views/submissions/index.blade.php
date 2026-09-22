@extends('layouts.app')

@section('title', 'Riwayat Pengajuan Dokumen KAK')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

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

    <!-- Table of Submissions -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
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
                            <th class="py-3.5 px-6">Format Terakhir</th>
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
</div>
@endsection
