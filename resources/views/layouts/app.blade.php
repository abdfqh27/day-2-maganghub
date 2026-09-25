<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem Digitalisasi KAK') - Kemenko PMK</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 Free CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            500: '#0284c7',
                            600: '#0369a1',
                            700: '#075985',
                            800: '#0c4a6e',
                            900: '#082f49',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="{ sidebarOpen: false }" class="h-full font-sans text-slate-800 antialiased selection:bg-brand-500 selection:text-white bg-slate-50">

    <!-- Mobile Sidebar Backdrop & Overlay -->
    <div x-show="sidebarOpen" 
         x-cloak 
         class="relative z-50 md:hidden" 
         role="dialog" 
         aria-modal="true">
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-200" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="transition-opacity ease-linear duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>

        <div class="fixed inset-0 flex">
            <div x-show="sidebarOpen" 
                 x-transition:enter="transition ease-in-out duration-300 transform" 
                 x-transition:enter-start="-translate-x-full" 
                 x-transition:enter-end="translate-x-0" 
                 x-transition:leave="transition ease-in-out duration-300 transform" 
                 x-transition:leave-start="translate-x-0" 
                 x-transition:leave-end="-translate-x-full" 
                 class="relative mr-16 flex w-full max-w-xs flex-1">
                
                <div class="flex flex-col flex-1 bg-white border-r border-slate-200 shadow-2xl">
                    <!-- Mobile Brand Header -->
                    <div class="flex items-center justify-between h-16 px-6 border-b border-slate-100">
                        <a href="{{ route('submissions.index') }}" class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-600 to-indigo-700 flex items-center justify-center text-white shadow-sm shadow-brand-500/20">
                                <i class="fa-solid fa-file-shield text-sm"></i>
                            </div>
                            <div>
                                <span class="text-sm font-bold text-slate-900 block leading-tight">Digitalisasi KAK</span>
                                <span class="text-[11px] font-semibold text-slate-400">Kemenko PMK</span>
                            </div>
                        </a>
                        <button type="button" 
                                @click="sidebarOpen = false" 
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition">
                            <i class="fa-solid fa-xmark text-base"></i>
                        </button>
                    </div>

                    <!-- Mobile Navigation Links -->
                    <nav class="flex-1 px-4 py-6 space-y-6 overflow-y-auto">
                        <div>
                            <p class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">Navigasi Utama</p>
                            <div class="space-y-1">
                                <a href="{{ route('submissions.index') }}" 
                                   @click="sidebarOpen = false"
                                   class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('submissions.index') || request()->routeIs('submissions.show') ? 'bg-brand-50 text-brand-700 font-bold border border-brand-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                    <i class="fa-solid fa-folder-open w-5 text-center text-base {{ request()->routeIs('submissions.index') || request()->routeIs('submissions.show') ? 'text-brand-600' : 'text-slate-400' }}"></i>
                                    <span>Riwayat Dokumen KAK</span>
                                </a>

                                <a href="{{ route('submissions.create') }}" 
                                   @click="sidebarOpen = false"
                                   class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('submissions.create') ? 'bg-brand-50 text-brand-700 font-bold border border-brand-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                    <i class="fa-solid fa-file-circle-plus w-5 text-center text-base {{ request()->routeIs('submissions.create') ? 'text-brand-600' : 'text-slate-400' }}"></i>
                                    <span>Formulir KAK Baru</span>
                                </a>
                            </div>
                        </div>

                        <div>
                            <p class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">Pintasan Cerdas</p>
                            <div class="space-y-1">
                                <a href="{{ route('submissions.index') }}#ai-search" 
                                   @click="sidebarOpen = false"
                                   class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition">
                                    <i class="fa-solid fa-brain w-5 text-center text-base text-indigo-500"></i>
                                    <span>Pencarian AI Dokumen</span>
                                </a>
                            </div>
                        </div>
                    </nav>

                    <!-- Mobile Operator Profile Card -->
                    <div class="p-4 border-t border-slate-100 bg-slate-50/60">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-600 font-bold shadow-2xs">
                                <i class="fa-solid fa-user-shield text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-900 truncate">Operator KAK</p>
                                <p class="text-[11px] text-slate-500 truncate">Kemenko PMK</p>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Aktif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Desktop Permanent Left Sidebar -->
    <aside class="hidden md:flex md:w-64 lg:w-72 md:flex-col md:fixed md:inset-y-0 bg-white border-r border-slate-200/80 z-30 shadow-xs">
        <!-- Sidebar Brand Header -->
        <div class="flex items-center h-16 px-6 border-b border-slate-100">
            <a href="{{ route('submissions.index') }}" class="flex items-center space-x-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-600 to-indigo-700 flex items-center justify-center text-white shadow-md shadow-brand-500/20 group-hover:scale-105 transition-transform">
                    <i class="fa-solid fa-file-shield text-base"></i>
                </div>
                <div>
                    <span class="text-base font-extrabold text-slate-900 tracking-tight block leading-tight">Digitalisasi KAK</span>
                    <span class="text-xs font-medium text-slate-400">Kemenko PMK</span>
                </div>
            </a>
        </div>

        <!-- Sidebar Navigation -->
        <nav class="flex-1 px-4 py-6 space-y-6 overflow-y-auto">
            <!-- Group 1: Navigation -->
            <div>
                <p class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">Menu Utama</p>
                <div class="space-y-1">
                    <a href="{{ route('submissions.index') }}" 
                       class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs lg:text-sm font-semibold transition {{ request()->routeIs('submissions.index') || request()->routeIs('submissions.show') ? 'bg-brand-50 text-brand-700 font-bold border border-brand-100 shadow-2xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i class="fa-solid fa-folder-open w-5 text-center text-sm {{ request()->routeIs('submissions.index') || request()->routeIs('submissions.show') ? 'text-brand-600' : 'text-slate-400' }}"></i>
                        <span class="flex-1">Riwayat Dokumen</span>
                    </a>

                    <a href="{{ route('submissions.create') }}" 
                       class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs lg:text-sm font-semibold transition {{ request()->routeIs('submissions.create') ? 'bg-brand-50 text-brand-700 font-bold border border-brand-100 shadow-2xs' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <i class="fa-solid fa-file-circle-plus w-5 text-center text-sm {{ request()->routeIs('submissions.create') ? 'text-brand-600' : 'text-slate-400' }}"></i>
                        <span class="flex-1">Formulir KAK Baru</span>
                    </a>
                </div>
            </div>

            <!-- Group 2: AI Tools -->
            <div>
                <p class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">Fitur Cerdas</p>
                <div class="space-y-1">
                    <a href="{{ route('submissions.index') }}#ai-search" 
                       class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs lg:text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition">
                        <i class="fa-solid fa-brain w-5 text-center text-sm text-indigo-500"></i>
                        <span class="flex-1">Pencarian AI Natural</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">AI</span>
                    </a>
                </div>
            </div>

            <!-- Group 3: Template Specifications -->
            <div class="pt-2">
                <div class="p-3.5 rounded-2xl bg-gradient-to-br from-slate-50 to-brand-50/40 border border-slate-200/80 text-xs space-y-2">
                    <div class="flex items-center space-x-2 text-slate-800 font-bold">
                        <i class="fa-solid fa-circle-check text-brand-600 text-xs"></i>
                        <span>Template Resmi KAK</span>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        Mendukung ekspor langsung ke format resmi Microsoft Word (.docx) & Adobe PDF (.pdf) dengan tata letak matriks jadwal terintegrasi.
                    </p>
                </div>
            </div>
        </nav>

        <!-- Sidebar Footer / Operator Profile -->
        <div class="p-4 border-t border-slate-100 bg-slate-50/70">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-white border border-slate-200/90 flex items-center justify-center text-slate-600 font-bold shadow-2xs">
                    <i class="fa-solid fa-user-shield text-xs"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-slate-900 truncate">Operator KAK</p>
                    <p class="text-[11px] text-slate-400 truncate">Kemenko PMK</p>
                </div>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Online
                </span>
            </div>
        </div>
    </aside>

    <!-- Main Column (Navbar + Content + Footer) -->
    <div class="md:pl-64 lg:pl-72 flex flex-col min-h-screen">
        
        <!-- Top Navbar -->
        <header class="sticky top-0 z-20 h-16 bg-white/85 backdrop-blur-md border-b border-slate-200/80 px-4 sm:px-6 lg:px-8 flex items-center justify-between shadow-2xs">
            <div class="flex items-center space-x-3 sm:space-x-4">
                <!-- Mobile Sidebar Toggle Button -->
                <button type="button" 
                        @click="sidebarOpen = true" 
                        class="md:hidden p-2 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition"
                        title="Buka Menu Navigasi">
                    <i class="fa-solid fa-bars text-base"></i>
                </button>

                <!-- Breadcrumb / Page Title Indicator -->
                <div class="flex items-center space-x-2 text-xs sm:text-sm">
                    <span class="hidden sm:inline text-slate-400 font-medium">Sistem Digitalisasi KAK</span>
                    <i class="hidden sm:inline fa-solid fa-chevron-right text-[10px] text-slate-300"></i>
                    <span class="font-bold text-slate-900">@yield('page_title', 'Dashboard')</span>
                </div>
            </div>

            <!-- Navbar Right Side Quick Controls -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                <!-- Year Badge -->
                <div class="hidden sm:inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-600">
                    <i class="fa-solid fa-calendar-check text-brand-600 text-xs"></i>
                    <span>T.A. 2026</span>
                </div>

                <!-- Formats Supported Pill -->
                <div class="hidden lg:flex items-center space-x-1 text-[11px] font-mono font-bold text-slate-500">
                    <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">DOCX</span>
                    <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200">PDF</span>
                </div>

                <!-- Quick Action Button -->
                <a href="{{ route('submissions.create') }}" 
                   class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs sm:text-sm shadow-sm transition active:scale-95">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span class="hidden sm:inline">Form KAK Baru</span>
                    <span class="sm:hidden">Baru</span>
                </a>
            </div>
        </header>

        <!-- Main Body Content Area -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">
                <!-- Flash Notification Alerts (Clean without emojis, using Font Awesome icons) -->
                @if(session('success'))
                    <div class="mb-6 rounded-2xl bg-emerald-50 border border-emerald-200 p-4 flex items-start space-x-3 shadow-2xs">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-lg mt-0.5 shrink-0"></i>
                        <div class="flex-1 text-sm text-emerald-800 font-medium">
                            {{ session('success') }}
                        </div>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mb-6 rounded-2xl bg-amber-50 border border-amber-200 p-4 flex items-start space-x-3 shadow-2xs">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg mt-0.5 shrink-0"></i>
                        <div class="flex-1 text-sm text-amber-800 font-medium">
                            {{ session('warning') }}
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 rounded-2xl bg-rose-50 border border-rose-200 p-4 flex items-start space-x-3 shadow-2xs">
                        <i class="fa-solid fa-circle-xmark text-rose-600 text-lg mt-0.5 shrink-0"></i>
                        <div class="flex-1 text-sm text-rose-800 font-medium">
                            {{ session('error') }}
                        </div>
                    </div>
                @endif

                @if(session('info'))
                    <div class="mb-6 rounded-2xl bg-blue-50 border border-blue-200 p-4 flex items-start space-x-3 shadow-2xs">
                        <i class="fa-solid fa-circle-info text-blue-600 text-lg mt-0.5 shrink-0"></i>
                        <div class="flex-1 text-sm text-blue-800 font-medium">
                            {{ session('info') }}
                        </div>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

        <!-- Minimalist Clean Footer -->
        <footer class="bg-white border-t border-slate-200/80 py-5 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
                <div class="flex items-center space-x-2">
                    <span class="font-bold text-slate-700">Digitalisasi Kerangka Acuan Kerja (KAK)</span>
                    <span>•</span>
                    <span>Kementerian Koordinator Bidang Pembangunan Manusia dan Kebudayaan RI</span>
                </div>
                <div class="text-slate-400">
                    Standar Master Template DOCX & Ekspor PDF
                </div>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
