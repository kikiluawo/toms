<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyContextController extends Controller
{
    /**
     * Show the company and branch picker.
     */
    public function select(Request $request): View
    {
        $memberships = $request->user()->companies()->with('branches')->get();

        return view('context.select', [
            'memberships' => $memberships,
            'allowedBranchIds' => $request->user()->branches()->pluck('branches.id'),
        ]);
    }

    /**
     * Store the selected company and branch in the session.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $membership = $request->user()->companies()->with('branches')->find($validated['company_id']);

        abort_unless($membership, 403);

        $branchId = $validated['branch_id'] ?? null;

        if ($branchId !== null) {
            $branch = $membership->branches->firstWhere('id', (int) $branchId);

            $allowed = (bool) $membership->pivot->all_branches
                || $request->user()->canAccessBranch((int) $branchId);

            abort_unless($branch && $allowed, 403);
        }

        $request->session()->put([
            'active_company_id' => $membership->id,
            'active_branch_id' => $branchId,
        ]);

        return redirect()->route('dashboard');
    }
}
