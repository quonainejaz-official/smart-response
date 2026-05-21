<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\UserResource;
use Quonain\SmartResponse\Traits\HasSmartResponse;

final class ExampleController extends Controller
{
    use HasSmartResponse;

    public function index(Request $request)
    {
        $users = User::paginate(15);

        return $this->smartResponse(
            request: $request,
            data: UserResource::collection($users),
            view: 'users.index',
            message: 'users.fetched',
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ]);

        $user = User::create($validated);

        return $this->smartResponse(
            request: $request,
            data: new UserResource($user),
            view: 'users.show',
            viewData: ['user' => $user],
            message: 'users.created',
            status: 201,
            route: 'users.index',
            toast: true,
        );
    }

    public function destroy(Request $request, User $user)
    {
        $user->delete();

        return $this->smartResponse(
            request: $request,
            data: null,
            message: 'users.deleted',
            route: 'users.index',
            redirect: null,
        );
    }
}
