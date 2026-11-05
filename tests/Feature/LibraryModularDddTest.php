<?php

namespace Tests\Feature;

use Tests\TestCase;
use Modules\Catalog\Models\Book;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Author;
use Modules\Catalog\Models\Publisher;
use Modules\Membership\Models\Member;
use Modules\Membership\Models\Role;
use Modules\Circulation\Models\Borrowing;
use Modules\Circulation\Services\BorrowingService;
use Modules\Circulation\Events\BookBorrowed;
use Modules\Shared\ValueObjects\Isbn;
use Modules\Shared\ValueObjects\Denda;
use Modules\Shared\Exceptions\InsufficientBookStockException;
use Modules\Shared\Exceptions\MemberNotActiveException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LibraryModularDddTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\LibrarySeeder::class);
    }

    /** @test */
    public function test_value_object_isbn_valid_and_invalid()
    {
        $validIsbn13 = new Isbn('978-0-13-235088-4');
        $this->assertEquals('9780132350884', $validIsbn13->getValue());

        $validIsbn10 = new Isbn('0-306-40615-2');
        $this->assertEquals('0306406152', $validIsbn10->getValue());

        $this->expectException(\InvalidArgumentException::class);
        new Isbn('1234567890123'); // Invalid checksum
    }

    /** @test */
    public function test_value_object_denda_calculation()
    {
        $denda = Denda::calculate('2026-11-01', '2026-11-05', 1000);
        $this->assertEquals(4, $denda->getDaysLate());
        $this->assertEquals(4000, $denda->getAmount());

        $onTime = Denda::calculate('2026-11-05', '2026-11-03', 1000);
        $this->assertEquals(0, $onTime->getAmount());
    }

    /** @test */
    public function test_advanced_relationships_pivot_and_self_referencing()
    {
        // 1. Many-to-Many pivot with added_at
        $book = Book::with('categories')->where('slug', 'clean-code')->first();
        $this->assertNotEmpty($book->categories);
        $firstCat = $book->categories->first();
        $this->assertNotNull($firstCat->pivot->added_at);

        // 2. Self-referencing category
        $parentCat = Category::where('slug', 'teknologi-informasi')->first();
        $this->assertGreaterThan(0, $parentCat->children->count());
        $subCat = $parentCat->children->first();
        $this->assertEquals($parentCat->id, $subCat->parent->id);

        // 3. HasMany borrowing details
        $borrowing = Borrowing::with('details')->first();
        $this->assertNotNull($borrowing);
        $this->assertGreaterThan(0, $borrowing->details->count());
    }

    /** @test */
    public function test_eager_loading_query_optimization()
    {
        // Tanpa eager loading (Simulasi N+1)
        DB::flushQueryLog();
        DB::enableQueryLog();
        $booksLazy = Book::all();
        foreach ($booksLazy as $b) {
            $authorName = $b->author?->name;
            $catsCount = $b->categories->count();
        }
        $lazyCount = count(DB::getQueryLog());

        // Dengan eager loading
        DB::flushQueryLog();
        DB::enableQueryLog();
        $booksEager = Book::with(['author', 'publisher', 'categories'])->get();
        foreach ($booksEager as $b) {
            $authorName = $b->author?->name;
            $catsCount = $b->categories->count();
        }
        $eagerCount = count(DB::getQueryLog());

        // Query dengan eager loading harus jauh lebih sedikit dan fixed (konstan)
        $this->assertLessThan($lazyCount, $eagerCount);
    }

    /** @test */
    public function test_query_scopes_available_and_search()
    {
        $availableBooks = Book::available()->get();
        foreach ($availableBooks as $b) {
            $this->assertGreaterThan(0, $b->stock);
            $this->assertTrue($b->is_available);
        }

        $searchResults = Book::search('Clean Code')->get();
        $this->assertTrue($searchResults->contains('slug', 'clean-code'));

        $categoryResults = Book::category('teknologi-informasi')->get();
        $this->assertTrue($categoryResults->contains('slug', 'clean-code'));
    }

    /** @test */
    public function test_database_transaction_successful_borrowing()
    {
        Event::fake([BookBorrowed::class]);

        $service = app(BorrowingService::class);
        $member = Member::where('status', 'active')->first();
        $book = Book::where('slug', 'bumi-manusia')->first();
        $initialStock = $book->stock;

        $borrowing = $service->borrowBooks($member->id, [
            ['book_id' => $book->id, 'quantity' => 1]
        ], 7, 'Unit testing transaction');

        $this->assertDatabaseHas('borrowings', ['id' => $borrowing->id, 'status' => 'borrowed']);
        $this->assertDatabaseHas('borrowing_details', ['borrowing_id' => $borrowing->id, 'book_title' => $book->title]);
        
        // Cek snapshot data buku tersimpan
        $detail = $borrowing->details()->first();
        $this->assertEquals($book->title, $detail->book_title);
        $this->assertEquals($book->isbn, $detail->book_isbn);

        // Cek stok berkurang
        $this->assertEquals($initialStock - 1, $book->fresh()->stock);

        // Cek Domain Event ditembakkan
        Event::assertDispatched(BookBorrowed::class, function ($event) use ($borrowing) {
            return $event->borrowing->id === $borrowing->id;
        });
    }

    /** @test */
    public function test_database_transaction_rollback_when_insufficient_stock()
    {
        $service = app(BorrowingService::class);
        $member = Member::where('status', 'active')->first();
        $outOfStockBook = Book::where('slug', 'hujan')->first(); // stock 0

        $initialBorrowingsCount = Borrowing::count();

        try {
            $service->borrowBooks($member->id, [
                ['book_id' => $outOfStockBook->id, 'quantity' => 1]
            ]);
            $this->fail('Harus throw InsufficientBookStockException');
        } catch (InsufficientBookStockException $e) {
            // Berhasil tertangkap
            $this->assertStringContainsString('tidak mencukupi', $e->getMessage());
        }

        // Pastikan tidak ada data yang masuk ke database (Rollback berhasil)
        $this->assertEquals($initialBorrowingsCount, Borrowing::count());
    }

    /** @test */
    public function test_soft_deletes_and_restore()
    {
        $book = Book::where('slug', 'clean-code')->first();
        $bookId = $book->id;

        $book->delete();

        // Tidak muncul pada query biasa
        $this->assertNull(Book::find($bookId));
        // Muncul pada withTrashed
        $this->assertNotNull(Book::withTrashed()->find($bookId));

        // Restore
        $bookTrashed = Book::withTrashed()->find($bookId);
        $bookTrashed->restore();

        // Muncul kembali pada query biasa
        $this->assertNotNull(Book::find($bookId));
    }
}
