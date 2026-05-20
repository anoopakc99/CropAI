<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crop AI - Login</title>
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('logo/crop.png') }}">
    <link rel="stylesheet" href="{{ asset('assets-css/auth/style.css') }}">
<style>
    body {
        margin: 0;
        font-family: 'Poppins', sans-serif;
        background: #fdfdfd;
        position: relative;
        min-height: 100vh;
        overflow-x: hidden;
    }

    /* ✅ Background image behind everything */
    body::after {
        content: "";
        background: url("{{ asset('assets/img/login-bg.png') }}") no-repeat center bottom;
        background-size: cover;
        opacity: 0.9;
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 60vh;
        z-index: 0;
    }

    /* ✅ Curved banner visible at top */
    .curved-banner {
        background-color: #5a750f;
        height: 50px;
        
        position: relative;
        z-index: 3; /* keep it above the background */
    }

    /* ✅ Enlarged login form */
    .login-container {
        position: relative;
        z-index: 2;
        max-width: 520px;
        margin: 60px auto;
        background: white;
        padding: 50px 40px;
        border-radius: 12px;
        box-shadow: 0 4px 25px rgba(0, 0, 0, 0.15);
        text-align: center;
        transition: all 0.3s ease;
    }

    .login-logo {
        height: 80px;
        margin-bottom: 15px;
    }

    h1 {
        font-size: 22px;
        margin-bottom: 25px;
        color: #222;
    }

    .form-group {
        text-align: left;
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-weight: 500;
        margin-bottom: 8px;
        font-size: 15px;
    }

    .form-group input {
        width: 100%;
        padding: 14px 12px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 15px;
    }

    .password-field {
        position: relative;
    }

    .password-toggle {
        position: absolute;
        top: 50%;
        right: 12px;
        transform: translateY(-50%);
        cursor: pointer;
        font-size: 18px;
        user-select: none;
    }

    .remember-container {
        display: flex;
        align-items: center;
        font-size: 14px;
        margin: 15px 0 25px;
    }

    .remember-container input[type="checkbox"] {
        margin: -8px 8px 0 0;
        vertical-align: middle;
    }

    .btn-login {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #667E06 0%, #7a9108 100%);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 17px;
        cursor: pointer;
        transition: background 0.3s;
    }

    .btn-login:hover {
        background: #546506;
    }

    .error-message {
        color: red;
        margin-bottom: 15px;
        font-size: 14px;
    }
</style>


</head>
<body>

    <!--<div class="top-header"></div>-->
    <!--<div class="nav-container">-->
    <!--    <img src="{{ asset('logo/crop.png') }}" alt="CropNet Logo" class="logo">-->
    <!--</div>-->
    <div class="curved-banner" style="height:40px"></div>

    <div class="login-container">
        <img src="{{ asset('logo/crop.png') }}" alt="CropNet Logo" class="login-logo">
        <h1>Login to Your Account</h1>

        @if(session('error'))
            <div class="error-message">
                {{ session('error') }}
            </div>
        @endif

        <form id="loginForm" action="{{ route('admins.dashboard') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="username">User Email</label>
                <input type="text" id="username" name="email" placeholder="User Email"
                       value="" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" placeholder="Enter Your Password"
                           value="" required>
                    <span class="password-toggle">👁️</span>
                </div>
            </div>

            <div class="remember-container">
    <input type="checkbox" id="remember" name="remember"
           >
    <label for="remember">Remember Me</label>
</div>


            <button type="submit" class="btn-login">Log In</button>
        </form>
    </div>

    <script>
        document.querySelector('.password-toggle').addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
        });
    </script>

</body>
</html>
