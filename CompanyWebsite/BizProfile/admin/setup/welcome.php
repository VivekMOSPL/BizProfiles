<?php
// Setup welcome page - M1
$csrf = isset($csrf_token) ? $csrf_token : (function_exists('csrf_field') ? csrf_field() : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - BizProfile</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif; background: #f3f4f6; color: #1f2937; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #ffffff; padding: 2.5rem; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); width: 100%; max-width: 440px; }
        .logo { text-align: center; margin-bottom: 1.5rem; }
        .logo h1 { font-size: 1.5rem; font-weight: 700; color: #007bff; letter-spacing: -0.5px; }
        .logo p { color: #6b7280; font-size: 0.875rem; margin-top: 0.25rem; }
        h2 { font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; color: #111827; }
        p { color: #4b5563; font-size: 0.875rem; margin-bottom: 1.5rem; line-height: 1.5; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.35rem; }
        input { width: 100%; padding: 0.65rem 0.85rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; transition: border-color 0.2s; }
        input:focus { outline: none; border-color: #007bff; ring-color: rgba(0, 123, 255, 0.3); }
        button { width: 100%; padding: 0.7rem; background: #007bff; color: #fff; border: none; border-radius: 6px; font-size: 0.95rem; font-weight: 500; cursor: pointer; transition: background 0.2s; }
        button:hover { background: #0056b3; }
        button:disabled { background: #9ca3af; cursor: not-allowed; }
        .error { color: #dc2626; font-size: 0.8rem; margin-bottom: 1rem; background: #fef2f2; padding: 0.6rem; border-radius: 6px; border: 1px solid #fecaca; }
        .note { font-size: 0.75rem; color: #6b7280; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <h1>BizProfile</h1>
            <p>Business Website Setup</p>
        </div>
        <h2>Welcome</h2>
        <p>Set up your business website. You will need the owner email provisioned by your deployer to continue.</p>
        <form method="POST" action="/setup/identify">
            <?php echo $csrf; ?>
            <div class="form-group">
                <label for="name">Your Name</label>
                <input type="text" name="name" id="name" required placeholder="John Doe">
            </div>
            <div class="form-group">
                <label for="business_name">Business Name</label>
                <input type="text" name="business_name" id="business_name" required placeholder="Acme Corporation">
            </div>
            <div class="form-group">
                <label for="email">Owner Email</label>
                <input type="email" name="email" id="email" required placeholder="owner@yourcompany.com">
            </div>
            <div class="form-group">
                <label for="mobile">Mobile Number</label>
                <input type="tel" name="mobile" id="mobile" placeholder="+1 234 567 8900">
            </div>
            <button type="submit">Continue</button>
        </form>
        <p class="note">Owner email must match the provisioned address. Setup data remains private until you verify via OTP.</p>
    </div>
</body>
</html>
