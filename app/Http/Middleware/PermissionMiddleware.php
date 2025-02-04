<?php

declare(strict_types=1);

// phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param mixed $permission Permissions to authenticate
     *
     * @return mixed
     *
     * @suppress PhanPluginAlwaysReturnMethod
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        if (Auth::guest() && $request->ajax()) {
            return response()->json(['status' => 'error',
                'message' => 'Unauthorized - You must authenticate to perform this action.',
            ], 401);
        }

        if (Auth::guest()) {
            abort(403);
        }

        $permissions = is_array($permission) ? $permission : explode('|', $permission);

        foreach ($permissions as $permission) {
            if ($request->user()->can($permission)) {
                return $next($request);
            }
        }

        if ($request->ajax()) {
            return response()->json(['status' => 'error',
                'message' => 'Forbidden - You do not have permission to perform this action.',
            ], 403);
        }

        abort(403);
    }
}
