<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
 
require 'db.php';
 
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
 
// Fetch all users except current user for contacts
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE id != ? ORDER BY username ASC");
$stmt->execute([$user_id]);
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>WhatsApp Clone - Chat</title>
<style>
  * {
    box-sizing: border-box;
  }
  body, html {
    margin: 0; padding: 0; height: 100%;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #ECE5DD;
  }
  .container {
    display: flex;
    height: 100vh;
    max-height: 100vh;
  }
  .sidebar {
    width: 320px;
    background: #075E54;
    color: white;
    display: flex;
    flex-direction: column;
  }
  .sidebar-header {
    padding: 20px;
    font-size: 22px;
    font-weight: 700;
    border-bottom: 1px solid #128C7E;
    text-align: center;
  }
  .contacts {
    flex-grow: 1;
    overflow-y: auto;
  }
  .contact {
    padding: 15px 20px;
    border-bottom: 1px solid #128C7E;
    cursor: pointer;
    transition: background-color 0.3s;
  }
  .contact:hover, .contact.active {
    background-color: #128C7E;
  }
  .contact span {
    font-weight: 600;
  }
  .logout-btn {
    background: #25D366;
    border: none;
    padding: 15px;
    font-weight: 600;
    cursor: pointer;
    color: #075E54;
    border-radius: 0 0 12px 12px;
    transition: background-color 0.3s;
  }
  .logout-btn:hover {
    background: #128C7E;
    color: white;
  }
  .chat-area {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    background: #FFF;
  }
  .chat-header {
    padding: 15px 20px;
    background: #128C7E;
    color: white;
    font-weight: 700;
    font-size: 20px;
    border-bottom: 1px solid #075E54;
  }
  .messages {
    flex-grow: 1;
    padding: 20px;
    overflow-y: auto;
    background: #ECE5DD;
  }
  .message {
    max-width: 70%;
    margin-bottom: 15px;
    padding: 10px 15px;
    border-radius: 20px;
    position: relative;
    font-size: 15px;
    line-height: 1.3;
    word-wrap: break-word;
  }
  .message.sent {
    background: #DCF8C6;
    margin-left: auto;
    border-bottom-right-radius: 5px;
  }
  .message.received {
    background: white;
    margin-right: auto;
    border-bottom-left-radius: 5px;
  }
  .timestamp {
    font-size: 11px;
    color: #999;
    margin-top: 5px;
    text-align: right;
  }
  .status {
    font-size: 12px;
    color: #4CAF50;
    position: absolute;
    bottom: 5px;
    right: 10px;
  }
  .input-area {
    display: flex;
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    background: white;
  }
  .input-area textarea {
    flex-grow: 1;
    resize: none;
    border-radius: 20px;
    border: 1.5px solid #ddd;
    padding: 10px 15px;
    font-size: 16px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    outline: none;
    transition: border-color 0.3s;
    height: 45px;
  }
  .input-area textarea:focus {
    border-color: #25D366;
  }
  .input-area button {
    background: #25D366;
    border: none;
    color: white;
    font-weight: 700;
    font-size: 16px;
    padding: 0 20px;
    margin-left: 10px;
    border-radius: 20px;
    cursor: pointer;
    transition: background-color 0.3s;
  }
  .input-area button:hover {
    background: #128C7E;
  }
  /* Scrollbar styling */
  .contacts::-webkit-scrollbar, .messages::-webkit-scrollbar {
    width: 6px;
  }
  .contacts::-webkit-scrollbar-thumb, .messages::-webkit-scrollbar-thumb {
    background-color: rgba(0,0,0,0.1
