<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Complete | AtoScreen</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #09090b;
            --bg-card: rgba(20, 20, 24, 0.85);
            --border-subtle: rgba(255, 255, 255, 0.08);
            --text-primary: #f4f4f5;
            --text-secondary: #a1a1aa;
            --text-muted: #71717a;
            --amber-primary: #f59e0b;
            --amber-hover: #d97706;
            --emerald-primary: #10b981;
            --emerald-bg: rgba(16, 185, 129, 0.12);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-base);
            background-image: 
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(16, 185, 129, 0.15), transparent),
                radial-gradient(circle at 50% 90%, rgba(245, 158, 11, 0.08), transparent 50%);
            background-attachment: fixed;
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            line-height: 1.5;
        }

        .container {
            width: 100%;
            max-width: 680px;
        }

        .complete-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 1.5rem;
            padding: 2.5rem;
            backdrop-filter: blur(16px);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.8);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .complete-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--emerald-primary), var(--amber-primary), transparent);
        }

        .success-icon-badge {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: var(--emerald-bg);
            border: 2px solid rgba(16, 185, 129, 0.35);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--emerald-primary);
            margin-bottom: 1.25rem;
            box-shadow: 0 0 30px rgba(16, 185, 129, 0.3);
            animation: bounceIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }

        .complete-title {
            font-family: 'Outfit', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .complete-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
            margin-bottom: 2rem;
        }

        /* Summary Credentials Box */
        .summary-box {
            background: rgba(10, 10, 14, 0.7);
            border: 1px solid var(--border-subtle);
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: left;
            margin-bottom: 2rem;
        }

        .summary-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.9rem;
        }

        .summary-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .summary-item:first-child {
            padding-top: 0;
        }

        .summary-label {
            color: var(--text-muted);
            font-weight: 500;
        }

        .summary-value {
            color: #ffffff;
            font-weight: 600;
            font-family: 'JetBrains Mono', monospace;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .copy-pill {
            background: rgba(255, 255, 255, 0.08);
            border: none;
            border-radius: 0.35rem;
            color: var(--text-secondary);
            padding: 0.2rem 0.5rem;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .copy-pill:hover {
            background: var(--amber-primary);
            color: #000;
        }

        /* Security Pill */
        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-subtle);
            padding: 0.4rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        /* Actions */
        .action-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.95rem 1.5rem;
            border-radius: 0.85rem;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }

        .btn-primary {
            background: var(--amber-primary);
            color: #000;
            box-shadow: 0 4px 18px rgba(245, 158, 11, 0.35);
        }

        .btn-primary:hover {
            background: var(--amber-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(245, 158, 11, 0.5);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--border-subtle);
            color: #ffffff;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        @media (max-width: 540px) {
            .action-grid { grid-template-columns: 1fr; }
            .complete-card { padding: 1.75rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="complete-card">
            <div class="success-icon-badge">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>

            <h1 class="complete-title">Ready to Display!</h1>
            <p class="complete-subtitle">
                AtoScreen has been successfully installed, connected to your database, and locked for production.
            </p>

            <div class="summary-box">
                <div class="summary-item">
                    <span class="summary-label">Application</span>
                    <span class="summary-value">{{ $summary['app_name'] ?? 'AtoScreen' }}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Database Connected</span>
                    <span class="summary-value" style="color:var(--emerald-primary);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                        {{ strtoupper($summary['db_driver'] ?? 'MYSQL') }} ({{ $summary['db_database'] ?? 'atoscreen' }})
                    </span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Admin Email</span>
                    <span class="summary-value">
                        <span id="adminEmailText">{{ $summary['admin_email'] ?? 'admin@atofood.com' }}</span>
                        <button type="button" class="copy-pill" onclick="copyText('adminEmailText')">Copy</button>
                    </span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Admin Password</span>
                    <span class="summary-value">
                        <span id="adminPassText">{{ $summary['admin_password'] ?? 'password123' }}</span>
                        <button type="button" class="copy-pill" onclick="copyText('adminPassText')">Copy</button>
                    </span>
                </div>
            </div>

            <div class="security-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--emerald-primary);"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <span>Installer locked via <code>storage/installed</code></span>
            </div>

            <div class="action-grid">
                <a href="{{ $loginUrl }}" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    <span>Admin Login</span>
                </a>
                <a href="{{ $summary['screen_url'] ?? url('/v/1') }}" target="_blank" class="btn btn-secondary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
                    <span>Launch TV Screen</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        function copyText(elementId) {
            const text = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(text);
            const btn = event.target;
            const original = btn.innerText;
            btn.innerText = 'Copied!';
            setTimeout(() => { btn.innerText = original; }, 1500);
        }
    </script>
</body>
</html>
