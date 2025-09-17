<?php
header('Content-Type: application/json');
 
if (isset($_FILES['voiceMessage'])) {
    $uploadDir = 'uploads/voice_messages/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
 
    $fileName = uniqid() . '.webm';
    $uploadFile = $uploadDir . $fileName;
 
    if (move_uploaded_file($_FILES['voiceMessage']['tmp_name'], $uploadFile)) {
        echo json_encode(['success' => true, 'file' => $uploadFile]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save file']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
}
?>
