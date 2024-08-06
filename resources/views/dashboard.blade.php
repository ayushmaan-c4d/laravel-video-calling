<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PeerJS Video Call</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
        video {
            height: auto;
            width: 500px;
            margin: 10px;
            border: 1px solid black;
        }
        .user-list-item {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="pd-3">
        <h2>Welcome, {{ Auth::user()->name }}</h2>
        <div style="text-align: right">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-dropdown-link :href="route('logout')"
                        onclick="event.preventDefault();
                                    this.closest('form').submit();">
                    {{ __('Log Out') }}
                </x-dropdown-link>
            </form>
        </div>
        <h1>VIDEO CALLING</h1>
        <h5>Current user id is <span id="peerId"></span></h5>
        <div class="row p-3">
            <div>
                <h5>You:</h5>
                <video id="currentUserVideo" autoplay></video>
            </div>
            <div>
                <h5>Guest:</h5>
                <video id="remoteVideo" autoplay></video>
            </div>
        </div>
        <hr>
        <div class="p-2">
            <h3>Select Users to Call:</h3>
            <ol id="userList">
                @foreach ($users as $user)
                  <li class="user-list-item" data-peer-id="{{ $user->peer_id }}">{{ $user->name }}</li>                
                @endforeach
            </ol>
        </div>
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
                config: {'iceServers': [
                  { url: 'stun:stun.l.google.com:19302' }
                ]}
            });

            peer.on('open', id => {
                peerIdElement.textContent = id;

                fetch('/update-peer-id', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ peer_id: id })
                })
                .then(response => response.json())
                .then(data => console.log('Peer ID updated:', data))
                .catch(error => console.error('Error updating peer ID:', error));
            });

            peer.on('call', call => {
                if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                    navigator.mediaDevices.getUserMedia({ video: true })
                        .then(mediaStream => {
                            currentUserVideo.srcObject = mediaStream;
                            currentUserVideo.play();
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
                        navigator.mediaDevices.getUserMedia({ video: true })
                            .then(mediaStream => {
                                currentUserVideo.srcObject = mediaStream;
                                currentUserVideo.play();
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
