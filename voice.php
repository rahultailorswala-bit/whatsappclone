<!DOCTYPE html>
<html>
<head>
  <title>WhatsApp Clone - Voice Message</title>
  <style>
    button { margin: 5px; padding: 10px; font-size: 16px; }
    audio { display: block; margin-top: 10px; }
  </style>
</head>
<body>
 
<h2>WhatsApp Clone - Voice Message</h2>
 
<button id="startRecordingBtn">Start Recording 🎤</button>
<button id="stopRecordingBtn" disabled>Stop Recording ⏹️</button>
 
<audio id="voiceMsgPlayback" controls style="display:none;"></audio>
 
<script>
  let mediaRecorder;
  let audioChunks = [];
 
  const startBtn = document.getElementById('startRecordingBtn');
  const stopBtn = document.getElementById('stopRecordingBtn');
  const playback = document.getElementById('voiceMsgPlayback');
 
  startBtn.onclick = async () => {
    try {
      const stream = await navigator.mediaDevices.getUser Media({ audio: true });
      mediaRecorder = new MediaRecorder(stream);
      mediaRecorder.start();
      audioChunks = [];
 
      mediaRecorder.ondataavailable = e => {
        audioChunks.push(e.data);
      };
 
      mediaRecorder.onstop = async () => {
        const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
        playback.src = URL.createObjectURL(audioBlob);
        playback.style.display = 'block';
 
        // Upload voice message to server
        const formData = new FormData();
        formData.append('voiceMessage', audioBlob, 'voiceMessage.webm');
 
        const response = await fetch('upload_voice_message.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();
 
        if (result.success) {
          alert('Voice message uploaded successfully!');
          // TODO: Add code to show voice message in chat UI
        } else {
          alert('Upload failed: ' + result.error);
        }
      };
 
      startBtn.disabled = true;
      stopBtn.disabled = false;
    } catch (err) {
      alert('Microphone permission denied or error: ' + err.message);
    }
  };
 
  stopBtn.onclick = () => {
    mediaRecorder.stop();
    startBtn.disabled = false;
    stopBtn.disabled = true;
  };
</script>
 
</body>
</html>
