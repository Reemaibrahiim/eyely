<?php
if (isset($_GET['error'])) {
  echo '<p style="color:red; text-align:center;">';
  if ($_GET['error'] === 'wrong_user_type') {
    echo "⚠️ You selected the wrong user type.";
  } elseif ($_GET['error'] === 'wrong_password') {
    echo "❌ Incorrect password. Please try again.";
  } elseif ($_GET['error'] === 'user_not_found') {
    echo "⚠️ No account found with that email.";
  }
  echo '</p>';
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Log-In</title>
    <link href="https://fonts.googleapis.com/css2?family=Halant:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
        <style>
        .header {
            background-color: white;
            border-radius: 50px;
            padding: 15px 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 1250px;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: top 0.3s ease;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 50px;
            width: auto;
            max-width: 200px;
            object-fit: contain;
        }
        
        .auth-buttons {
            display: flex;
            gap: 20px;
        }
        
        .auth-btn {
            background: none;
            border: none;
            color: #3498db;
            font-family: 'Halant', serif;
            font-size: 18px;
            font-weight: 500;
            cursor: pointer;
            padding: 8px 0;
            position: relative;
            transition: color 0.3s;
        }
        
        .auth-btn:hover {
            color: #2980b9;
        }
        
        .auth-btn::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: #3498db;
            transition: width 0.3s;
        }
        
        .auth-btn:hover::after {
            width: 100%;
        }
    </style>

</head>
<body>
    
    <div class="login-container">
        <div class="logo-in">
                <a href="index.php"><img src="images/logo.png" alt="Color Vision"></a>
            <p>Log in to continue your journey</p>
        </div>

        <form id="login-form" action="signup_login.php" method="POST">
            <div class="form-group">
                <label for="login-email">Email Address</label>
                <input type="email" name = "emailAddress" id="login-email" placeholder="Enter your email" required>
                <div id="login-email-status" class="email-status"></div>
            </div>
            
            <div class="form-group">
                <label for="login-password">Password</label>
                <div class="password-container">
                    <input type="password" name ="password" id="login-password" placeholder="Enter your password" required>
                  
                </div>
            </div>
            
            <div class="form-group">
                <label for="user-type-login">User Type</label>
                <select id="user-type-login" name="userType" required>
                    <option value="">Select User Type</option>
                    <option value="learner">Learner</option>
                    <option value="educator">Educator</option>
                </select>
            </div>

            <button type="submit" class="btn-login" id="login-btn" name="login" class="btn-login">Log In</button>

            
        </form>

        <div class="additional-links">
            <a href="#">Forgot your password?</a>
        </div>

        <div class="divider">
            <span></span>
            <p>or</p>
            <span></span>
        </div>

        <div class="social-login">
            <div class="social-btn">G</div>
            <div class="social-btn">f</div>
            <div class="social-btn">in</div>
        </div>

        <div class="additional-links" style="margin-top: 25px;">
            <p>Don't have an account? <a href="Sign-up.php">Sign up</a></p>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('login-password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🔒';
        });

        // Email validation function
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        // Show email validation status
        function showEmailStatus(email, statusElement) {
            if (!email) {
                statusElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please enter an email address';
                statusElement.className = 'email-status invalid';
                return false;
            }
            
            if (!validateEmail(email)) {
                statusElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please enter a valid email address';
                statusElement.className = 'email-status invalid';
                return false;
            }
            
            statusElement.innerHTML = '<i class="fas fa-check-circle"></i> Valid email format';
            statusElement.className = 'email-status valid';
            return true;
        }

        // Simple form validation
        
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;
            const userType = document.getElementById('user-type-login').value;
            
            // Validate email
            const isEmailValid = showEmailStatus(email, document.getElementById('login-email-status'));
            if (!isEmailValid) {
                return;
            }
            
            if (!password || !userType) {
                alert('Please fill in all fields');
                return;
            }

            if (password.length < 8) {
                alert('Password must be at least 8 characters long');
                return;
            }
            // Simulate login process
            this.textContent = 'Logging in...';
            this.disabled = true;
            
            document.getElementById('login-form').submit();

        // Clear email status when user starts typing
        document.getElementById('login-email').addEventListener('input', function() {
            document.getElementById('login-email-status').innerHTML = '';
            document.getElementById('login-email-status').className = 'email-status';
        });

        
    </script>
</body>
</html>