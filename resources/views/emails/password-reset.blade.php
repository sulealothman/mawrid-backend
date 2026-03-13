<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password Reset</title>
</head>
<body>
    <h2>Password Reset Request</h2>

    <p>Hello,</p>
    <p>You requested to reset your password. Click the link below to set a new password:</p>

    <p>
        <a href="{{ $resetUrl }}" style="display:inline-block;padding:10px 20px;background:#3490dc;color:#fff;text-decoration:none;border-radius:5px;">
            Reset Password
        </a>
    </p>

    <p>If you did not request this, please ignore this email.</p>
    <p>Thanks,<br> {{ config('app.name') }}</p>
</body>
</html>
