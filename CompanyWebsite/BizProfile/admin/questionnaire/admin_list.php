<?php
// Admin questionnaire list
$questionnaires = $questionnaires ?? [];
$csrf = $csrf_token ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questionnaires - BizProfile Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #1f2937; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        h1 { color: #111827; font-size: 1.75rem; }
        .btn { padding: 0.65rem 1.25rem; background: #007bff; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 0.95rem; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6b7280; }
        .btn-secondary:hover { background: #4b5563; }
        table { width: 100%; border-collapse: collapse; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th { background: #f9fafb; color: #374151; font-weight: 600; text-align: left; padding: 1rem; border-bottom: 1px solid #e5e7eb; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 1rem; border-bottom: 1px solid #e5e7eb; font-size: 0.95rem; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #f9fafb; }
        .badge { display: inline-block; padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
        .badge-default { background: #dbeafe; color: #1e40af; }
        .empty { text-align: center; padding: 3rem; color: #6b7280; }
        .nav-link { color: #007bff; text-decoration: none; font-size: 0.95rem; }
        .nav-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Manage Questionnaires</h1>
            <a href="/admin/questionnaires/create" class="btn">+ Create Questionnaire</a>
        </header>

        <p style="margin-bottom: 1.5rem; color: #6b7280; font-size: 0.95rem;">
            <a href="/" class="nav-link">&larr; Back to site</a>
        </p>

        <?php if (empty($questionnaires)): ?>
            <div class="empty">
                <p>No questionnaires found. Create your first questionnaire to collect visitor information.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Slug</th>
                        <th>Industry</th>
                        <th>Status</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questionnaires as $q): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($q['title']); ?></strong>
                            <?php echo !empty($q['description']) ? '<br><small style="color:#6b7280;">' . htmlspecialchars(substr($q['description'], 0, 80)) . '...</small>' : ''; ?>
                        </td>
                        <td><code><?php echo htmlspecialchars($q['slug']); ?></code></td>
                        <td><?php echo htmlspecialchars($q['industry']); ?></td>
                        <td>
                            <span class="badge <?php echo $q['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $q['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $q['is_default'] ? 'badge-default' : ''; ?>">
                                <?php echo $q['is_default'] ? 'Default' : 'Custom'; ?>
                            </span>
                        </td>
                        <td>
                            <a href="/admin/questionnaires/<?php echo $q['id']; ?>/responses" class="nav-link">View Responses</a>
                            <?php if (!$q['is_default']): ?>
                                | <a href="#" class="nav-link" style="color:#dc2626;">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
