<!DOCTYPE html>
<html>
<head>
    <title>School Management System Login</title>
    <link rel="stylesheet" href="css/index.css">
     <script src="js/index.js"></script>
     <style type="text/css">
            body {
                background-image: url("img/backg.jpg");               
            }

        </style>
</head>
<body>
      
    <div class="container">
       <h1>School Management System</h1>
        <form method="post" action="login.php">
            <label for="username">Username/Email:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Login</button>
            <p class="forgot-password" onclick="showPopup()">Forgot password?</p>
        </form>
    </div>
    <div class="popup-box" id="popup">
        <div class="popup-box-content">
            <span class="close" onclick="hidePopup()">&times;</span>
            <h2>Forgot Password</h2>
            <p>Enter your email address below and we'll send you a link to reset your password.</p>
            <form method="post">
                <label for="email">Email address:</label>
                <input type="email" id="email" name="email" required>
                <button type="submit">Submit</button>
            </form>
        </div>
    </div>
   
</body>
</html>