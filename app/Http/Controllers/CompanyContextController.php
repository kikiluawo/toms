<?php

namespace App\Http\Controllers;

use App\Support\UserContext;
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
        $context = UserContext::for($request->user());

        $picker = $context->visibleCompanies()->map(function ($company) use ($context) {
            $branchIds = $context->visibleBranchIds($company->id);

            return [
                'company' => $company,
                'branches' => $company->branches->filter(fn ($branch) => in_array($branch->id, $branchIds, true))->values(),
                'role_label' => $context->roleLabel($company->id),
                'scope_label' => $context->scopeLabel($company->id),
                'can_act' => $context->canActOn($company->id),
            ];
        })->values();

        return view('context.select', [
            'userContext' => $context,
            'picker' => $picker,
            'activeCompanyId' => $request->session()->get('active_company_id'),
            'activeBranchId' => $request->session()->get('active_branch_id'),
        ]);
    }

    /**
     * Store the selected company and branch in the session.
     */
    public function store(Request $request): RedirectResponse
    {
        $context = UserContext::for($request->user());

        $validated = $request->validate([
            'company_id' => ['required'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $companyId = $validated['company_id'] === UserContext::ALL
            ? UserContext::ALL
            : (int) $validated['company_id'];

        $branchId = isset($validated['branch_id']) ? (int) $validated['branch_id'] : null;

        if ($companyId === UserContext::ALL) {
            $branchId = null;
        }

        abort_unless($context->canActOn($companyId, $branchId), 403);

        $request->session()->put([
            'active_company_id' => $companyId,
            'active_branch_id' => $branchId,
        ]);

        return redirect()->route('dashboard');
    }
}
