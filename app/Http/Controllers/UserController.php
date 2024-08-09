<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class UserController extends Controller
{
    public function updatePeerId(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user) {
            $peerId = $request->input('peer_id');

            $validatedData = $request->validate([
                'peer_id' => 'required|string|max:255|unique:users,peer_id,' . $user->id,
            ]);

            // update peer id
            $user->peer_id = $peerId;
            $user->save();

            return response()->json(['success' => true, 'message' => 'Peer ID updated successfully.']);
        }

        return response()->json(['success' => false, 'message' => 'User not authenticated.'], 401);
    }

    public function getUsers()
    {
        $users = User::all();
        return response()->json($users);
    }
}
