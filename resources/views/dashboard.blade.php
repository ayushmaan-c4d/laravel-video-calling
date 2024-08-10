{{-- 3 USERS GROUP VIDEO CALL  --}}
{{-- <!DOCTYPE html>
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

        .video-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        .video-container {
            flex: 1 0 30%;
            margin: 10px;
            position: relative;
        }

        .video-container h5 {
            position: absolute;
            top: 5px;
            left: 10px;
            color: #fff;
            background-color: rgba(0, 0, 0, 0.5);
            padding: 5px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Welcome, {{ Auth::user()->name }}</h2>
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" class="logout-btn">
                    {{ __('Log Out') }}
                </button>
            </form>
        </div>

        <h1 class="text-center mb-4"><strong>Video Calling</strong></h1>
        <h5 class="text-center">Current user's Peer ID is <span id="peerId"></span></h5>
        <hr>

        <div class="video-grid">
            <div class="video-container">
                <h5>You:</h5>
                <video id="currentUserVideo" autoplay muted></video>
            </div>
            <div class="video-container">
                <h5>Participant 1:</h5>
                <video id="remoteVideo1" autoplay></video>
            </div>
            <div class="video-container">
                <h5>Participant 2:</h5>
                <video id="remoteVideo2" autoplay></video>
            </div>
            <div class="video-container" hidden>
                <h5>Participant 3:</h5>
                <video id="remoteVideo3" autoplay></video>
            </div>
        </div>

        <hr>

        <div class="m-2">
            <h3>Select Users to Call:</h3>
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
        <button class="btn btn-warning">Add User</button>
    </div>

    <script src="https://unpkg.com/peerjs@1.5.4/dist/peerjs.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const peerIdElement = document.getElementById('peerId');
            const userList = document.getElementById('userList');
            const currentUserVideo = document.getElementById('currentUserVideo');
            const remoteVideos = [
                document.getElementById('remoteVideo1'),
                document.getElementById('remoteVideo2'),
                document.getElementById('remoteVideo3')
            ];
            let selectedPeerIds = [];

            const peer = new Peer({
                config: {
                    'iceServers': [
                        { url: 'stun:stun1.l.google.com:19302' },
                        { url: 'stun:stun3.l.google.com:19302' },
                        { url: 'stun:stun4.l.google.com:19302' },
                    ]
                }
            });

            peer.on('open', id => {
                peerIdElement.textContent = id;

                fetch('/update-peer-id', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
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

                            call.answer(mediaStream);
                            call.on('stream', remoteStream => {
                                let assigned = false;
                                for (const remoteVideo of remoteVideos) {
                                    if (remoteVideo.srcObject === null) {
                                        remoteVideo.srcObject = remoteStream;
                                        remoteVideo.play();
                                        assigned = true;
                                        break;
                                    }
                                }
                                if (!assigned) {
                                    console.error('No available video element to display the stream.');
                                }
                            });
                        })
                        .catch(err => console.error('Error accessing media devices.', err));
                } else {
                    console.error('getUserMedia is not supported by this browser.');
                }
            });

            userList.addEventListener('click', event => {
                const target = event.target;
                if (target.classList.contains('user-list-item')) {
                    const peerId = target.getAttribute('data-peer-id');
                    if (!selectedPeerIds.includes(peerId)) {
                        selectedPeerIds.push(peerId);
                    }
                    initiateCalls();
                }
            });

            function initiateCalls() {
                if (selectedPeerIds.length > 0) {
                    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                        navigator.mediaDevices.getUserMedia({
                                audio: false,
                                video: true
                            })
                            .then(mediaStream => {
                                currentUserVideo.srcObject = mediaStream;
                                currentUserVideo.play();
                                currentUserVideo.muted = true;

                                selectedPeerIds.forEach(peerId => {
                                    const call = peer.call(peerId, mediaStream);
                                    call.on('stream', remoteStream => {
                                        let assigned = false;
                                        for (const remoteVideo of remoteVideos) {
                                            if (remoteVideo.srcObject === null) {
                                                remoteVideo.srcObject = remoteStream;
                                                remoteVideo.play();
                                                assigned = true;
                                                break;
                                            }
                                        }
                                        if (!assigned) {
                                            console.error('No available video element to display the stream.');
                                        }
                                    });
                                });

                                selectedPeerIds = [];
                            })
                            .catch(err => console.error('Error accessing media devices.', err));
                    } else {
                        console.error('getUserMedia is not supported by this browser.');
                    }
                } else {
                    console.error('No peer IDs selected.');
                }
            }
        });
    </script>
</body>
</html> --}}


{{-- Multi User upto 4 Video CAll --}}
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

        .video-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        .video-container {
            flex: 1 0 30%;
            margin: 10px;
            position: relative;
        }

        .video-container h5 {
            position: absolute;
            top: 5px;
            left: 10px;
            color: #fff;
            background-color: rgba(0, 0, 0, 0.5);
            padding: 5px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Welcome, {{ Auth::user()->name }}</h2>
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" class="logout-btn">
                    {{ __('Log Out') }}
                </button>
            </form>
        </div>

        <h1 class="text-center mb-4"><strong>Video Calling</strong></h1>
        <h5 class="text-center">Current user's Peer ID is <span id="peerId"></span></h5>
        <hr>

        <div class="video-grid">
            <div class="video-container">
                <h5>You:</h5>
                <video id="currentUserVideo" autoplay muted></video>
            </div>
            <div class="video-container">
                <h5>Participant 1:</h5>
                <video id="remoteVideo1" autoplay></video>
            </div>
            <div class="video-container">
                <h5>Participant 2:</h5>
                <video id="remoteVideo2" autoplay></video>
            </div>
            <div class="video-container">
                <h5>Participant 3:</h5>
                <video id="remoteVideo3" autoplay></video>
            </div>
        </div>

        <hr>

        <div class="m-2">
            <h3>Select Users to Call:</h3>
            <ol id="userList">
                <!-- User list items will be dynamically populated -->
            </ol>
        </div>

        <a href="{{ route('dashboard') }}" class="refresh-btn">Refresh</a>
        <button class="btn btn-warning" id="addUserBtn">Add User</button>
    </div>

    <script src="https://unpkg.com/peerjs@1.5.4/dist/peerjs.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const peerIdElement = document.getElementById('peerId');
            const userList = document.getElementById('userList');
            const currentUserVideo = document.getElementById('currentUserVideo');
            const remoteVideos = {
                1: document.getElementById('remoteVideo1'),
                2: document.getElementById('remoteVideo2'),
                3: document.getElementById('remoteVideo3')
            };
            let selectedPeerIds = [];
            let videoAssignment = {};

            const peer = new Peer({
                config: {
                    'iceServers': [
                        { url: 'stun:stun.l.google.com:19302' },
                        { url: 'stun:stun1.l.google.com:19302' },
                        { url: 'stun:stun3.l.google.com:19302' },
                        { url: 'stun:stun4.l.google.com:19302' },
                    ]
                }
            });

            peer.on('open', id => {
                peerIdElement.textContent = id;

                fetch('/update-peer-id', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
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

                            call.answer(mediaStream);
                            call.on('stream', remoteStream => {
                                if (!videoAssignment[call.peer]) {
                                    let availableVideoId = Object.keys(remoteVideos).find(id => !remoteVideos[id].srcObject);

                                    if (availableVideoId) {
                                        videoAssignment[call.peer] = availableVideoId;
                                        remoteVideos[availableVideoId].srcObject = remoteStream;
                                        remoteVideos[availableVideoId].play();
                                    } else {
                                        console.error('No available video element to display the stream.');
                                    }
                                } else {
                                    const assignedVideoId = videoAssignment[call.peer];
                                    remoteVideos[assignedVideoId].srcObject = remoteStream;
                                    remoteVideos[assignedVideoId].play();
                                }
                            });
                        })
                        .catch(err => console.error('Error accessing media devices.', err));
                } else {
                    console.error('getUserMedia is not supported by this browser.');
                }
            });

            userList.addEventListener('click', event => {
                const target = event.target;
                if (target.classList.contains('user-list-item')) {
                    const peerId = target.getAttribute('data-peer-id');
                    if (!selectedPeerIds.includes(peerId)) {
                        selectedPeerIds.push(peerId);
                    }
                    initiateCalls();
                }
            });

            function initiateCalls() {
                if (selectedPeerIds.length > 0) {
                    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                        navigator.mediaDevices.getUserMedia({
                                audio: false,
                                video: true
                            })
                            .then(mediaStream => {
                                currentUserVideo.srcObject = mediaStream;
                                currentUserVideo.play();
                                currentUserVideo.muted = true;

                                selectedPeerIds.forEach(peerId => {
                                    const call = peer.call(peerId, mediaStream);
                                    call.on('stream', remoteStream => {
                                        if (!videoAssignment[peerId]) {
                                            let availableVideoId = Object.keys(remoteVideos).find(id => !remoteVideos[id].srcObject);

                                            if (availableVideoId) {
                                                videoAssignment[peerId] = availableVideoId;
                                                remoteVideos[availableVideoId].srcObject = remoteStream;
                                                remoteVideos[availableVideoId].play();
                                            } else {
                                                console.error('No available video element to display the stream.');
                                            }
                                        } else {
                                            const assignedVideoId = videoAssignment[peerId];
                                            remoteVideos[assignedVideoId].srcObject = remoteStream;
                                            remoteVideos[assignedVideoId].play();
                                        }
                                    });
                                });

                                selectedPeerIds = [];
                            })
                            .catch(err => console.error('Error accessing media devices.', err));
                    } else {
                        console.error('getUserMedia is not supported by this browser.');
                    }
                } else {
                    console.error('No peer IDs selected.');
                }
            }

            function refreshUserList() {
                fetch('/users')
                    .then(response => response.json())
                    .then(users => {
                        userList.innerHTML = '';
                        users.forEach(user => {
                            if (user.name !== '{{ Auth::user()->name }}') {
                                const listItem = document.createElement('li');
                                listItem.textContent = user.name;
                                listItem.classList.add('user-list-item');
                                listItem.setAttribute('data-peer-id', user.peer_id);
                                userList.appendChild(listItem);
                            }
                        });
                    })
                    .catch(error => console.error('Error fetching users:', error));
            }

            document.getElementById('addUserBtn').addEventListener('click', refreshUserList);
            refreshUserList();
        });
    </script>
</body>
</html>
