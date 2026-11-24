<?php
// Admin questionnaire responses view
$questionnaire = $questionnaire ?? [];
$responses = $responses ?? [];
$csrf = $csrf_token ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responses - <?php echo htmlspecialchars($questionnaire['title'] ?? ''); ?> - BizProfile Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #1f2937; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        h1 { color: #111827; font-size: 1.75rem; }
        .subtitle { color: #6b7280; font-size: 0.95rem; margin-bottom: 1.5rem; }
        .btn { padding: 0.65rem 1.25rem; background: #007bff; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 0.95rem; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6b7280; }
        .btn-secondary:hover { background: #4b5563; }
        table { width: 100%; border-collapse: collapse; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th { background: #f9fafb; color: #374151; font-weight: 600; text-align: left; padding: 1rem; border-bottom: 1px solid #e5e7eb; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 1rem; border-bottom: 1px solid #e5e7eb; font-size: 0.95rem; vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #f9fafb; }
        .empty { text-align: center; padding: 3rem; color: #6b7280; }
        .nav-link { color: #007bff; text-decoration: none; font-size: 0.95rem; }
        .nav-link:hover { text-decoration: underline; }
        .answer-preview { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #4b5563; }
        .badge { display: inline-block; padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #e5e7eb; color: #374151; }
        .date { font-size: 0.875rem; color: #6b7280; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div>
                <h1>Responses</h1>
                <p class="subtitle"><?php echo htmlspecialchars($questionnaire['title'] ?? ''); ?></p>
            </div>
            <a href="/admin/questionnaires" class="btn btn-secondary">&larr; Back to Questionnaires</a>
        </header>

        <?php if (empty($responses)): ?>
            <div class="empty">
                <p>No responses yet. Share your questionnaire link to start collecting responses.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Respondent</th>
                        <th>Email</th>
                        <th>Submitted</th>
                        <th>Answers Preview</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($responses as $r): ?>
                    <tr>
                        <td>#<?php echo $r['id']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($r['respondent_name'] ?? 'Anonymous'); ?>
                            <span class="badge"><?php echo !empty($r['user_id']) ? 'Registered' : 'Guest'; ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($r['respondent_email'] ?? '-'); ?></td>
                        <td>
                            <span class="date"><?php echo htmlspecialchars($r['submitted_at'] ?? $r['created_at']); ?></span>
                        </td>
                        <td>
                            <?php
                            $answers = Database::query(
                                "SELECT q.question_text, ra.answer_text
                                 FROM response_answers ra
                                 JOIN questions q ON ra.question_id = q.id
                                 WHERE ra.response_id = ?
                                 ORDER BY q.sort_order ASC",
                                [$r['id']]
                            );
                            if (!empty($answers)):
                                foreach (array_slice($answers, 0, 3) as $a):
                                    echo '<div class="answer-preview" title="' . htmlspecialchars($a['question_text'] . ': ' . $a['answer_text']) . '">';
                                    echo htmlspecialchars(substr($a['answer_text'] ?? '', 0, 40));
                                    if (strlen($a['answer_text'] ?? '') > 40) echo '...';
                                    echo '</div>';
                                endforeach;
                                if (count($answers) > 3): echo ' <span style="color:#6b7280;">+'.(count($answers)-3).' more</span>'; endif;
                            else:
                                echo '<span class="badge">No answers</span>';
                            endif;
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
