<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Author;
use Modules\Catalog\Models\Publisher;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Book;
use Modules\Membership\Models\Role;
use Modules\Membership\Models\Member;
use Modules\Circulation\Services\BorrowingService;
use Modules\Shared\ValueObjects\Isbn;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Penerbit (Publishers)
        $gramedia = Publisher::firstOrCreate(
            ['slug' => 'gramedia-pustaka-utama'],
            ['name' => 'Gramedia Pustaka Utama', 'address' => 'Jl. Palmerah Barat, Jakarta', 'phone' => '021-53650110']
        );

        $mizan = Publisher::firstOrCreate(
            ['slug' => 'mizan-publishing'],
            ['name' => 'Mizan Publishing', 'address' => 'Jl. Cinambo No. 135, Bandung', 'phone' => '022-7834310']
        );

        // 2. Seed Penulis (Authors)
        $pramoedya = Author::firstOrCreate(
            ['slug' => 'pramoedya-ananta-toer'],
            ['name' => 'Pramoedya Ananta Toer', 'bio' => 'Sastrawan terkemuka Indonesia']
        );

        $tereliye = Author::firstOrCreate(
            ['slug' => 'tere-liye'],
            ['name' => 'Tere Liye', 'bio' => 'Penulis novel produktif Indonesia']
        );

        $martin = Author::firstOrCreate(
            ['slug' => 'robert-c-martin'],
            ['name' => 'Robert C. Martin (Uncle Bob)', 'bio' => 'Software engineer and author of Clean Code']
        );

        // 3. Seed Kategori & Sub-Kategori (Self-referencing)
        $itCat = Category::firstOrCreate(
            ['slug' => 'teknologi-informasi'],
            ['name' => 'Teknologi Informasi', 'description' => 'Buku-buku seputar komputer dan pemrograman']
        );

        $seCat = Category::firstOrCreate(
            ['slug' => 'rekayasa-perangkat-lunak'],
            ['name' => 'Rekayasa Perangkat Lunak', 'description' => 'Software engineering, architecture, DDD', 'parent_id' => $itCat->id]
        );

        $sastraCat = Category::firstOrCreate(
            ['slug' => 'sastra-novel'],
            ['name' => 'Sastra & Fiksi', 'description' => 'Koleksi novel dan sastra']
        );

        $sejarahNovelCat = Category::firstOrCreate(
            ['slug' => 'novel-sejarah'],
            ['name' => 'Novel Sejarah', 'description' => 'Novel berlatar sejarah', 'parent_id' => $sastraCat->id]
        );

        // 4. Seed Buku (Books) dengan ISBN valid
        $cleanCode = Book::firstOrCreate(
            ['slug' => 'clean-code'],
            [
                'title' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
                'isbn' => (new Isbn('9780132350884'))->getValue(),
                'author_id' => $martin->id,
                'publisher_id' => $gramedia->id,
                'description' => 'Panduan penulisan kode bersih dan maintainable',
                'published_year' => 2008,
                'stock' => 5,
                'shelf_location' => 101,
                'is_available' => true,
            ]
        );
        $cleanCode->categories()->syncWithoutDetaching([
            $itCat->id => ['added_at' => now()],
            $seCat->id => ['added_at' => now()]
        ]);

        $bumiManusia = Book::firstOrCreate(
            ['slug' => 'bumi-manusia'],
            [
                'title' => 'Bumi Manusia',
                'isbn' => (new Isbn('9789799731234'))->getValue(),
                'author_id' => $pramoedya->id,
                'publisher_id' => $gramedia->id,
                'description' => 'Roman bagian pertama dari Tetralogi Buru',
                'published_year' => 1980,
                'stock' => 3,
                'shelf_location' => 202,
                'is_available' => true,
            ]
        );
        $bumiManusia->categories()->syncWithoutDetaching([
            $sastraCat->id => ['added_at' => now()],
            $sejarahNovelCat->id => ['added_at' => now()]
        ]);

        $hujan = Book::firstOrCreate(
            ['slug' => 'hujan'],
            [
                'title' => 'Hujan',
                'isbn' => (new Isbn('9786020324784'))->getValue(),
                'author_id' => $tereliye->id,
                'publisher_id' => $gramedia->id,
                'description' => 'Novel fiksi ilmiah romantis tentang persahabatan dan cinta',
                'published_year' => 2016,
                'stock' => 0, // Sengaja stok 0 untuk pengujian rollback
                'shelf_location' => 203,
                'is_available' => false,
            ]
        );
        $hujan->categories()->syncWithoutDetaching([
            $sastraCat->id => ['added_at' => now()]
        ]);

        // 5. Seed Membership Roles & Member
        $mahasiswaRole = Role::firstOrCreate(
            ['slug' => 'mahasiswa'],
            ['name' => 'Mahasiswa', 'max_borrow_days' => 7, 'max_borrow_books' => 3]
        );

        $dosenRole = Role::firstOrCreate(
            ['slug' => 'dosen'],
            ['name' => 'Dosen / Peneliti', 'max_borrow_days' => 14, 'max_borrow_books' => 5]
        );

        $user1 = User::firstOrCreate(
            ['email' => 'budi@perpus.ac.id'],
            ['name' => 'Budi Santoso', 'password' => Hash::make('password123')]
        );

        $memberBudi = Member::firstOrCreate(
            ['member_code' => 'MBR-2026-001'],
            [
                'user_id' => $user1->id,
                'name' => 'Budi Santoso',
                'email' => 'budi@perpus.ac.id',
                'phone' => '081234567890',
                'address' => 'Asrama Mahasiswa Kampus',
                'status' => 'active',
                'membership_expiry' => now()->addYear()->toDateString(),
            ]
        );
        $memberBudi->roles()->syncWithoutDetaching([$mahasiswaRole->id]);

        $user2 = User::firstOrCreate(
            ['email' => 'siti@perpus.ac.id'],
            ['name' => 'Dr. Siti Aminah', 'password' => Hash::make('password123')]
        );

        $memberSiti = Member::firstOrCreate(
            ['member_code' => 'MBR-2026-002'],
            [
                'user_id' => $user2->id,
                'name' => 'Dr. Siti Aminah',
                'email' => 'siti@perpus.ac.id',
                'phone' => '081298765432',
                'address' => 'Komplek Dosen Blok B',
                'status' => 'active',
                'membership_expiry' => now()->addYear()->toDateString(),
            ]
        );
        $memberSiti->roles()->syncWithoutDetaching([$dosenRole->id]);

        $memberSuspended = Member::firstOrCreate(
            ['member_code' => 'MBR-2026-003'],
            [
                'name' => 'Doni Suspended',
                'email' => 'doni@example.com',
                'phone' => '081211112222',
                'address' => 'Bandung',
                'status' => 'suspended',
                'membership_expiry' => now()->addYear()->toDateString(),
            ]
        );
        $memberSuspended->roles()->syncWithoutDetaching([$mahasiswaRole->id]);

        // 6. Seed contoh transaksi peminjaman via BorrowingService
        /** @var BorrowingService $borrowingService */
        $borrowingService = app(BorrowingService::class);

        // Peminjaman 1: Aktif
        $borrowingService->borrowBooks(
            $memberBudi->id,
            [
                ['book_id' => $cleanCode->id, 'quantity' => 1]
            ],
            7,
            'Peminjaman untuk tugas praktikum'
        );
    }
}
