<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset for Wooing</title>
</head>
<body style="font-family: 'Arial', sans-serif;">

    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">

        <h2 style="color: #333; text-align: center;">Password Reset for Wooing</h2>

        <p>Dear {{ $name }},</p>

        <p>We received a request to reset your password. Your new temporary password is: <strong>{{ $password }}</strong></p>

        <p>For security reasons, we recommend changing this password immediately after logging in. If you didn't request a password reset, please ignore this email.</p>

        <p>Thank you for choosing Wooing! If you have any questions or need assistance, feel free to reach out to our support team.</p>

        <p>Best regards,<br>
            The Wooing Team</p>

    </div>

</body>
</html>
