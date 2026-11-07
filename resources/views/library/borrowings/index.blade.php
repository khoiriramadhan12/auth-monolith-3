<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                    🔄 {{ __('Sirkulasi Peminjaman Buku') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Domain-Driven Design (Circulation Context) dengan Database Transaction & Value Object Denda</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('library.books.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-700 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-800 transition">
                    📚 Lihat Katalog
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Alert -->
            @if (session('success'))
            <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-lg shadow-sm">
                <p class="text-sm text-emerald-800 font-medium">{{ session('success') }}</p>
            </div>
            @endif

            @if (session('error'))
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm">
                <p class="text-sm text-red-800 font-medium">{{ session('error') }}</p>
            </div>
            @endif

            <!-- Form Peminjaman Baru (Atomic Transaction Demo) -->
            <div style="background: #ffffff; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; margin-bottom: 24px;">
                <h3 style="font-weight: 700; font-size: 18px; color: #1e293b; margin-bottom: 6px;">➕ Buat Transaksi Peminjaman Baru</h3>
                <p style="font-size: 13px; color: #64748b; margin-bottom: 18px;">Uji coba Database Transaction: Jika memilih buku yang stoknya 0, transaksi otomatis dibatalkan (Rollback).</p>

                <form method="POST" action="{{ route('library.borrowings.store') }}">
                    @csrf
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; margin-bottom: 6px;">Pilih Anggota</label>
                            <select name="member_id" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 14px;">
                                @foreach ($members as $member)
                                    <option value="{{ $member->id }}">
                                        {{ $member->member_code }} - {{ $member->name }} ({{ strtoupper($member->status) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; margin-bottom: 6px;">Pilih Buku</label>
                            <select name="book_id" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 14px;">
                                @foreach ($books as $book)
                                    <option value="{{ $book->id }}" style="{{ $book->stock == 0 ? 'color: red; font-weight: bold;' : '' }}">
                                        {{ $book->title }} (Stok: {{ $book->stock }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; margin-bottom: 6px;">Lama Pinjam (Hari)</label>
                            <input type="number" name="borrow_days" value="7" min="1" max="30" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 14px;">
                        </div>
                    </div>

                    <div>
                        <button type="submit" style="display: inline-block; padding: 12px 24px; background-color: #4f46e5; color: #ffffff; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; border-radius: 8px; border: none; cursor: pointer; box-shadow: 0 2px 4px rgba(79, 70, 229, 0.3);">
                            🚀 Proses Peminjaman
                        </button>
                    </div>
                </form>
            </div>

            <!-- Borrowings List Table -->
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100">
                <div class="p-6">
                    <h3 class="font-bold text-lg text-gray-800 mb-4">Riwayat Peminjaman Aktif & Selesai</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="py-3 px-4">Kode Pinjam</th>
                                    <th class="py-3 px-4">Anggota</th>
                                    <th class="py-3 px-4">Buku yang Dipinjam (Snapshot)</th>
                                    <th class="py-3 px-4">Tgl Pinjam / Batas</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                    <th class="py-3 px-4 text-center">Denda (Value Object)</th>
                                    <th class="py-3 px-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm">
                                @forelse ($borrowings as $b)
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 font-mono font-bold text-indigo-600">
                                        {{ $b->borrow_code }}
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-medium text-gray-900">{{ $b->member?->name ?? '—' }}</div>
                                        <div class="text-xs text-gray-500">{{ $b->member?->member_code ?? '' }}</div>
                                    </td>
                                    <td class="py-4 px-4">
                                        @foreach ($b->details as $d)
                                            <div class="text-xs text-gray-800 font-medium">
                                                📖 {{ $d->book_title }} (Qty: {{ $d->quantity }})
                                            </div>
                                            <div class="text-[11px] font-mono text-gray-400">ISBN: {{ $d->book_isbn }}</div>
                                        @endforeach
                                    </td>
                                    <td class="py-4 px-4 text-xs text-gray-600">
                                        <div>Pinjam: {{ $b->borrow_date->format('d M Y') }}</div>
                                        <div class="font-medium text-gray-800">Batas: {{ $b->due_date->format('d M Y') }}</div>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        @if ($b->status === 'borrowed')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                                Sedang Dipinjam
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                Dikembalikan
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="font-semibold {{ $b->total_fine > 0 ? 'text-red-600' : 'text-gray-500' }}">
                                            Rp {{ number_format($b->total_fine, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        @if ($b->status === 'borrowed')
                                            <form method="POST" action="{{ route('library.borrowings.return', $b->id) }}">
                                                @csrf
                                                <button type="submit" style="padding: 6px 14px; background-color: #059669; color: #ffffff; font-weight: 600; font-size: 12px; border-radius: 6px; border: none; cursor: pointer;">
                                                    Kembalikan
                                                </button>
                                            </form>
                                        @else
                                            <span style="font-size: 12px; color: #94a3b8;">Selesai</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-gray-500 text-sm">
                                        Belum ada data transaksi peminjaman.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $borrowings->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
