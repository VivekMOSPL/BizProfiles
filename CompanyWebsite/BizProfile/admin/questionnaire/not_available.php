<?php
// Not available page
$message = $message ?? 'No questionnaire is currently available.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questionnaire Not Available - BizProfile</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #1f2937; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #ffffff; padding: 3rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: center; max-width: 500px; width: 100%; }
        h1 { color: #dc2626; font-size: 1.75rem; margin-bottom: 0.5rem; }
        p { color: #6b7280; margin-bottom: 2rem; line-height: 1.6; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #007bff; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Not Available</h1>
        <p><?php echo htmlspecialchars($message); ?></p>
        <a href="/" class="btn">Return to Home</a>
    </div>
</body>
</html>
