<?php

namespace Modules\Membership\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Membership\Models\Member;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        // Eager loading roles and user to prevent N+1
        $query = Member::with(['roles', 'user']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $members = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $members
        ]);
    }

    public function show(int $id)
    {
        $member = Member::with(['roles', 'user'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $member
        ]);
    }

    public function restore(int $id)
    {
        $member = Member::withTrashed()->findOrFail($id);
        $member->restore();

        return response()->json([
            'status' => 'success',
            'message' => "Anggota '{$member->name}' berhasil direstore.",
            'data' => $member
        ]);
    }
}
