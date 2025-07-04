<!DOCTYPE html>
<html>
<head>
    <title>Password Reset Request</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f44336; color: white; padding: 10px; text-align: center; }
        .content { padding: 20px; }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer { margin-top: 20px; font-size: 12px; text-align: center; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset Request</h1>
        </div>

        <div class="content">
            <p>Hello,</p>
            <p>We received a request to reset your password. Click the button below to reset it:</p>

            <a href="{{ $resetUrl }}" class="button">Reset Password</a>

            <p>This password reset link will expire in 60 minutes.</p>
            <p>If you did not request a password reset, please ignore this email.</p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Task Scheduler. All rights reserved.
        </div>
    </div>
</body>
</html>
