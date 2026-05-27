<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>INMACOM MIS — Invitation</title>
    <style>
        body { font-family: sans-serif; color: #1a1a2e; background: #f4f4f9; margin: 0; padding: 0; }
        .container { max-width: 520px; margin: 40px auto; background: #fff; border-radius: 12px; padding: 40px; }
        .heading { font-size: 22px; font-weight: 700; margin-bottom: 8px; }
        .sub { color: #555; margin-bottom: 28px; }
        .badge { display: inline-block; background: #e0f2fe; color: #0369a1; border-radius: 6px; padding: 4px 12px; font-size: 13px; font-weight: 600; margin-bottom: 28px; }
        .pin-box { background: #f0fdf4; border: 1.5px dashed #16a34a; border-radius: 8px; padding: 16px 20px; margin-bottom: 20px; font-size: 28px; letter-spacing: 6px; font-family: monospace; color: #166534; font-weight: 700; }
        .btn { display: inline-block; background: #1d4ed8; color: #fff; text-decoration: none; border-radius: 8px; padding: 12px 28px; font-size: 15px; font-weight: 600; margin-bottom: 20px; }
        .warning { background: #fef9c3; border-left: 4px solid #ca8a04; border-radius: 6px; padding: 12px 16px; font-size: 13px; color: #713f12; margin-bottom: 24px; }
        .footer { font-size: 12px; color: #999; margin-top: 32px; }
    </style>
</head>
<body>
<div class="container">
    <div class="heading">You're invited to INMACOM MIS</div>
    <p class="sub">An administrator has invited you to join the Incomati Basin Water Resources Management Information System.</p>

    <div class="badge">Role: {{ $roleLabel }}</div>

    <p>Your one-time registration code:</p>
    <div class="pin-box">{{ $pin }}</div>

    <p>Or use this link to go directly to your registration page:</p>
    <a href="{{ $inviteUrl }}" class="btn">Accept Invitation</a>

    <div class="warning">
        <strong>Important:</strong> This link expires after the first click. Do not share it or open it yourself before the recipient does.
    </div>

    <p>If the button doesn't work, copy and paste this URL into your browser:</p>
    <p style="word-break:break-all; font-size: 13px; color: #555;">{{ $inviteUrl }}</p>

    <div class="footer">If you did not expect this invitation, you can safely ignore this email.</div>
</div>
</body>
</html>
