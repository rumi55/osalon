<?php

namespace App\Http\Middleware;

use App\Salon;
use App\User;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AbonnementMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $salon = Salon::find($request->salon);

        $abonnement = $salon->abonnements()->orderBy("id", "desc")->first();

        if(Carbon::parse($abonnement->echeance)->lessThan(Carbon::now()))
        {
            return response()->json([
                "message" => "Votre abonnement a expiré, veuillez vous réabonner."
            ], 402);
        }
        return $next($request);
    }
}
