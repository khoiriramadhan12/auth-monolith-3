<?php

namespace Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Book;

class BookController extends Controller
{
    public function index(Request $request)
    {
        // Eager loading to prevent N+1 query problem
        $query = Book::with(['author', 'publisher', 'categories']);

        if ($request->has('available')) {
            $query->available();
        }

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->filled('category')) {
            $query->category($request->input('category'));
        }

        $books = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $books
        ]);
    }

    public function show(int $id)
    {
        $book = Book::with(['author', 'publisher', 'categories'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $book
        ]);
    }

    public function restore(int $id)
    {
        $book = Book::withTrashed()->findOrFail($id);
        $book->restore();

        return response()->json([
            'status' => 'success',
            'message' => "Buku '{$book->title}' berhasil direstore.",
            'data' => $book
        ]);
    }
}
