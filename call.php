<!DOCTYPE html>
<html>
<head>
  <title>WhatsApp Clone - Voice & Video Call</title>
  <style>
    #callUI { margin-top: 20px; }
    video { background: black; }
    button { margin: 5px; padding: 10px; font-size: 16px; }
  </style>
</head>
<body>
 
<h2>WhatsApp Clone - Voice & Video Call</h2>
 
<!-- Call Buttons -->
<button id="voiceCallBtn">📞 Voice Call</button>
<button id="videoCallBtn">🎥 Video Call</button>
 
<!-- Call UI -->
<div id="callUI" style="display:none;">
  <video id="localVideo" autoplay muted playsinline style="width:150px; height:150px;"></video>
  <video id="remoteVideo" autoplay playsinline style="width:300px; height:300px;"></video>
  <div>
    <button id="muteBtn">Mute</button>
    <button id="cameraBtn">Camera Off</button>
    <button id="endCallBtn">End Call</button>
  </div>
</div>
 
<!-- Agora SDK -->
<script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script>
 
<script>
  // ====== CONFIGURATION ======
  const APP_ID = '7537d330ef1644b79d12654ac952eba5'; // Your Agora App ID
  const CHANNEL = 'testChannel'; // Fixed channel for demo; change as needed
 
  // ====== VARIABLES ======
  let client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
  let localTracks = { videoTrack: null, audioTrack: null };
  let isVideoCall = false;
 
  // ====== DOM ELEMENTS ======
  const voiceCallBtn = document.getElementById('voiceCallBtn');
  const videoCallBtn = document.getElementById('videoCallBtn');
  const callUI = document.getElementById('callUI');
  const localVideo = document.getElementById('localVideo');
  const remoteVideo = document.getElementById('remoteVideo');
  const muteBtn = document.getElementById('muteBtn');
  const cameraBtn = document.getElementById('cameraBtn');
  const endCallBtn = document.getElementById('endCallBtn');
 
  // ====== FUNCTIONS ======
 
  // Start call with or without video
  async function startCall(video) {
    isVideoCall = video;
    callUI.style.display = 'block';
 
    // Join Agora channel
    await client.join(APP_ID, CHANNEL, null, null);
 
    // Create audio track from mic
    localTracks.audioTrack = await AgoraRTC.createMicrophoneAudioTrack();
 
    // Create video track if video call
    if (video) {
      localTracks.videoTrack = await AgoraRTC.createCameraVideoTrack();
      localTracks.videoTrack.play(localVideo);
    }
 
    // Publish local tracks to channel
    await client.publish(Object.values(localTracks).filter(t => t !== null));
 
    // Subscribe to remote users
    client.on("user-published", async (user, mediaType) => {
      await client.subscribe(user, mediaType);
      if (mediaType === "video") {
        user.videoTrack.play(remoteVideo);
      }
      if (mediaType === "audio") {
        user.audioTrack.play();
      }
    });
 
    client.on("user-unpublished", user => {
      remoteVideo.srcObject = null;
    });
 
    alert("Call started! Open this page on another device/browser to join.");
  }
 
  // End call and cleanup
  async function endCall() {
    for (let trackName in localTracks) {
      let track = localTracks[trackName];
      if (track) {
        track.stop();
        track.close();
        localTracks[trackName] = null;
      }
    }
    await client.leave();
    callUI.style.display = 'none';
  }
 
  // Toggle mute/unmute mic
  function toggleMute() {
    if (!localTracks.audioTrack) return;
    if (localTracks.audioTrack.isMuted()) {
      localTracks.audioTrack.setMuted(false);
      muteBtn.textContent = "Mute";
    } else {
      localTracks.audioTrack.setMuted(true);
      muteBtn.textContent = "Unmute";
    }
  }
 
  // Toggle camera on/off
  function toggleCamera() {
    if (!localTracks.videoTrack) return;
    if (localTracks.videoTrack.isMuted()) {
      localTracks.videoTrack.setMuted(false);
      cameraBtn.textContent = "Camera Off";
    } else {
      localTracks.videoTrack.setMuted(true);
      cameraBtn.textContent = "Camera On";
    }
  }
 
  // ====== EVENT LISTENERS ======
  voiceCallBtn.onclick = () => startCall(false);
  videoCallBtn.onclick = () => startCall(true);
  muteBtn.onclick = toggleMute;
  cameraBtn.onclick = toggleCamera;
  endCallBtn.onclick = endCall;
 
  // ====== PERMISSION CHECK ======
  async function checkPermissions() {
    try {
      await navigator.mediaDevices.getUser Media({ audio: true, video: true });
      console.log('Permissions granted');
    } catch (err) {
      alert('Please allow microphone and camera permissions to use calls.');
    }
  }
  checkPermissions();
 
</script>
 
</body>
</html>
