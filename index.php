<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Positioned Image</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: white;
            font-family: 'Halant', serif;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        .image-container {
            position: absolute;
            top: 0px;
            left: 0px;
            width: 1500px;
            height: auto;
            z-index: -1;
        }

        .image-container img {
            display: block;
            max-width: 100%;
            height: auto;
        }

        /* Header Styles */
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
        
        /* Background image */        
        .background-image {
            position: absolute;
            top: 35%;
            right: -885px;
            transform: translateY(-50%);
            width: 150%;
            height: 150%;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .background-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }

        /* Main Content */
        .content {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding-top: 250px;
            max-width: 700px;
            margin-left: 120px;
            position: relative;
            z-index: 2;
        }

        h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            color: white;
            line-height: 1.2;
            
        }

        .subtitle {
            font-size: 2rem;
            margin-bottom: 30px;
            color: white;
            line-height: 1.5;
            
        }

        /* GET START BUTTON - Fixed styles */
        #get-start {
            background: linear-gradient(45deg, white, #e4e2e2);
            color: #007ACD;
            border: none;
            padding: 18px 80px;
            border-radius: 50px;
            font-family: 'Halant', serif;
            font-size: 1.2rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: inline-block;
        }

        #get-start:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
        }

        #get-start:active {
            transform: translateY(0);
        }

        /* Statistics Section */
        .statistics {
            position: relative;
            z-index: 2;
            margin: 100px 0 50px 120px;
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            max-width: 1200px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 5px;
            width: 350px;
            box-shadow: 0 10px 30px #7a56c735;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            margin-left: 30px;
            margin-top: 150px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .stat-image {
            width: 70%;
            height: 200px;
            border-radius: 12px;
            margin-bottom: 20px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 16px;
        }
        
        #stat-image-circle26 {
            width: 50%;
            height: 150px;
            object-fit: cover;
        }

        .stat-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 500;
            color: #6554a9;
            margin-top: 25px;
        }

        .stat-text {
            font-size: 1.3rem;
            color: #6554a9;
            line-height: 1;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .content {
                margin-left: 80px;
                max-width: 600px;
            }
            
            .statistics {
                margin-left: 80px;
            }
        }

        @media (max-width: 992px) {
            .content {
                margin-left: 60px;
                max-width: 500px;
            }
            
            .statistics {
                margin-left: 60px;
                justify-content: center;
            }
            
            h1 {
                font-size: 2.5rem;
            }
            
            .subtitle {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 12px 20px;
                border-radius: 30px;
            }
            
            .logo img {
                height: 40px;
            }
            
            .auth-btn {
                font-size: 16px;
            }
            
            .content {
                padding-top: 150px;
                margin: 0 auto;
                align-items: center;
                text-align: center;
                max-width: 90%;
            }
            
            .statistics {
                margin: 80px auto 50px;
                justify-content: center;
            }
            
            h1 {
                font-size: 2.2rem;
            }
            
            .subtitle {
                font-size: 1.4rem;
            }
            
            .stat-card {
                margin-left: 0;
                margin-top: 30px;
                width: 300px;
            }
            
            #get-start {
                padding: 15px 60px;
                font-size: 1.1rem;
            }
        }

        @media (max-width: 480px) {
            .auth-buttons {
                gap: 15px;
            }
            
            .logo img {
                height: 35px;
            }
            
            .auth-btn {
                font-size: 14px;
            }
            
            .stat-card {
                width: 280px;
                padding: 15px;
            }
            
            .stat-number {
                font-size: 1.8rem;
            }
            
            .stat-text {
                font-size: 1.1rem;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .subtitle {
                font-size: 1.2rem;
            }
            
            #get-start {
                padding: 15px 50px;
                font-size: 1.1rem;
            }
        }

    </style>
        
</head>
<body>
  <div class="image-container">
        <img src="images/c.png" alt="Positioned Image">
    </div>

     <!-- Header -->
        <header class="header">
            <div class="logo">
                <div class="logo-icon"></div>
                <img src="images/logo.png" alt="">
            </div>
            <div class="auth-buttons">
                <a href="login.php"><button class="auth-btn">log in</button></a>
                <a href="Sign-up.php"><button class="auth-btn">sign up</button></a>
            </div>
        </header>

    <!-- background image -->
    <div class="background-image">
        <img src="images/p.svg" alt="Background Image">
    </div>

     <!-- Main Content -->
        <div class="content">
            <h1>Can you really see all the colors? <br>Find out now!</h1>
            <p class="subtitle">A quick and fun test to measure your<br>ability to distinguish colors.</p>
            <a href="login.php"><button id="get-start">Get Start</button></a>
        </div>
    
    <!-- Statistics Section -->
    <div class="statistics">
        <div class="stat-card">
            <div class="stat-number">1 in 12</div>
            <div class="stat-text">Men Are Color Blind</div>

            <div class="stat-image">
                <img src="images/person.png" alt="1 in 12 Men Are Color Blind">
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number">Only 11</div>
            <div class="stat-text">States Test Kids For Color Blindness</div>

            <div class="stat-image">
                <img id="stat-image-circle26" src="images/circle26.png" alt="Only 11 States Test Kids For Color Blindness">
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number">1 in 200</div>
            <div class="stat-text">Women Are Color Blind</div>

            <div class="stat-image">
                <img src="images/people.png" alt="1 in 200 Women Are Color Blind">
            </div>
        </div>
    </div>

     

    
</body>

</html>