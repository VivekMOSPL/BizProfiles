<?php
// Setup pending page - M1
$reason = isset($reason) ? $reason : 'owner_not_activated';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Pending - BizProfile</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif; background: #f3f4f6; color: #1f2937; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #ffffff; padding: 2.5rem; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); width: 100%; max-width: 440px; text-align: center; }
        .logo { text-align: center; margin-bottom: 1.5rem; }
        .logo h1 { font-size: 1.5rem; font-weight: 700; color: #007bff; letter-spacing: -0.5px; }
        h2 { font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; color: #111827; }
        p { color: #4b5563; font-size: 0.875rem; margin-bottom: 1.5rem; line-height: 1.5; }
        .btn-group { display: flex; gap: 0.75rem; justify-content: center; }
        button { flex: 1; padding: 0.7rem; border: none; border-radius: 6px; font-size: 0.95rem; font-weight: 500; cursor: pointer; transition: background 0.2s; }
        button.primary { background: #007bff; color: #fff; }
        button.primary:hover { background: #0056b3; }
        button.secondary { background: #f3f4f6; color: #374151; }
        button.secondary:hover { background: #e5e7eb; }
        .note { font-size: 0.75rem; color: #6b7280; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <h1>BizProfile</h1>
        </div>
        <h2>Setup Pending</h2>
        <p>This website is being set up. Please complete the owner setup process to continue.</p>
        <div class="btn-group">
            <button class="primary" onclick="location.href='/setup'>Start Setup</button>
        </div>
        <p class="note">This site is not indexed by search engines until it is published.</p>
    </div>
</body>
</html>
