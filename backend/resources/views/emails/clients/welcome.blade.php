<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to {{ $appName }}</title>
</head>
<body>
<p>Hi {{ $client->name ?? 'there' }},</p>
<p>Welcome to {{ $appName }}! We're excited to start working together. If you have any questions, just reply to this message.</p>
<p>Best regards,<br>{{ $appName }} Team</p>
</body>
</html>
