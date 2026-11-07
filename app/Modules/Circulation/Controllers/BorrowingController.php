<?php

namespace Modules\Circulation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Circulation\Models\Borrowing;
use Modules\Circulation\Services\BorrowingService;
use Exception;

class BorrowingController extends Controller
{
    public function __construct(
        private BorrowingService $borrowingService
    ) {}

    public function index(Request $request)
    {
        // Eager loading detail and fines and member to prevent N+1
        $query = Borrowing::with(['member', 'details', 'fines']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $borrowings = $query->paginate(15);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $borrowings
            ]);
        }

        $members = \Modules\Membership\Models\Member::all();
        $books = \Modules\Catalog\Models\Book::all();

        return view('library.borrowings.index', [
            'borrowings' => $borrowings,
            'members' => $members,
            'books' => $books
        ]);
    }

    public function show(int $id)
    {
        $borrowing = Borrowing::with(['member', 'details', 'fines'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $borrowing
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|integer',
            'book_id' => 'nullable|integer',
            'items' => 'nullable|array',
            'borrow_days' => 'nullable|integer|min:1|max:30',
            'notes' => 'nullable|string',
        ]);

        $items = $validated['items'] ?? [];
        if (empty($items) && !empty($validated['book_id'])) {
            $items = [['book_id' => $validated['book_id'], 'quantity' => 1]];
        }

        try {
            $borrowing = $this->borrowingService->borrowBooks(
                $validated['member_id'],
                $items,
                $validated['borrow_days'] ?? 7,
                $validated['notes'] ?? null
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Peminjaman buku berhasil diproses.',
                    'data' => $borrowing
                ], 201);
            }

            return redirect()->route('library.borrowings.index')->with('success', "Peminjaman dengan kode {$borrowing->borrow_code} berhasil dibuat!");
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function returnBooks(int $id, Request $request)
    {
        $validated = $request->validate([
            'return_date' => 'nullable|date',
        ]);

        try {
            $borrowing = $this->borrowingService->returnBooks($id, $validated['return_date'] ?? null);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Buku berhasil dikembalikan.',
                    'data' => $borrowing
                ]);
            }

            return redirect()->route('library.borrowings.index')->with('success', "Buku berhasil dikembalikan.");
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function restore(int $id)
    {
        $borrowing = Borrowing::withTrashed()->findOrFail($id);
        $borrowing->restore();

        return response()->json([
            'status' => 'success',
            'message' => "Peminjaman '{$borrowing->borrow_code}' berhasil direstore.",
            'data' => $borrowing
        ]);
    }
}
