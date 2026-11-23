<?php
// Setup OTP page - M1
$csrf = isset($csrf_token) ? $csrf_token : (function_exists('csrf_field') ? csrf_field() : '');
$step = isset($step) ? $step : 'send';
$email = isset($email) ? $email : '';
$error = isset($error) ? $error : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification - BizProfile</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif; background: #f3f4f6; color: #1f2937; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #ffffff; padding: 2.5rem; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); width: 100%; max-width: 440px; }
        .logo { text-align: center; margin-bottom: 1.5rem; }
        .logo h1 { font-size: 1.5rem; font-weight: 700; color: #007bff; letter-spacing: -0.5px; }
        h2 { font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; color: #111827; }
        p { color: #4b5563; font-size: 0.875rem; margin-bottom: 1.5rem; line-height: 1.5; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.35rem; }
        input { width: 100%; padding: 0.65rem 0.85rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; transition: border-color 0.2s; }
        input:focus { outline: none; border-color: #007bff; }
        button { width: 100%; padding: 0.7rem; background: #007bff; color: #fff; border: none; border-radius: 6px; font-size: 0.95rem; font-weight: 500; cursor: pointer; transition: background 0.2s; }
        button:hover { background: #0056b3; }
        button.secondary { background: #f3f4f6; color: #374151; margin-top: 0.75rem; }
        button.secondary:hover { background: #e5e7eb; }
        .error { color: #dc2626; font-size: 0.8rem; margin-bottom: 1rem; background: #fef2f2; padding: 0.6rem; border-radius: 6px; border: 1px solid #fecaca; }
        .email-hint { font-size: 0.8rem; color: #6b7280; margin-bottom: 1rem; }
        .back { display: inline-block; margin-top: 1rem; color: #007bff; text-decoration: none; font-size: 0.875rem; }
        .back:hover { text-decoration: underline; }
        .note { font-size: 0.75rem; color: #6b7280; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; text-align: center; }
        .otp-inputs { display: flex; gap: 0.5rem; justify-content: center; margin: 1rem 0; }
        .otp-input { width: 45px; height: 55px; text-align: center; font-size: 1.5rem; font-weight: 600; border: 1px solid #d1d5db; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <h1>BizProfile</h1>
        </div>
        <h2><?php echo $step === 'send' ? 'Verify Your Email' : 'Enter Verification Code'; ?></h2>
        <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        
        <?php if ($step === 'send'): ?>
            <p>A verification code will be sent to:</p>
            <div class="email-hint"><strong><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></strong></div>
            <form method="POST" action="/setup/otp/send">
                <?php echo $csrf; ?>
                <button type="submit">Send Verification Code</button>
            </form>
        <?php else: ?>
            <p>Enter the 6-digit code sent to:</p>
            <div class="email-hint"><strong><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></strong></div>
            <form method="POST" action="/setup/otp/verify">
                <?php echo $csrf; ?>
                <div class="otp-inputs">
                    <input type="text" name="otp[0]" class="otp-input" maxlength="1" inputmode="numeric" required>
                    <input type="text" name="otp[1]" class="otp-input" maxlength="1" inputmode="numeric" required>
                    <input type="text" name="otp[2]" class="otp-input" maxlength="1" inputmode="numeric" required>
                    <input type="text" name="otp[3]" class="otp-input" maxlength="1" inputmode="numeric" required>
                    <input type="text" name="otp[4]" class="otp-input" maxlength="1" inputmode="numeric" required>
                    <input type="text" name="otp[5]" class="otp-input" maxlength="1" inputmode="numeric" required>
                </div>
                <input type="hidden" name="otp" value="" id="otp-combined">
                <button type="submit">Verify Code</button>
            </form>
            <button class="secondary" onclick="location.href='/setup/otp/send'">Resend Code</button>
        <?php endif; ?>
        
        <a href="/setup/identify" class="back">&larr; Back</a>
        <p class="note">The code expires in 10 minutes. If you do not receive it, check your spam folder or contact your deployer.</p>
    </div>

    <script>
        // Combine OTP inputs into single hidden field
        const inputs = document.querySelectorAll('.otp-input');
        const combined = document.getElementById('otp-combined');
        const form = document.querySelector('form');
        inputs.forEach((input, idx) => {
            input.addEventListener('input', (e) => {
                if (e.target.value.length === 1 && idx < inputs.length - 1) {
                    inputs[idx + 1].focus();
                }
                combined.value = Array.from(inputs).map(i => i.value).join('');
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && e.target.value === '' && idx > 0) {
                    inputs[idx - 1].focus();
                }
            });
        });
        form.addEventListener('submit', () => {
            combined.value = Array.from(inputs).map(i => i.value).join('');
        });
    </script>
</body>
</html>
