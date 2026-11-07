<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                    📚 {{ __('Katalog Buku Perpustakaan') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Domain-Driven Design (Catalog Context) dengan Eager Loading & Query Scopes</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('library.borrowings.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                    🔄 Sirkulasi & Peminjaman
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Message -->
            @if (session('success'))
            <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-lg shadow-sm">
                <p class="text-sm text-emerald-800 font-medium">{{ session('success') }}</p>
            </div>
            @endif

            <!-- Filter & Search Bar -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <form method="GET" action="{{ route('library.books.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Cari Judul / ISBN / Penulis</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Contoh: Clean Code, 9780132350884, Pramoedya..." 
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Filter Kategori</label>
                        <select name="category" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->parent_id ? '— ' : '' }}{{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <div class="flex items-center h-10">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="available" value="1" {{ request('available') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ms-2 text-xs font-medium text-gray-700">Tersedia Saja</span>
                            </label>
                        </div>
                        <button type="submit" class="ms-auto px-4 py-2 bg-gray-800 text-white text-xs font-semibold rounded-lg uppercase tracking-wider hover:bg-gray-700 transition">
                            Filter
                        </button>
                        <a href="{{ route('library.books.index') }}" class="px-3 py-2 bg-gray-100 text-gray-600 text-xs font-semibold rounded-lg hover:bg-gray-200 transition">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Books Table -->
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="py-3 px-4">Buku & Penulis</th>
                                    <th class="py-3 px-4">ISBN (Value Object)</th>
                                    <th class="py-3 px-4">Kategori (Pivot)</th>
                                    <th class="py-3 px-4">Penerbit</th>
                                    <th class="py-3 px-4 text-center">Stok</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm">
                                @forelse ($books as $book)
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4">
                                        <div class="font-bold text-gray-900">{{ $book->title }}</div>
                                        <div class="text-xs text-indigo-600 mt-0.5">Penulis: {{ $book->author?->name ?? '—' }}</div>
                                        <div class="text-xs text-gray-400 mt-1 line-clamp-1">{{ $book->description }}</div>
                                    </td>
                                    <td class="py-4 px-4 font-mono text-xs text-gray-700">
                                        <span class="bg-gray-100 px-2 py-1 rounded">{{ $book->isbn }}</span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex flex-wrap gap-1">
                                            @forelse ($book->categories as $category)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                                    {{ $category->name }}
                                                </span>
                                            @empty
                                                <span class="text-xs text-gray-400">—</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-xs text-gray-600">
                                        {{ $book->publisher?->name ?? '—' }} ({{ $book->published_year ?? '—' }})
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="font-bold {{ $book->stock > 0 ? 'text-gray-900' : 'text-red-500' }}">
                                            {{ $book->stock }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        @if ($book->stock > 0 && $book->is_available)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                Tersedia
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                                Habis / Kosong
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-gray-500 text-sm">
                                        Tidak ada buku yang cocok dengan pencarian Anda.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $books->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
