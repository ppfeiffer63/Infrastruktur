<?php
$hostname = gethostname();
$php_version = phpversion();
$date = date('d.m.Y H:i:s');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Infra – Pfeiffer</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 1rem;
            padding: 2.5rem;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
        }

        .badge {
            display: inline-block;
            background: #0ea5e9;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            margin-bottom: 1.5rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: #94a3b8;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .info-grid {
            display: grid;
            gap: 0.75rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            background: #0f172a;
            border-radius: 0.5rem;
            border: 1px solid #1e293b;
        }

        .info-label {
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .info-value {
            color: #38bdf8;
            font-family: monospace;
            font-size: 0.9rem;
        }

        .footer {
            margin-top: 2rem;
            color: #475569;
            font-size: 0.8rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">Infrastruktur</span>
        <h1>pfeiffer-privat.de</h1>
        <p class="subtitle">Heimserver · Selbst gehostet</p>

        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">Hostname</span>
                <span class="info-value"><?= htmlspecialchars($hostname) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">PHP Version</span>
                <span class="info-value"><?= htmlspecialchars($php_version) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Serverzeit</span>
                <span class="info-value"><?= $date ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Domain</span>
                <span class="info-value"><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'n/a') ?></span>
            </div>
        </div>
    </div>

    <div class="footer">Betrieben mit Caddy · Docker · PHP <?= PHP_MAJOR_VERSION ?></div>
</body>
</html>
