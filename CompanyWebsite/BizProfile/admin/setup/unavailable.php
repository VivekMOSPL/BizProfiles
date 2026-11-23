<?php
// Setup unavailable page - M1 (schema missing or not provisioned)
$reason = isset($reason) ? $reason : 'schema_missing';
$message = isset($message) ? $message : 'Database schema is not initialized.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Unavailable - BizProfile</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif; background: #f3f4f6; color: #1f2937; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #ffffff; padding: 2.5rem; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); width: 100%; max-width: 440px; text-align: center; }
        .logo { text-align: center; margin-bottom: 1.5rem; }
        .logo h1 { font-size: 1.5rem; font-weight: 700; color: #dc2626; letter-spacing: -0.5px; }
        h2 { font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; color: #111827; }
        p { color: #4b5563; font-size: 0.875rem; margin-bottom: 1.5rem; line-height: 1.5; }
        code { background: #f3f4f6; padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.85rem; color: #dc2626; }
        .note { font-size: 0.75rem; color: #6b7280; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <h1>BizProfile</h1>
        </div>
        <h2>Setup Unavailable</h2>
        <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php if ($reason === 'schema_missing'): ?>
        <p>Run the CLI installer to initialize the database schema:</p>
        <code>php bin/bizprofile owner:inspect</code>
        <?php elseif ($reason === 'owner_not_provisioned'): ?>
        <p>Contact your deployer to provision the owner email:</p>
        <code>php bin/bizprofile owner:provision --email=owner@example.com</code>
        <?php endif; ?>
        <p class="note">This page is not indexed by search engines.</p>
    </div>
</body>
</html>
