<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: chat.php");
    exit;
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>WhatsApp Clone - Signup</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #00b09b, #96c93d);
    margin: 0; padding: 0; height: 100vh;
    display: flex; justify-content: center; align-items: center;
  }
  .signup-container {
    background: white;
    padding: 30px 40px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    width: 320px;
  }
  h2 {
    margin-bottom: 20px;
    color: #075E54;
    text-align: center;
  }
  input[type="text"], input[type="password"] {
    width: 100%;
    padding: 12px 15px;
    margin: 10px 0 20px 0;
    border: 1.5px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s;
  }
  input[type="text"]:focus, input[type="password"]:focus {
    border-color: #25D366;
    outline: none;
  }
  button {
    width: 100%;
    background-color: #25D366;
    border: none;
    padding: 14px;
    color: white;
    font-weight: 600;
    font-size: 16px;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s;
  }
  button:hover {
    background-color: #128C7E;
  }
  .login-link {
    margin-top: 15px;
    text-align: center;
    font-size: 14px;
  }
  .login-link a {
    color: #128C7E;
    text-decoration: none;
    font-weight: 600;
  }
  .error {
    color: #e74c3c;
    font-size: 14px;
    margin-bottom: 10px;
    text-align: center;
  }
</style>
</head>
<body>
<div class="signup-container">
  <h2>Create Account</h2>
  <div id="error" class="error"></div>
  <input type="text" id="username" placeholder="Choose a username" autocomplete="off" />
  <input type="password" id="password" placeholder="Choose a password" autocomplete="off" />
  <button onclick="signup()">Sign Up</button>
  <div class="login-link">
    Already have an account? <a href="#" onclick="redirectLogin()">Login</a>
  </div>
</div>
 
<script>
function redirectLogin() {
  window.location.href = 'index.php';
}
 
function signup() {
  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value.trim();
  const errorDiv = document.getElementById('error');
  errorDiv.textContent = '';
 
  if (!username || !password) {
    errorDiv.textContent = 'Please enter username and password.';
    return;
  }
  if (username.length < 3) {
    errorDiv.textContent = 'Username must be at least 3 characters.';
    return;
  }
  if (password.length < 5) {
    errorDiv.textContent = 'Password must be at least 5 characters.';
    return;
  }
 
  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'signup_process.php', true);
  xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
  xhr.onload = function() {
    if (this.status === 200) {
      const res = JSON.parse(this.responseText);
      if (res.success) {
        alert('Signup successful! Please login.');
        window.location.href = 'index.php';
      } else {
        errorDiv.textContent = res.message;
      }
    } else {
      errorDiv.textContent = 'Server error. Try again later.';
    }
  };
  xhr.send('username=' + encodeURIComponent(username) + '&password=' + encodeURIComponent(password));
}
</script>
</body>
</html>
