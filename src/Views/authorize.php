<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autorisation - Vollmacht</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: #f59e0b;
            padding: 30px;
            text-align: center;
            color: white;
        }
        .header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .header .client-name {
            font-size: 18px;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .message {
            margin-bottom: 24px;
            color: #374151;
            line-height: 1.5;
        }
        .scopes {
            background: #f9fafb;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .scopes h3 {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 12px;
        }
        .scope-list {
            list-style: none;
        }
        .scope-item {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #374151;
        }
        .scope-item:last-child {
            border-bottom: none;
        }
        .scope-icon {
            color: #10b981;
            font-weight: bold;
        }
        .actions {
            display: flex;
            gap: 12px;
        }
        .btn {
            flex: 1;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 16px;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
            text-align: center;
        }
        .btn-primary {
            background: #f59e0b;
            color: white;
        }
        .btn-primary:hover {
            background: #d97706;
        }
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        .footer {
            padding: 20px 30px;
            background: #f9fafb;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        .powered-by {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .logo-icon {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>Autorisation</h1>
            <div class="client-name"><?= esc($client->name) ?></div>
        </div>

        <div class="content">
            <div class="message">
                <p><strong><?= esc($client->name) ?></strong> souhaite accéder à votre compte.</p>
                <?php if (!empty($scopes)) : ?>
				<p style="margin-top: 8px; font-size: 14px; color: #6b7280;">
                    Cette application pourra :
                </p>
				<?php endif; ?>
            </div>

            <?php if (!empty($scopes)): ?>
            <div class="scopes">
                <h3>Permissions demandées</h3>
                <ul class="scope-list">
                    <?php foreach ($scopes as $scope): ?>
                    <li class="scope-item">
                        <span class="scope-icon">✓</span>
                        <span><?= esc(ucfirst(str_replace(['-', '_'], ' ', $scope->getIdentifier()))) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" style="display: inline-block;">
                <?= csrf_field() ?>
                
                <?php foreach ($request->getQueryParams() as $key => $value): ?>
                    <input type="hidden" name="<?= esc($key) ?>" value="<?= esc($value) ?>">
                <?php endforeach; ?>
				<input type="hidden" name="auth_token" value="<?= esc($authToken) ?>">

                <div class="actions">
                    <button type="submit" name="action" value="approve" class="btn btn-primary">
                        Autoriser
                    </button>
                </div>
            </form>
            <form method="POST" style="display: inline-block;">
				<?= $this->method('DELETE') ?>
                <?= csrf_field() ?>
                
                <?php foreach ($request->getQueryParams() as $key => $value): ?>
                    <input type="hidden" name="<?= esc($key) ?>" value="<?= esc($value) ?>">
                <?php endforeach; ?>
				<input type="hidden" name="auth_token" value="<?= esc($authToken) ?>">

                <div class="actions">
                    <button type="submit" name="action" value="deny" class="btn btn-secondary">
                        Refuser
                    </button>
                </div>
            </form>
        </div>

        <div class="footer">
            <div class="powered-by">
                <span>Propulsé par</span>
                <div class="logo">
                    <span class="logo-icon">⚡</span>
                    <strong>Vollmacht</strong>
                </div>
                <span>•</span>
                <span>BlitzPHP</span>
            </div>
        </div>
    </div>
</body>
</html>
