<?php
// Minimum UI restoration (M0 subtask) — admin login view.
// Full authentication functionality is M1 scope; this view only restores a
// renderable template so /admin/login no longer fatals on a missing template.
$csrf = function_exists('csrf_field') ? csrf_field() : '';
$error = isset($_SESSION['error']) ? htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - BizProfile</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; color: #222; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); width: 100%; max-width: 400px; }
        h1 { margin: 0 0 1.5rem; font-size: 1.25rem; color: #007bff; }
        label { display: block; font-size: 0.85rem; margin-bottom: 0.35rem; color: #555; }
        input { width: 100%; padding: 0.6rem; margin-bottom: 1rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 0.6rem; background: #007bff; color: #fff; border: none; border-radius: 4px; font-size: 1rem; cursor: pointer; }
        button:hover { background: #0056b3; }
        .error { color: #c00; font-size: 0.85rem; margin-bottom: 1rem; }
        .note { font-size: 0.75rem; color: #888; margin-top: 1rem; border-top: 1px solid #eee; padding-top: 0.75rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Admin Login</h1>
        <?php if ($error): ?><p class="error"><?php echo $error; ?></p><?php endif; ?>
        <form method="POST" action="/admin/login">
            <?php echo $csrf; ?>
            <label for="email">Email</label>
            <input type="email" name="email" id="email" required placeholder="you@example.com">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>
            <button type="submit">Sign in</button>
        </form>
        <p class="note">Minimum UI restoration (M0). Full authentication flows are M1 scope.</p>
    </div>
</body>
</html>
