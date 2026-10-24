<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\UserContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveActiveContext
{
    /**
     * Keep the session company/branch in step with what the user is allowed to see.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $request->routeIs('context.*')) {
            return $next($request);
        }

        $context = $this->context($request, $user);

        if ($context->visibleCompanies()->isEmpty()) {
            return $next($request);
        }

        if (! $context->isGrounded()) {
            if ($context->isGroup()) {
                $this->remember($request, UserContext::ALL, null);
            } elseif ($context->visibleCompanies()->count() > 1) {
                return redirect()->route('context.select');
            } else {
                $this->remember($request, $context->visibleCompanies()->first()->id, null);
            }

            $context = $this->context($request, $user);
        }

        if ($request->session()->get('active_branch_id') && ! $context->branch()) {
            $this->remember($request, $request->session()->get('active_company_id'), null);
            $context = $this->context($request, $user);
        }

        $request->attributes->set('userContext', $context);

        View::share([
            'userContext' => $context,
            'activeCompany' => $context->company(),
            'activeBranch' => $context->branch(),
        ]);

        return $next($request);
    }

    private function context(Request $request, User $user): UserContext
    {
        return new UserContext(
            $user,
            $request->session()->get('active_company_id'),
            $request->session()->get('active_branch_id'),
        );
    }

    private function remember(Request $request, int|string|null $companyId, ?int $branchId): void
    {
        $request->session()->put([
            'active_company_id' => $companyId,
            'active_branch_id' => $branchId,
        ]);
    }
}
