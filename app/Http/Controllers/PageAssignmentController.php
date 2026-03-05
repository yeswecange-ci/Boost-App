<?php

namespace App\Http\Controllers;

use App\Models\FacebookPage;
use App\Models\User;
use Illuminate\Http\Request;

class PageAssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index()
    {
        $users = User::with(['roles', 'facebookPages'])
            ->where('is_active', true)
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))
            ->orderBy('name')
            ->get();

        $pages = FacebookPage::where('is_active', true)->orderBy('page_name')->get();

        return view('admin.page-assignments', compact('users', 'pages'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'assignments'   => 'nullable|array',
            'assignments.*' => 'nullable|array',
        ]);

        $users = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))->get();

        foreach ($users as $user) {
            $pageIds = $request->input("assignments.{$user->id}", []);
            $user->facebookPages()->sync($pageIds);
        }

        return back()->with('success', 'Assignations de pages sauvegardées.');
    }
}
