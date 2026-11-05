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

        return response()->json([
            'status' => 'success',
            'data' => $borrowings
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
            'items' => 'required|array|min:1',
            'items.*.book_id' => 'required|integer',
            'items.*.quantity' => 'nullable|integer|min:1',
            'borrow_days' => 'nullable|integer|min:1|max:30',
            'notes' => 'nullable|string',
        ]);

        try {
            $borrowing = $this->borrowingService->borrowBooks(
                $validated['member_id'],
                $validated['items'],
                $validated['borrow_days'] ?? 7,
                $validated['notes'] ?? null
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Peminjaman buku berhasil diproses.',
                'data' => $borrowing
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function returnBooks(int $id, Request $request)
    {
        $validated = $request->validate([
            'return_date' => 'nullable|date',
        ]);

        try {
            $borrowing = $this->borrowingService->returnBooks($id, $validated['return_date'] ?? null);

            return response()->json([
                'status' => 'success',
                'message' => 'Buku berhasil dikembalikan.',
                'data' => $borrowing
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
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
