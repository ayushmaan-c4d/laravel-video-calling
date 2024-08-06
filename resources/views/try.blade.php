<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PeerJS Video Call</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
        video {
            height: auto;
            width: 600px;
            margin: 10px;
            border: 1px solid black;
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
        <h1>Current user id is <span id="peerId"></span></h1>
        <input type="text" id="remotePeerId" placeholder="Enter peer ID to call" class="form-control" />
        <button id="callButton" class="btn btn-primary">Call</button>
        <div class="row p-3">
        <div >
            <h5>You:</h5>
            <video id="currentUserVideo" autoplay></video>
        </div>
        <div  >
            <h5>Guest:</h5>
            <video id="remoteVideo" autoplay></video>
        </div>
        </div>
    </div>
    <hr>
    <div class="p-2">
        <h3>List of Users:</h3>
        <ol>
            @foreach ($users as $user)
              <li> {{ $user->name}} </li>                
            @endforeach
        </ol>
    </div>

    <script src="https://unpkg.com/peerjs@1.5.4/dist/peerjs.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const peerIdElement = document.getElementById('peerId');
            const remotePeerIdInput = document.getElementById('remotePeerId');
            const callButton = document.getElementById('callButton');
            const currentUserVideo = document.getElementById('currentUserVideo');
            const remoteVideo = document.getElementById('remoteVideo');

            const peer = new Peer({
            	config: {'iceServers': [
            	  { url: 'stun:stun.l.google.com:19302' },
            	  { url: 'stun:stun.l.google.com:19302' }
            	]} 
              });

            peer.on('open', id => {
                peerIdElement.textContent = id;
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
                    navigator.mediaDevices.getUserMedia({ video: true, audio:true })
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
                }
            });

            callButton.addEventListener('click', () => {
                const remotePeerId = remotePeerIdInput.value;
                if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                    navigator.mediaDevices.getUserMedia({ video: true })
                        .then(mediaStream => {
                            currentUserVideo.srcObject = mediaStream;
                            currentUserVideo.play();
                            const call = peer.call(remotePeerId, mediaStream);
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
                    navigator.mediaDevices.getUserMedia({ video: true, audio: false })
                        .then(mediaStream => {
                            currentUserVideo.srcObject = mediaStream;
                            currentUserVideo.play();
                            const call = peer.call(remotePeerId, mediaStream);
                            call.on('stream', remoteStream => {
                                remoteVideo.srcObject = remoteStream;
                                remoteVideo.play();
                            });
                        })
                        .catch(err => {
                            console.error('Error accessing media devices.', err);
                        });
                }
            });
        });
    </script>
</body>
</html>
