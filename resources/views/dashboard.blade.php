{{-- Multi user video call --}}
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

        #videoContainer {
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

        .hidden {
            display: none;
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

        <div id="videoContainer">
            <!-- video elements adding here -->
        </div>

        <hr>

        <div class="m-2">
            <h3>Select Users to Call:</h3> &nbsp; <button class="btn btn-warning mb-2" id="addUserBtn">Add User</button>
            <ol id="userList" class="hidden">
                <!-- User list show -->
            </ol>
        </div>
        <a href="{{ route('dashboard') }}" class="refresh-btn">Refresh</a>
    </div>
    <script src="https://unpkg.com/peerjs@1.5.4/dist/peerjs.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const peerIdElement = document.getElementById('peerId');
            const userList = document.getElementById('userList');
            const videoContainer = document.getElementById('videoContainer');
            let selectedPeerIds = [];
            let videoAssignment = {};
            let localStreamAdded = false;

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
                            if (!localStreamAdded) {
                                const currentUserVideo = document.createElement('video');
                                currentUserVideo.id = 'currentUserVideo';
                                currentUserVideo.autoplay = true;
                                currentUserVideo.muted = true;
                                videoContainer.appendChild(currentUserVideo);
                                currentUserVideo.srcObject = mediaStream;
                                currentUserVideo.play();
                                localStreamAdded = true;
                            }

                            //answer call
                            call.answer(mediaStream);
                            call.on('stream', remoteStream => {
                                if (!videoAssignment[call.peer]) {
                                    //create a new video element of remote user
                                    const remoteVideo = document.createElement('video');
                                    remoteVideo.id = `remoteVideo${Date.now()}`;
                                    remoteVideo.autoplay = true;
                                    remoteVideo.classList.add('video-container');
                                    videoContainer.appendChild(remoteVideo);

                                    videoAssignment[call.peer] = remoteVideo.id;
                                    remoteVideo.srcObject = remoteStream;
                                    remoteVideo.play();
                                } else {
                                    const assignedVideoId = videoAssignment[call.peer];
                                    document.getElementById(assignedVideoId).srcObject = remoteStream;
                                    document.getElementById(assignedVideoId).play();
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
                    userList.classList.add('hidden');
                }
            });

            document.getElementById('addUserBtn').addEventListener('click', () => {
                userList.classList.toggle('hidden');
                refreshUserList();
            });

            function initiateCalls() {
                if (selectedPeerIds.length > 0) {
                    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                        navigator.mediaDevices.getUserMedia({
                                audio: false,
                                video: true
                            })
                            .then(mediaStream => {
                                if (!localStreamAdded) {
                                    const currentUserVideo = document.createElement('video');
                                    currentUserVideo.id = 'currentUserVideo';
                                    currentUserVideo.autoplay = true;
                                    currentUserVideo.muted = true;
                                    videoContainer.appendChild(currentUserVideo);
                                    currentUserVideo.srcObject = mediaStream;
                                    currentUserVideo.play();
                                    localStreamAdded = true;
                                }

                                selectedPeerIds.forEach(peerId => {
                                    const call = peer.call(peerId, mediaStream);
                                    call.on('stream', remoteStream => {
                                        if (!videoAssignment[peerId]) {
                                            //create a new video of remote user
                                            const remoteVideo = document.createElement('video');
                                            remoteVideo.id = `remoteVideo${Date.now()}`;
                                            remoteVideo.autoplay = true;
                                            remoteVideo.classList.add('video-container');
                                            videoContainer.appendChild(remoteVideo);

                                            videoAssignment[peerId] = remoteVideo.id;
                                            remoteVideo.srcObject = remoteStream;
                                            remoteVideo.play();
                                        } else {
                                            const assignedVideoId = videoAssignment[peerId];
                                            document.getElementById(assignedVideoId).srcObject = remoteStream;
                                            document.getElementById(assignedVideoId).play();
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

            refreshUserList();
        });
    </script>
</body>
</html>
