<?php
// Questionnaire show page - public form
$questionnaire = $questionnaire ?? [];
$questions = $questions ?? [];
$errors = $errors ?? [];
$csrf = $csrf_token ?? '';
$submittedValues = $submitted_values ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($questionnaire['title'] ?? 'Questionnaire'); ?> - BizProfile</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #1f2937; line-height: 1.6; padding: 2rem; max-width: 800px; margin: 0 auto; }
        header { text-align: center; margin-bottom: 2rem; }
        h1 { color: #111827; font-size: 2rem; margin-bottom: 0.5rem; }
        .subtitle { color: #6b7280; font-size: 1rem; }
        .card { background: #ffffff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; font-weight: 600; color: #374151; margin-bottom: 0.35rem; font-size: 0.95rem; }
        input[type="text"], input[type="email"], input[type="tel"], input[type="number"], input[type="date"], textarea, select {
            width: 100%; padding: 0.65rem 0.85rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; transition: border-color 0.2s;
        }
        input[type="text"]:focus, input[type="email"]:focus, input[type="tel"]:focus, textarea:focus, select:focus {
            outline: none; border-color: #007bff;
        }
        textarea { min-height: 120px; resize: vertical; }
        select { cursor: pointer; }
        .checkbox-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .checkbox-item { display: flex; align-items: center; gap: 0.5rem; }
        .checkbox-item input[type="checkbox"] { width: auto; }
        .help-text { font-size: 0.85rem; color: #6b7280; margin-top: 0.25rem; }
        .error { color: #dc2626; font-size: 0.85rem; margin-top: 0.25rem; display: block; }
        .btn { width: 100%; padding: 0.75rem; background: #007bff; color: #fff; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #0056b3; }
        .btn:disabled { background: #9ca3af; cursor: not-allowed; }
        .required::after { content: " *"; color: #dc2626; }
        .back-link { display: inline-block; color: #007bff; text-decoration: none; margin-top: 1rem; }
        .back-link:hover { text-decoration: underline; }
        .industry-badge { display: inline-block; background: #e5e7eb; color: #374151; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <header>
        <h1><?php echo htmlspecialchars($questionnaire['title'] ?? 'Business Questionnaire'); ?></h1>
        <p class="subtitle"><?php echo htmlspecialchars($questionnaire['description'] ?? ''); ?></p>
        <span class="industry-badge"><?php echo htmlspecialchars($questionnaire['industry'] ?? 'general'); ?></span>
    </header>

    <div class="card">
        <form method="POST" action="/questionnaire/<?php echo htmlspecialchars($questionnaire['slug']); ?>/submit">
            <?php echo $csrf; ?>
            <?php if (!empty($errors)): ?>
                <div style="background: #fef2f2; color: #dc2626; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; border: 1px solid #fecaca;">
                    <strong>Please fix the following errors:</strong>
                    <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                        <?php foreach ($errors as $field => $msg): ?>
                            <li><?php echo htmlspecialchars($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php foreach ($questions as $q): ?>
                <?php
                $qKey = $q['question_key'];
                $postKey = 'q_' . $q['id'];
                $isRequired = !empty($q['is_required']);
                $savedValue = isset($submittedValues[$postKey]) ? $submittedValues[$postKey] : '';
                $isError = isset($errors[$qKey]);
                $options = !empty($q['options_json']) ? json_decode($q['options_json'], true) : [];
                ?>
                <div class="form-group">
                    <label class="<?php echo $isRequired ? 'required' : ''; ?>"><?php echo htmlspecialchars($q['question_text']); ?></label>
                    <?php if (!empty($q['help_text'])): ?>
                        <p class="help-text"><?php echo htmlspecialchars($q['help_text']); ?></p>
                    <?php endif; ?>

                    <?php if ($q['question_type'] === 'textarea'): ?>
                        <textarea name="<?php echo $postKey; ?>" rows="4"><?php echo htmlspecialchars($savedValue ?? ''); ?></textarea>
                    <?php elseif ($q['question_type'] === 'select'): ?>
                        <select name="<?php echo $postKey; ?>">
                            <option value="">Select an option</option>
                            <?php foreach ($options as $opt): ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo ($savedValue === $opt) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($opt); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($q['question_type'] === 'radio'): ?>
                        <div class="checkbox-group">
                            <?php foreach ($options as $opt): ?>
                                <label class="checkbox-item">
                                    <input type="radio" name="<?php echo $postKey; ?>" value="<?php echo htmlspecialchars($opt); ?>" <?php echo ($savedValue === $opt) ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($opt); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($q['question_type'] === 'checkbox'): ?>
                        <div class="checkbox-group">
                            <?php foreach ($options as $opt): ?>
                                $savedArray = is_string($savedValue) ? json_decode($savedValue, true) : ($savedValue ?? []);
                                <label class="checkbox-item">
                                    <input type="checkbox" name="<?php echo $postKey; ?>[]" value="<?php echo htmlspecialchars($opt); ?>" <?php echo (is_array($savedArray) && in_array($opt, $savedArray)) ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($opt); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($q['question_type'] === 'email'): ?>
                        <input type="email" name="<?php echo $postKey; ?>" value="<?php echo htmlspecialchars($savedValue ?? ''); ?>" placeholder="name@example.com">
                    <?php elseif ($q['question_type'] === 'tel'): ?>
                        <input type="tel" name="<?php echo $postKey; ?>" value="<?php echo htmlspecialchars($savedValue ?? ''); ?>" placeholder="+1 234 567 8900">
                    <?php elseif ($q['question_type'] === 'number'): ?>
                        <input type="number" name="<?php echo $postKey; ?>" value="<?php echo htmlspecialchars($savedValue ?? ''); ?>">
                    <?php elseif ($q['question_type'] === 'date'): ?>
                        <input type="date" name="<?php echo $postKey; ?>" value="<?php echo htmlspecialchars($savedValue ?? ''); ?>">
                    <?php elseif ($q['question_type'] === 'file'): ?>
                        <input type="file" name="<?php echo $postKey; ?>">
                    <?php else: ?>
                        <input type="text" name="<?php echo $postKey; ?>" value="<?php echo htmlspecialchars($savedValue ?? ''); ?>">
                    <?php endif; ?>

                    <?php if ($isError): ?>
                        <span class="error"><?php echo htmlspecialchars($errors[$qKey]); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn">Submit Questionnaire</button>
        </form>
    </div>

    <a href="/" class="back-link">&larr; Back to home</a>
</body>
</html>
