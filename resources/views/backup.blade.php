{{-- 1 TO 1 VIDEO CALL vew blade --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Video Call</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
        }

        .container {
            margin-top: 20px;
        }

        video {
            height: 100%;
            max-height: 480px;
            width: 100%;
            max-width: 480px;
            border-radius: 8px;
            border: 2px solid #ddd;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-bottom: 15px;
            background-color: #23272b;
        }

        .user-list-item {
            cursor: pointer;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 5px;
            background-color: #fff;
            transition: background-color 0.3s;
        }

        .user-list-item:hover {
            background-color: #f1f1f1;
        }

        .logout-btn {
            background-color: #dc3545;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #c82333;
        }

        .header {
            margin-bottom: 20px;
            text-align: center;
        }

        .refresh-btn {
            display: inline-block;
            margin-top: 5px;
            padding: 10px 20px;
            background-color: #343a40;
            color: #fff;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .refresh-btn:hover {
            background-color: #23272b;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header row-1">
            <h2>Welcome, {{ Auth::user()->name }}</h2>
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" class="logout-btn">
                    {{ __('Log Out') }}
                </button>
            </form>
        </div>

        <h1 class="text-center mb-4"><strong> Video Calling </strong></h1>
        <h5 class="text-center">Current user's Peer ID is <span id="peerId"></span></h5>
        <hr>
        <div class="row">
            <div class="col-md-6 mb-3">
                <h5>You:</h5>
                <video id="currentUserVideo" autoplay></video>
            </div>
            <div class="col-md-6 mb-3">
                <h5>Guest:</h5>
                <video id="remoteVideo" autoplay></video>
            </div>
        </div>

        <hr>

        <div class="p-2">
            <h3>Select User to Call:</h3>
            <ol id="userList">
                @foreach ($users as $user)
                    @if ($user->name == Auth::user()->name)
                        @continue
                    @endif
                    <li class="user-list-item" data-peer-id="{{ $user->peer_id }}">{{ $user->name }}</li>
                @endforeach
            </ol>
        </div>

        <a href="{{ route('dashboard') }}" class="refresh-btn">Refresh</a>
    </div>

    <script src="https://unpkg.com/peerjs@1.5.4/dist/peerjs.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const peerIdElement = document.getElementById('peerId');
            const userList = document.getElementById('userList');
            const currentUserVideo = document.getElementById('currentUserVideo');
            const remoteVideo = document.getElementById('remoteVideo');
            let selectedPeerId = '';

            const peer = new Peer({
                config: {
                    'iceServers': [{
                            url: 'stun:stun1.l.google.com:19302'
                        },
                        {
                            url: 'stun:stun3.l.google.com:19302'
                        },
                        {
                            url: 'stun:stun4.l.google.com:19302'
                        },
                    ]
                }
            });

            peer.on('open', id => {
                peerIdElement.textContent = id;

                fetch('/update-peer-id', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify({
                            peer_id: id
                        })
                    })
                    .then(response => response.json())
                    .then(data => console.log('Peer ID updated:', data))
                    .catch(error => console.error('Error updating peer ID:', error));
            });

            peer.on('call', call => {
                if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                    navigator.mediaDevices.getUserMedia({
                            audio: false,
                            video: true
                        })
                        .then(mediaStream => {
                            currentUserVideo.srcObject = mediaStream;
                            currentUserVideo.play();

                            // Disable audio playback on local video element
                            currentUserVideo.muted = true;

                            call.answer(mediaStream);
                            call.on('stream', remoteStream => {
                                remoteVideo.srcObject = remoteStream;
                                remoteVideo.play();
                            });
                        })
                        .catch(err => {
                            console.error('Error accessing media devices.', err);
                        });
                } else {
                    console.error('getUserMedia is not supported by this browser.');
                }
            });

            userList.addEventListener('click', event => {
                const target = event.target;
                if (target.classList.contains('user-list-item')) {
                    selectedPeerId = target.getAttribute('data-peer-id');
                    initiateCall();
                }
            });

            function initiateCall() {
                if (selectedPeerId) {
                    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                        navigator.mediaDevices.getUserMedia({
                                audio: false,
                                video: true
                            })
                            .then(mediaStream => {
                                currentUserVideo.srcObject = mediaStream;
                                currentUserVideo.play();

                                currentUserVideo.muted = true; // local video is muted

                                const call = peer.call(selectedPeerId, mediaStream);
                                call.on('stream', remoteStream => {
                                    remoteVideo.srcObject = remoteStream;
                                    remoteVideo.play();
                                });
                            })
                            .catch(err => {
                                console.error('Error accessing media devices.', err);
                            });
                    } else {
                        console.error('getUserMedia is not supported by this browser.');
                    }
                } else {
                    console.error('No peer ID selected.');
                }
            }
        });
    </script>
</body>

</html>

{{--  --}}
{{-- CONTROLLER --}}
{{--  --}}
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
}
{{-- ROUTES --}}
Route::post('/update-peer-id', [UserController::class, 'updatePeerId'])->name('update-peer-id');
Route::get('/dashboard', function () {
    $users = User::all();
    return view('dashboard',compact('users'));
})->middleware(['auth', 'verified'])->name('dashboard');

{{--  --}}
{{-- 1 TO 1 VIDEO CALL end --}}
