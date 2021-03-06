<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\SalonResource;
use App\Http\Resources\UserResource;
use App\Jobs\SendSMS;
use App\Salon;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Response;

class UserController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $salons = [];
        foreach ($this->user->salons()->orderBy("nom")->get() as $salon)
        {
            $salons[] = [
                "id" => $salon->id,
                "nom" => $salon->nom,
                "adresse" => $salon->adresse,
                "users" => UserResource::collection($salon->users()->orderBy("name")->get()),
            ];
        }

        return response()->json($salons);
    }

    /**
     * Show users for given salon
     *
     * @param \Illuminate\Http\Request $request
     * @param Salon $salon
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Salon $salon)
    {
        /**
         * Si au moment de l'affichage, l'utilisateur a maintenant 1 seul salon,
         * renvyer 204 pour retouner à Index et auto reactualiser
         */
        if($this->user->salons()->count() == 1)
        {
            return \response()->json(new SalonResource(new Salon()), 204);
        }

        return response()->json(UserResource::collection($salon->users()->orderBy("name")->get()));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateUserRequest $request)
    {
        $status = 200;
        $statusMessage = "Ok";

        DB::transaction(function () use (&$status, &$statusMessage, $request){
            $user = User::where("telephone", $request->telephone)->first();

            //si user n'existe pas
            if($user == null)
            {
                $password = User::generatePassword();

                $user = User::create([
                    "name" => $request->name,
                    "telephone" => $request->telephone,
                    "email" => $request->email,
                    "password" => bcrypt($password),
                ]);

                //Envoi du mot de passe par SMS
                $message = "Votre mot de passe est: $password" .
                "%0ATéléchargez l'application " . config("app.name") . " sur playstore.";
                $sms = new \stdClass();
                $sms->to = [$request->telephone];
                $sms->message = $message;
                Queue::push(new SendSMS($sms));

                $statusMessage = "L'utilisateur a été créé! Nous venons de lui envoyer son mot de passe par SMS";
                $status = 201;
            }
            else
            {
                $salon = $this->salon->nom;
                //Envoi d'une notification par SMS
                $message =
                    "$salon a été rattaché à votre compte " . config('app.name') .
                    "%0AVous pouvez suivre les activités de ce salon à distance partout où vous etes.";
                $sms = new \stdClass();
                $sms->to = [$request->telephone];
                $sms->message = $message;
                Queue::push(new SendSMS($sms));

                $statusMessage = "L'utilisateur a été ajouté avec succès! Nous venons de lui envoyer une notification par SMS";
                $status = 200;
            }

            $user->salons()->sync([$this->salon->id], false);
        }, 1);

        return response()->json([
            "message" => $statusMessage,
        ], $status);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        if($this->user->id == $user->id)
        {
            return response()->json([
                "message" => "Vous n'êtes pas autorisé à effectuer cette action"
            ], 401);
        }

        /**
         * Check if the resource exists and prevent access to another user's resource
         */
        if(!DB::table("salon_user")->where(["salon_id" => $this->salon->id, "user_id" => $user->id])->exists())
        {
            return response()->json([
                "message" => "L'utilisateur n'existe pas ou a été supprimé"
            ], 404);
        }

        if($user->salons()->count() == 1)
        {
            User::destroy($user->id);
        }
        else
        {
            $user->salons()->detach($this->salon->id);
        }

        return response()->json(null, 204);
    }
}
