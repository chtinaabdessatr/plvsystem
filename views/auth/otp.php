<!DOCTYPE html>
<html>
<head>

<style>
    /* Reset basic spacing and ensure padding is included in width calculations */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

/* Body Styling: Centers the box vertically and horizontally */
body.login-page {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f0f2f5; /* Light grey background */
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* The Login Container Card */
.login-box {
    background-color: #ffffff;
    padding: 40px;
    width: 100%;
    max-width: 400px; /* Prevents it from getting too wide on large screens */
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); /* Soft shadow for depth */
}

/* Header Styling */
.login-box h2 {
    text-align: center;
    color: #333;
    margin-bottom: 30px;
    font-size: 24px;
    font-weight: 600;
}

/* Form Input Styling */
.login-box input {
    width: 100%;
    padding: 12px 15px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

/* Input Focus Effect */
.login-box input:focus {
    outline: none;
    border-color: #007bff; /* Highlights blue when clicked */
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.2);
}

/* Button Styling */
.btn {
    width: 100%;
    padding: 12px;
    background-color: #007bff; /* Primary Blue */
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

/* Button Hover Effect */
.btn:hover {
    background-color: #0056b3; /* Darker blue on hover */
}
    </style>
</head>
<body class="login-page">
    <div class="login-box">
        <h2>Verify OTP</h2>
        <p>Use code: <strong>123456</strong></p>
        <form method="POST" action="/plvsystem/auth/verifyOtp">
            <input type="text" name="otp_code" placeholder="Enter Code" required>
            <button type="submit" class="btn">Verify</button>
        </form>
    </div>
</body>
</html>