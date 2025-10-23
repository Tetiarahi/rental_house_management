<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
        }

        body {
            background: linear-gradient(to right,rgb(196, 11, 11),rgb(0, 45, 241));
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background-color: white;
            padding: 2.5rem 3rem;
            border-radius: 1rem;
            box-shadow: 0 8px 24px rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-container img.logo {
            width: 80px;
            margin-bottom: 1rem;
        }

        .login-container h2 {
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            color:rgb(1, 0, 10);
        }

        .form-group {
            margin-bottom: 1.2rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border 0.3s;
        }

        .form-group input:focus {
            border-color: #26a69a;
            outline: none;
        }

        .remember-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1rem;
            font-size: 0.95rem;
            color: #333;
        }

        .login-btn {
            width: 100%;
            padding: 0.9rem;
            background-color:rgb(4, 27, 235);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            position: relative;
        }

        .login-btn:hover {
            background-color:rgb(255, 1, 1);
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-left: 8px;
            vertical-align: middle;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .alert-danger {
            background: #ffcdd2;
            border: 1px solid #e53935;
            color: #b71c1c;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="login-container">
    <img src="assets/logo/logo1.png" alt="Logo" class="logo"> <!-- Replace with your own logo -->
    <h2>Room Rental Management System</h2>
    <form id="login-form">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autocomplete="username">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <div class="remember-group">
            <input type="checkbox" id="remember" name="remember">
            <label for="remember">Remember me</label>
        </div>
        <button type="button" class="login-btn">
            Login
        </button>
    </form>
</div>

<script>
    $('#login-form').submit(function(e){
        e.preventDefault()
        let btn = $('#login-form button[type="button"]');
        btn.attr('disabled', true).html('Logging in <span class="spinner"></span>');
        if($(this).find('.alert-danger').length > 0 )
            $(this).find('.alert-danger').remove();

        $.ajax({
            url:'ajax.php?action=login',
            method:'POST',
            data:$(this).serialize(),
            error:err=>{
                console.log(err)
                btn.removeAttr('disabled').html('Login');
            },
            success:function(resp){
                if(resp == 1){
                    location.href ='index.php?page=home';
                }else{
                    $('#login-form').prepend('<div class="alert alert-danger">Username or password is incorrect.</div>')
                    btn.removeAttr('disabled').html('Login');
                }
            }
        })
    })

    // Trigger submit on button click
    $('.login-btn').on('click', function () {
        $('#login-form').submit();
    });
</script>

</body>
</html>
