<?php

namespace App\Http\Controllers;

use App\Models\BudgetLog;
use App\Models\User;
use Illuminate\Http\Request;

class BudgetLogController extends Controller
{
    public function index(Request $request)
    {
        $query = BudgetLog::with('user')->orderByDesc('created_at');

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs  = $query->paginate(50)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('budget.logs', compact('logs', 'users'));
    }
}
