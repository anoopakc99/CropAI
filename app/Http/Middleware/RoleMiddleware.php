<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param mixed ...$roles A list of roles that are allowed access.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = Auth::user(); // Get the currently authenticated user

        // 1. If the user is not authenticated, redirect to the login page.
        if (!$user) {
            // It's good to ensure the login page itself isn't cached if reached via redirect.
            return $this->noCacheHeaders(redirect()->route('login'));
        }

        // 2. Super Admin (role 1) has access to everything this middleware protects.
        // This acts as a global override for role 1.
        if ($user->role == 1) {
            return $this->noCacheHeaders($next($request)); // Allow request, add no-cache headers
        }

        // 3. Check if the user has any of the required roles passed to the middleware.
        //    `...$roles` means this method can accept multiple role arguments (e.g., middleware('role:2,3')).
        foreach ($roles as $role) {
            if ($user->role == $role) {
                return $this->noCacheHeaders($next($request)); // Allow request, add no-cache headers
            }
        }

        // 4. If the user is authenticated but does not have the required role,
        //    redirect them to an appropriate dashboard based on their current role.
        return $this->noCacheHeaders($this->redirectToDashboard($user));
    }

    /**
     * Determine where to redirect users based on their role if they are unauthorized for a specific route.
     *
     * @param \App\Models\User $user The authenticated user.
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectToDashboard($user)
    {
        switch ($user->role) {
            case 1: // Super Admin (though they'd likely pass the check above)
                return redirect('/ccbf-admin');
            case 2: // Example: Master User
                return redirect('/master/consolidated');
            case 3: // Example: Regular Admin
                return redirect('/admin/dashboard');
            default: // Fallback for any other authenticated user
                return redirect('/dashboard');
        }
    }

    /**
     * Add no-cache headers to the response to prevent browser back button issues
     * and ensure fresh content.
     *
     * @param \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse $response
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    protected function noCacheHeaders($response)
    {
        // Use headers->set() for StreamedResponse compatibility
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');

        return $response;
    }
}

