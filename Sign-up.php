<?php
if (isset($_GET['error']) && $_GET['error'] === 'email_exists') {
    echo '<p style="color:red; text-align:center; margin-top:10px;">
            ⚠ This email is already registered. Please log in instead.
          </p>';
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Sign-Up</title>
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
    
    <div class="signup-container">
        <div class="logo-in">
            <img src="images/logo.png" alt="">
            <p>Create your account</p>
        </div>

        <div class="progress-bar">
            <div class="progress" id="signup-progress"></div>
        </div>

        <!-- User Type Selection -->
        <div id="user-type-selection">
            <div class="form-group">
                <label for="user-type">I am a:</label>
                <select id="user-type" required>
                    <option value="">Select account type</option>
                    <option value="learner">Learner</option>
                    <option value="educator">Educator</option>
                </select>
            </div>
            
            
            

            <button type="button" class="btn-signup" id="select-user-type-btn">Continue</button>
        </div>

        <!-- Learner Form -->
        <form id="learner-form" class="hidden" action="signup_login.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="userType" value="learner">

            <div class="form-group">
                <label for="learner-first-name">First Name</label>
                <input type="text" name = "firstName" id="learner-first-name" placeholder="Enter your first name" required>
            </div>
            
            <div class="form-group">
                <label for="learner-last-name">Last Name</label>
                <input type="text" name = "lastName" id="learner-last-name" placeholder="Enter your last name" required>
            </div>
            
            <div class="form-group">
                <label for="learner-profile-image">Profile Image (Optional)</label>
                <input type="file" name = "photoFile" id="learner-profile-image" accept="image/*">
                <img id="learner-image-preview" class="profile-image-preview hidden" src="#" alt="Profile Preview">
            </div>
            
            <div class="form-group">
                <label for="learner-email">Email Address</label>
                <input type="email" name = "emailAddress" id="learner-email" placeholder="Enter your email" required>
                <div id="learner-email-status" class="email-status"></div>
            </div>
            
            <div class="form-group">
                <label for="learner-password">Password</label>
                <div class="password-container">
                    <input type="password" name = "password" id="learner-password" placeholder="Create a password" required>
                </div>
                <div class="password-requirements">
                    Must be at least 8 characters with a number and symbol
                </div>
            </div>

            <button type="submit" class="btn-signup" id="learner-signup-btn" name="signup" class="btn-signup">Create Account</button>   
        </form>

        <!-- Educator Form -->
        <form id="educator-form" class="hidden" action="signup_login.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="userType" value="educator">
            
            <div class="form-group">
                <label for="educator-first-name">First Name</label>
                <input type="text" name = "firstName" id="educator-first-name" placeholder="Enter your first name" required>
            </div>
            
            <div class="form-group">
                <label for="educator-last-name">Last Name</label>
                <input type="text" name = "lastName" id="educator-last-name" placeholder="Enter your last name" required>
            </div>
            
            <div class="form-group">
                <label for="educator-profile-image">Profile Image (Optional)</label>
                <input type="file" name = "photoFile" id="educator-profile-image" accept="image/*">
                <img id="educator-image-preview" class="profile-image-preview hidden" src="#" alt="Profile Preview">
            </div>
            
            <div class="form-group">
                <label for="educator-email">Email Address</label>
                <input type="email" name = "emailAddress" id="educator-email" placeholder="Enter your email" required>
                <div id="educator-email-status" class="email-status"></div>
            </div>
            
            <div class="form-group">
                <label for="educator-password">Password</label>
                <div class="password-container">
                    <input type="password" name = "password" id="educator-password" placeholder="Create a password" required>
                </div>
                <div class="password-requirements">
                    Must be at least 8 characters with a number and symbol
                </div>
            </div>
            
<div class="form-group">
    <label>Specialized Topics (Select at least one)</label>
    <div class="topics-container">
        <div class="topic-checkbox">
            <input type="checkbox" name="topics[]" id="topic-protanopia" value="1">
            <label for="topic-protanopia">Protanopia</label>
        </div>
        <div class="topic-checkbox">
            <input type="checkbox" name="topics[]" id="topic-protanomaly" value="2">
            <label for="topic-protanomaly">Protanomaly</label>
        </div>
        <div class="topic-checkbox">
            <input type="checkbox" name="topics[]" id="topic-deuteranopia" value="3">
            <label for="topic-deuteranopia">Deuteranopia</label>
        </div>
        <div class="topic-checkbox">
            <input type="checkbox" name="topics[]" id="topic-deuteranomaly" value="4">
            <label for="topic-deuteranomaly">Deuteranomaly</label>
        </div>
        <div class="topic-checkbox">
            <input type="checkbox" name="topics[]" id="topic-tritanopia" value="5">
            <label for="topic-tritanopia">Tritanopia</label>
        </div>
        <div class="topic-checkbox">
            <input type="checkbox" name="topics[]" id="topic-tritanomaly" value="6">
            <label for="topic-tritanomaly">Tritanomaly</label>
        </div>
        <div class="topic-checkbox">
            <input type="checkbox" name="topics[]" id="topic-rod-monochromacy" value="7">
            <label for="topic-rod-monochromacy">Rod Monochromacy</label>
        </div>
        <div class="topic-checkbox">
            <input type="checkbox" name="topics[]" id="topic-cone-monochromacy" value="8">
            <label for="topic-cone-monochromacy">Cone Monochromacy</label>
        </div>
    </div>
</div>
            
            <button type="submit" class="btn-signup" id="educator-signup-btn" name="signup" class="btn-signup">Create Account</button>

           
        </form>

        <div class="terms">
            By creating an account, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
        </div>

        <div class="login-link">
            Already have an account? <a href="login.php">Log in</a>
        </div>
    </div>

    <script>
        // DOM Elements
        const userTypeSelection = document.getElementById('user-type-selection');
        const learnerForm = document.getElementById('learner-form');
        const educatorForm = document.getElementById('educator-form');
        const userTypeSelect = document.getElementById('user-type');
        const selectUserTypeBtn = document.getElementById('select-user-type-btn');
        const learnerSignupBtn = document.getElementById('learner-signup-btn');
        const educatorSignupBtn = document.getElementById('educator-signup-btn');
        const learnerEmailStatus = document.getElementById('learner-email-status');
        const educatorEmailStatus = document.getElementById('educator-email-status');
        
        // Profile image preview functionality
        function setupImagePreview(inputId, previewId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            
            input.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.classList.remove('hidden');
                    }
                    reader.readAsDataURL(file);
                } else {
                    preview.src = "#";
                    preview.classList.add('hidden');
                }
            });
        }
        
        setupImagePreview('learner-profile-image', 'learner-image-preview');
        setupImagePreview('educator-profile-image', 'educator-image-preview');
        
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
        
        // User type selection
        selectUserTypeBtn.addEventListener('click', function() {
            const userType = userTypeSelect.value;
            
            if (!userType) {
                alert('Please select an account type');
                return;
            }
            
            // Hide selection and show appropriate form
            userTypeSelection.classList.add('hidden');
            
            if (userType === 'learner') {
                learnerForm.classList.remove('hidden');
            } else {
                educatorForm.classList.remove('hidden');
            }
            
            // Update progress bar
            document.getElementById('signup-progress').style.width = '66.66%';
        });
        
        // Form validation and submission for learner
        learnerSignupBtn.addEventListener('click', function() {
            const firstName = document.getElementById('learner-first-name').value;
            const lastName = document.getElementById('learner-last-name').value;
            const email = document.getElementById('learner-email').value;
            const password = document.getElementById('learner-password').value;
            
            // Validate email format
            const isEmailValid = showEmailStatus(email, learnerEmailStatus);
            if (!isEmailValid) {
                return;
            }
            
            // Basic validation
            if (!firstName || !lastName || !password) {
                alert('Please fill in all required fields');
                return;
            }
            
            if (password.length < 8) {
                alert('Password must be at least 8 characters long');
                return;
            }
            
            
        });
        
        // Form validation and submission for educator
        educatorSignupBtn.addEventListener('click', function() {
            const firstName = document.getElementById('educator-first-name').value;
            const lastName = document.getElementById('educator-last-name').value;
            const email = document.getElementById('educator-email').value;
            const password = document.getElementById('educator-password').value;
            const topics = document.querySelectorAll('input[name="topics[]"]:checked');

            
            // Validate email format
            const isEmailValid = showEmailStatus(email, educatorEmailStatus);
            if (!isEmailValid) {
                return;
            }
            
            // Basic validation
            if (!firstName || !lastName || !password) {
                alert('Please fill in all required fields');
                return;
            }
            
            if (password.length < 8) {
                alert('Password must be at least 8 characters long');
                return;
            }
            
            if (topics.length === 0) {
                alert('Please select at least one specialized topic');
                return;
            }
        
        });
        

        // Add subtle animation to form inputs on focus
        const inputs = document.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', () => {
                input.parentElement.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>