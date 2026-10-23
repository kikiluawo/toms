<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveActiveContext
{
    /**
     * Resolve the active company and branch for the authenticated user.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $request->routeIs('context.*')) {
            return $next($request);
        }

        $companies = $user->companies()->with('branches')->get();

        if ($companies->isEmpty()) {
            return $next($request);
        }

        $company = null;
        $activeCompanyId = $request->session()->get('active_company_id');

        if ($activeCompanyId) {
            $company = $companies->firstWhere('id', (int) $activeCompanyId);
        }

        if (! $company) {
            if ($companies->count() > 1) {
                return redirect()->route('context.select');
            }

            $company = $companies->first();
            $request->session()->put('active_company_id', $company->id);
        }

        $branch = null;
        $activeBranchId = $request->session()->get('active_branch_id');

        if ($activeBranchId) {
            $branch = $company->branches->firstWhere('id', (int) $activeBranchId);

            $allowed = (bool) $company->pivot->all_branches
                || $user->canAccessBranch((int) $activeBranchId);

            if (! $branch || ! $allowed) {
                $branch = null;
                $request->session()->forget('active_branch_id');
            }
        }

        View::share([
            'activeCompany' => $company,
            'activeBranch' => $branch,
            'activeMembership' => $company->pivot,
        ]);

        $request->attributes->set('activeCompany', $company);
        $request->attributes->set('activeBranch', $branch);

        return $next($request);
    }
}
