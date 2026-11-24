<?php
// Thank you page after questionnaire submission
$questionnaire = $questionnaire ?? [];
$responseId = $responseId ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - BizProfile</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #1f2937; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #ffffff; padding: 3rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: center; max-width: 500px; width: 100%; }
        h1 { color: #15803d; font-size: 2rem; margin-bottom: 0.5rem; }
        p { color: #6b7280; margin-bottom: 2rem; line-height: 1.6; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #007bff; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; }
        .btn:hover { background: #0056b3; }
        .response-id { display: inline-block; background: #f3f4f6; color: #374151; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.9rem; margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Thank You!</h1>
        <p>Your questionnaire response has been submitted successfully.</p>
        <div class="response-id">Response ID: #<?php echo htmlspecialchars($responseId); ?></div>
        <a href="/" class="btn">Return to Home</a>
    </div>
</body>
</html>
