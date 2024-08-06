<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class UserController extends Controller
{
    public function updatePeerId(Request $request)
    {
        $user = Auth::user(); // Get the currently authenticated user

        if ($user) {
            $peerId = $request->input('peer_id');

            // Validate the peer ID
            $validatedData = $request->validate([
                'peer_id' => 'required|string|max:255|unique:users,peer_id,' . $user->id,
            ]);

            // Update the user's peer ID
            $user->peer_id = $peerId;
            $user->save();

            return response()->json(['success' => true, 'message' => 'Peer ID updated successfully.']);
        }

        return response()->json(['success' => false, 'message' => 'User not authenticated.'], 401);
    }
}
