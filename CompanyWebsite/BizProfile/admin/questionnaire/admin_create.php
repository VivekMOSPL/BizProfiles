<?php
// Admin create questionnaire form
$csrf = $csrf_token ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Questionnaire - BizProfile Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #1f2937; max-width: 600px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #ffffff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        h1 { color: #111827; font-size: 1.75rem; margin-bottom: 0.5rem; }
        p { color: #6b7280; margin-bottom: 1.75rem; font-size: 0.95rem; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; font-weight: 600; color: #374151; margin-bottom: 0.35rem; font-size: 0.95rem; }
        input[type="text"], select, textarea {
            width: 100%; padding: 0.65rem 0.85rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; transition: border-color 0.2s;
        }
        input[type="text"]:focus, select:focus, textarea:focus {
            outline: none; border-color: #007bff;
        }
        textarea { min-height: 100px; resize: vertical; }
        .btn { padding: 0.75rem 1.5rem; background: #007bff; color: #fff; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6b7280; margin-left: 0.75rem; }
        .btn-secondary:hover { background: #4b5563; }
        .help-text { font-size: 0.85rem; color: #6b7280; margin-top: 0.25rem; }
        .industries { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; margin-top: 1rem; }
        .industry-option {
            padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; background: #f9fafb;
        }
        .industry-option:hover { border-color: #007bff; background: #f0f7ff; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Create Questionnaire</h1>
        <p>Create a new industry-specific questionnaire to collect visitor information.</p>
        <form method="POST" action="/admin/questionnaires">
            <?php echo $csrf; ?>

            <div class="form-group">
                <label for="slug">Slug (URL-friendly identifier)</label>
                <input type="text" name="slug" id="slug" required placeholder="e.g. manufacturing" maxlength="100">
                <p class="help-text">Used in the questionnaire URL. Use lowercase letters, numbers, and hyphens only.</p>
            </div>

            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" name="title" id="title" required placeholder="e.g. Manufacturing Business Questionnaire" maxlength="255">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" placeholder="Describe what this questionnaire is for..."></textarea>
                <p class="help-text">Shown to users at the top of the questionnaire form.</p>
            </div>

            <div class="form-group">
                <label for="industry">Industry</label>
                <select name="industry" id="industry">
                    <option value="general">General Business</option>
                    <option value="manufacturing">Manufacturing</option>
                    <option value="professional_services">Professional Services</option>
                    <option value="technology">Technology</option>
                    <option value="healthcare">Healthcare</option>
                    <option value="education">Education</option>
                    <option value="ngo">NGO / Non-Profit</option>
                    <option value="retail">Retail</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div style="display:flex; gap:0.75rem; margin-top:1.5rem;">
                <button type="submit" class="btn">Create Questionnaire</button>
                <a href="/admin/questionnaires" class="btn btn-secondary">Cancel</a>
            </div>
        </form>

        <p style="margin-top:2rem; font-size:0.875rem; color:#6b7280;">
            <a href="/" style="color:#007bff; text-decoration:none;">&larr; Back to site</a>
        </p>
    </div>
</body>
</html>
