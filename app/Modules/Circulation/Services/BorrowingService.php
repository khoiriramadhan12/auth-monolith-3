<?php

namespace Modules\Circulation\Services;

use Modules\Circulation\Models\Borrowing;
use Modules\Circulation\Models\BorrowingDetail;
use Modules\Circulation\Models\Fine;
use Modules\Circulation\Events\BookBorrowed;
use Modules\Shared\Contracts\BookCatalogServiceInterface;
use Modules\Shared\Contracts\MemberCirculationServiceInterface;
use Modules\Shared\Exceptions\InsufficientBookStockException;
use Modules\Shared\Exceptions\MemberNotActiveException;
use Modules\Shared\ValueObjects\Denda;
use Illuminate\Support\Facades\DB;
use Exception;

class BorrowingService
{
    public function __construct(
        private BookCatalogServiceInterface $bookCatalogService,
        private MemberCirculationServiceInterface $memberCirculationService
    ) {}

    /**
     * Memproses peminjaman buku dengan Database Transaction.
     * Rollback otomatis jika stok tidak mencukupi atau validasi gagal.
     */
    public function borrowBooks(int $memberId, array $items, int $borrowDays = 7, ?string $notes = null): Borrowing
    {
        return DB::transaction(function () use ($memberId, $items, $borrowDays, $notes) {
            // 1. Cek validitas anggota melalui contract
            $member = $this->memberCirculationService->getMemberForBorrowing($memberId);
            if (!$member || !$member['is_active']) {
                throw new MemberNotActiveException($member ? $member['member_code'] : (string)$memberId);
            }

            if (empty($items)) {
                throw new Exception("Peminjaman harus memiliki minimal 1 buku.");
            }

            // 2. Buat aggregate root Borrowing
            $borrowing = Borrowing::create([
                'borrow_code' => 'BRW-' . strtoupper(uniqid()),
                'member_id' => $memberId,
                'borrow_date' => now()->toDateString(),
                'due_date' => now()->addDays($borrowDays)->toDateString(),
                'status' => 'borrowed',
                'total_fine' => 0,
                'notes' => $notes,
            ]);

            // 3. Tambahkan item/detail peminjaman dan kurangi stok
            foreach ($items as $item) {
                $bookId = $item['book_id'];
                $quantity = $item['quantity'] ?? 1;

                $book = $this->bookCatalogService->getBookForBorrowing($bookId);
                if (!$book) {
                    throw new Exception("Buku dengan ID {$bookId} tidak ditemukan di katalog.");
                }

                if (!$this->bookCatalogService->isStockAvailable($bookId, $quantity)) {
                    throw new InsufficientBookStockException($book['title'], $book['stock'], $quantity);
                }

                // Simpan snapshot judul buku dan ISBN
                BorrowingDetail::create([
                    'borrowing_id' => $borrowing->id,
                    'book_id' => $book['id'],
                    'book_title' => $book['title'],
                    'book_isbn' => $book['isbn'],
                    'quantity' => $quantity,
                    'status' => 'borrowed',
                ]);

                // Kurangi stok via contract (lockForUpdate)
                $reduced = $this->bookCatalogService->reduceStock($bookId, $quantity);
                if (!$reduced) {
                    throw new InsufficientBookStockException($book['title'], 0, $quantity);
                }
            }

            // 4. Update status anggota jika diperlukan (misal: active borrowing)
            $this->memberCirculationService->updateBorrowingStatus($memberId, 'active');

            // 5. Tembakkan Domain Event BookBorrowed
            event(new BookBorrowed($borrowing->load('details')));

            return $borrowing->load('details');
        });
    }

    /**
     * Memproses pengembalian buku dan kalkulasi denda otomatis menggunakan Value Object Denda.
     */
    public function returnBooks(int $borrowingId, ?string $returnDate = null): Borrowing
    {
        return DB::transaction(function () use ($borrowingId, $returnDate) {
            $borrowing = Borrowing::with('details')->lockForUpdate()->findOrFail($borrowingId);

            if ($borrowing->status === 'returned') {
                throw new Exception("Peminjaman ini sudah dikembalikan sebelumnya.");
            }

            $actualReturnDate = $returnDate ?: now()->toDateString();
            $dendaVo = Denda::calculate($borrowing->due_date, $actualReturnDate, 1000.0);

            $borrowing->update([
                'status' => 'returned',
                'return_date' => $actualReturnDate,
                'total_fine' => $dendaVo->getAmount(),
            ]);

            // Kembalikan stok buku melalui contract
            foreach ($borrowing->details as $detail) {
                $detail->update([
                    'status' => 'returned',
                    'returned_at' => $actualReturnDate,
                ]);

                $this->bookCatalogService->restoreStock($detail->book_id, $detail->quantity);
            }

            // Jika ada denda, catat ke tabel fines
            if ($dendaVo->getAmount() > 0) {
                Fine::create([
                    'borrowing_id' => $borrowing->id,
                    'member_id' => $borrowing->member_id,
                    'days_late' => $dendaVo->getDaysLate(),
                    'fine_amount' => $dendaVo->getAmount(),
                    'payment_status' => 'unpaid',
                ]);
            }

            return $borrowing->fresh(['details', 'fines']);
        });
    }
}
