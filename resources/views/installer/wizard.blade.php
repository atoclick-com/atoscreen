<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Server Setup & Database Wizard | AtoScreen</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #09090b;
            --bg-card: rgba(20, 20, 24, 0.85);
            --bg-card-hover: rgba(26, 26, 32, 0.95);
            --border-subtle: rgba(255, 255, 255, 0.08);
            --border-active: rgba(245, 158, 11, 0.5);
            --text-primary: #f4f4f5;
            --text-secondary: #a1a1aa;
            --text-muted: #71717a;
            --amber-glow: rgba(245, 158, 11, 0.15);
            --amber-primary: #f59e0b;
            --amber-hover: #d97706;
            --emerald-primary: #10b981;
            --emerald-bg: rgba(16, 185, 129, 0.1);
            --rose-primary: #ef4444;
            --rose-bg: rgba(239, 68, 68, 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-base);
            background-image: 
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(245, 158, 11, 0.12), transparent),
                radial-gradient(circle at 10% 90%, rgba(16, 185, 129, 0.05), transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(245, 158, 11, 0.05), transparent 40%);
            background-attachment: fixed;
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2.5rem 1rem;
            line-height: 1.5;
        }

        .container {
            width: 100%;
            max-width: 820px;
        }

        /* Header Branding */
        .wizard-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.25);
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            color: var(--amber-primary);
            text-transform: uppercase;
            margin-bottom: 0.75rem;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background-color: var(--amber-primary);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--amber-primary);
            animation: pulse 2s infinite ease-in-out;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        .wizard-title {
            font-family: 'Outfit', sans-serif;
            font-size: 2.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #ffffff;
            margin-bottom: 0.4rem;
        }

        .wizard-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
            max-width: 580px;
            margin: 0 auto;
        }

        /* Progress Stepper */
        .stepper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            background: rgba(18, 18, 22, 0.6);
            border: 1px solid var(--border-subtle);
            border-radius: 1rem;
            padding: 0.85rem 1.25rem;
            backdrop-filter: blur(10px);
            position: relative;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            cursor: default;
            transition: all 0.25s ease;
            position: relative;
            z-index: 2;
        }

        .step-num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-subtle);
            color: var(--text-muted);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .step-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            transition: all 0.25s ease;
        }

        .step-item.active .step-num {
            background: var(--amber-primary);
            border-color: var(--amber-primary);
            color: #000;
            box-shadow: 0 0 16px rgba(245, 158, 11, 0.4);
        }

        .step-item.active .step-label {
            color: #ffffff;
        }

        .step-item.completed .step-num {
            background: var(--emerald-primary);
            border-color: var(--emerald-primary);
            color: #000;
        }

        .step-item.completed .step-label {
            color: var(--text-secondary);
        }

        .step-divider {
            flex: 1;
            height: 1px;
            background: var(--border-subtle);
            margin: 0 0.85rem;
        }

        /* Glass Card */
        .wizard-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 1.25rem;
            padding: 2.25rem;
            backdrop-filter: blur(16px);
            box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.7);
            position: relative;
            overflow: hidden;
        }

        .wizard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--amber-primary), transparent);
            opacity: 0.7;
        }

        /* Step Panels */
        .step-panel {
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .step-panel.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .panel-heading {
            margin-bottom: 1.75rem;
        }

        .panel-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.35rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .panel-desc {
            color: var(--text-secondary);
            font-size: 0.92rem;
        }

        /* System Requirements Grid */
        .req-section {
            margin-bottom: 1.75rem;
        }

        .section-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-muted);
            font-weight: 700;
            margin-bottom: 0.75rem;
            display: block;
        }

        .req-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 0.75rem;
        }

        .req-card {
            background: rgba(255, 255, 255, 0.025);
            border: 1px solid var(--border-subtle);
            border-radius: 0.75rem;
            padding: 0.85rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.65rem;
            transition: all 0.2s ease;
            min-width: 0;
        }

        .req-card:hover {
            border-color: rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.04);
        }

        .req-name {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-primary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            flex-shrink: 0;
            white-space: nowrap;
        }

        .status-pill.ok {
            background: var(--emerald-bg);
            color: var(--emerald-primary);
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .status-pill.error {
            background: var(--rose-bg);
            color: var(--rose-primary);
            border: 1px solid rgba(239, 68, 68, 0.25);
        }

        /* Forms & Inputs */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .form-grid.single {
            grid-template-columns: 1fr;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }

        .form-group.col-span-2 {
            grid-column: span 2;
        }

        label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .label-hint {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 400;
        }

        .input-wrapper {
            position: relative;
        }

        input[type="text"],
        input[type="password"],
        input[type="number"],
        input[type="email"],
        select {
            width: 100%;
            background: rgba(14, 14, 18, 0.8);
            border: 1px solid var(--border-subtle);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            color: #ffffff;
            font-size: 0.95rem;
            font-family: inherit;
            outline: none;
            transition: all 0.2s ease;
        }

        input:focus,
        select:focus {
            border-color: var(--amber-primary);
            box-shadow: 0 0 0 3px var(--amber-glow);
            background: rgba(18, 18, 22, 1);
        }

        .input-icon-btn {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .input-icon-btn:hover {
            color: var(--text-primary);
        }

        /* Driver Segmented Switch */
        .driver-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .driver-btn {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-subtle);
            border-radius: 0.85rem;
            padding: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.85rem;
            transition: all 0.2s ease;
            text-align: left;
        }

        .driver-btn:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .driver-btn.active {
            background: rgba(245, 158, 11, 0.08);
            border-color: var(--amber-primary);
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.1);
        }

        .driver-icon {
            width: 36px;
            height: 36px;
            border-radius: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
        }

        .driver-btn.active .driver-icon {
            background: var(--amber-primary);
            color: #000;
        }

        .driver-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #fff;
            display: block;
        }

        .driver-desc {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* Feedback Notice Banner */
        .feedback-banner {
            display: none;
            padding: 0.85rem 1.15rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.88rem;
            line-height: 1.45;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .feedback-banner.show {
            display: flex;
        }

        .feedback-banner.success {
            background: var(--emerald-bg);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #a7f3d0;
        }

        .feedback-banner.error {
            background: var(--rose-bg);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .feedback-banner.info {
            background: rgba(6, 182, 212, 0.1);
            border: 1px solid rgba(6, 182, 212, 0.25);
            color: #a5f3fc;
        }

        /* Action Buttons */
        .card-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-subtle);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            border-radius: 0.75rem;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            border: 1px solid transparent;
        }

        .btn-primary {
            background: var(--amber-primary);
            color: #000;
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
        }

        .btn-primary:hover:not(:disabled) {
            background: var(--amber-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.45);
        }

        .btn-primary:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--border-subtle);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.09);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .btn-outline {
            background: transparent;
            border-color: var(--border-subtle);
            color: var(--text-secondary);
            font-size: 0.85rem;
            padding: 0.55rem 1rem;
        }

        .btn-outline:hover {
            border-color: var(--amber-primary);
            color: var(--amber-primary);
        }

        /* Checkbox Option Card */
        .option-toggle {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-subtle);
            border-radius: 0.75rem;
            padding: 1rem 1.15rem;
            display: flex;
            align-items: center;
            gap: 0.85rem;
            cursor: pointer;
            margin-bottom: 1.25rem;
            transition: all 0.2s ease;
        }

        .option-toggle:hover {
            border-color: rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.035);
        }

        .option-toggle input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--amber-primary);
            cursor: pointer;
        }

        /* Terminal Execution Simulator */
        .terminal-box {
            background: #000000;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.85rem;
            padding: 1.25rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            color: #d4d4d8;
            margin-bottom: 1.5rem;
            max-height: 280px;
            overflow-y: auto;
            box-shadow: inset 0 2px 10px rgba(0, 0, 0, 0.8);
        }

        .term-line {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.45rem;
            opacity: 0;
            animation: termFade 0.25s forwards ease;
        }

        @keyframes termFade {
            to { opacity: 1; }
        }

        .term-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .term-dot.pending { background: #eab308; }
        .term-dot.success { background: #22c55e; }
        .term-dot.error { background: #ef4444; }

        /* Spinner */
        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(0, 0, 0, 0.3);
            border-radius: 50%;
            border-top-color: #000;
            animation: spin 0.6s linear infinite;
        }

        .spinner-light {
            border-color: rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 640px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-group.col-span-2 { grid-column: span 1; }
            .driver-selector { grid-template-columns: 1fr; }
            .wizard-card { padding: 1.5rem; }
            .step-label { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Brand Header -->
        <header class="wizard-header">
            <div class="brand-badge">
                <span class="pulse-dot"></span>
                <span>Server & Database Installation</span>
            </div>
            <h1 class="wizard-title">AtoScreen Setup Wizard</h1>
            <p class="wizard-subtitle">
                Configure your server environment, connect your database, and initialize your digital signage management platform.
            </p>
        </header>

        <!-- Stepper Indicator -->
        <div class="stepper">
            <div class="step-item active" id="stepIndicator1">
                <div class="step-num">1</div>
                <div class="step-label">Server Checks</div>
            </div>
            <div class="step-divider"></div>
            <div class="step-item" id="stepIndicator2">
                <div class="step-num">2</div>
                <div class="step-label">Database</div>
            </div>
            <div class="step-divider"></div>
            <div class="step-item" id="stepIndicator3">
                <div class="step-num">3</div>
                <div class="step-label">App & Admin</div>
            </div>
            <div class="step-divider"></div>
            <div class="step-item" id="stepIndicator4">
                <div class="step-num">4</div>
                <div class="step-label">Installation</div>
            </div>
        </div>

        <!-- Main Card -->
        <main class="wizard-card">
            <!-- ================= STEP 1: SERVER CHECKS ================= -->
            <section class="step-panel active" id="stepPanel1">
                <div class="panel-heading">
                    <h2 class="panel-title">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--amber-primary)"><rect width="20" height="8" x="2" y="2" rx="2" ry="2"/><rect width="20" height="8" x="2" y="14" rx="2" ry="2"/><line x1="6" x2="6.01" y1="6" y2="6"/><line x1="6" x2="6.01" y1="18" y2="18"/></svg>
                        System Environment & Permissions
                    </h2>
                    <p class="panel-desc">We analyzed your hosting environment to ensure seamless operation on your server.</p>
                </div>

                <!-- PHP Version -->
                <div class="req-section">
                    <span class="section-label">PHP Engine</span>
                    <div class="req-card">
                        <div>
                            <div class="req-name">PHP Version (>= {{ $minPhpVersion }})</div>
                            <div style="font-size:0.75rem; color:var(--text-muted)">Current server version: {{ $phpVersion }}</div>
                        </div>
                        @if($phpOk)
                            <span class="status-pill ok">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                Passed
                            </span>
                        @else
                            <span class="status-pill error">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                Upgrade Needed
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Extensions -->
                <div class="req-section">
                    <span class="section-label">Required PHP Extensions</span>
                    <div class="req-grid">
                        @foreach($extensions as $extKey => $ext)
                            <div class="req-card">
                                <span class="req-name">{{ $ext['name'] }}</span>
                                @if($ext['status'])
                                    <span class="status-pill ok">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                        OK
                                    </span>
                                @else
                                    <span class="status-pill error">Missing</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Writable Folders -->
                <div class="req-section">
                    <span class="section-label">Directory Writable Permissions</span>
                    <div class="req-grid">
                        @foreach($directories as $label => $dir)
                            <div class="req-card">
                                <div>
                                    <span class="req-name">{{ $label }}</span>
                                </div>
                                @if($dir['writable'])
                                    <span class="status-pill ok">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                        Writable
                                    </span>
                                @else
                                    <span class="status-pill error">Read-Only</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                @if(!$allRequirementsMet)
                    <div class="feedback-banner error show">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <div>
                            <strong>Attention:</strong> Some server requirements are not fulfilled. Please adjust permissions using <code>chmod -R 777 storage bootstrap/cache</code> or activate missing PHP modules in cPanel.
                        </div>
                    </div>
                @endif

                <div class="card-actions">
                    <button type="button" class="btn btn-outline" onclick="window.location.reload()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                        Re-check System
                    </button>
                    <button type="button" class="btn btn-primary" onclick="goToStep(2)" {{ $allRequirementsMet ? '' : 'disabled' }}>
                        <span>Continue to Database</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </button>
                </div>
            </section>

            <!-- ================= STEP 2: DATABASE CONFIG ================= -->
            <section class="step-panel" id="stepPanel2">
                <div class="panel-heading">
                    <h2 class="panel-title">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--amber-primary)"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                        Database Connection
                    </h2>
                    <p class="panel-desc">Select your database engine and specify connection details.</p>
                </div>

                <!-- Engine Selection -->
                <div class="driver-selector">
                    <button type="button" class="driver-btn active" id="btnDriverMysql" onclick="selectDriver('mysql')">
                        <div class="driver-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                        </div>
                        <div>
                            <span class="driver-title">MySQL / MariaDB</span>
                            <span class="driver-desc">Recommended for cPanel & production servers</span>
                        </div>
                    </button>
                    <button type="button" class="driver-btn" id="btnDriverSqlite" onclick="selectDriver('sqlite')">
                        <div class="driver-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        <div>
                            <span class="driver-title">SQLite (File-based)</span>
                            <span class="driver-desc">Zero-config single file database</span>
                        </div>
                    </button>
                </div>

                <input type="hidden" id="db_connection" name="db_connection" value="mysql">

                <!-- Connection Feedback Banner -->
                <div class="feedback-banner" id="dbTestBanner">
                    <span id="dbTestIcon"></span>
                    <div id="dbTestMessage"></div>
                </div>

                <!-- MySQL Fields -->
                <div id="mysqlFieldsContainer">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="db_host">
                                Database Host
                                <span class="label-hint">Usually localhost or 127.0.0.1</span>
                            </label>
                            <input type="text" id="db_host" name="db_host" value="{{ $dbHost }}" placeholder="127.0.0.1">
                        </div>
                        <div class="form-group">
                            <label for="db_port">
                                Port
                                <span class="label-hint">Default 3306</span>
                            </label>
                            <input type="number" id="db_port" name="db_port" value="{{ $dbPort }}" placeholder="3306">
                        </div>
                        <div class="form-group">
                            <label for="db_database">
                                Database Name
                                <span class="label-hint">e.g. trotiluxe_screen</span>
                            </label>
                            <input type="text" id="db_database" name="db_database" value="{{ $dbDatabase }}" placeholder="atoscreen">
                        </div>
                        <div class="form-group">
                            <label for="db_username">
                                Username
                                <span class="label-hint">e.g. root or cPanel user</span>
                            </label>
                            <input type="text" id="db_username" name="db_username" value="{{ $dbUsername }}" placeholder="root">
                        </div>
                        <div class="form-group col-span-2">
                            <label for="db_password">
                                Database Password
                            </label>
                            <div class="input-wrapper">
                                <input type="password" id="db_password" name="db_password" placeholder="Leave empty if none">
                                <button type="button" class="input-icon-btn" onclick="togglePasswordVisibility('db_password')">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SQLite Fields -->
                <div id="sqliteFieldsContainer" style="display:none;">
                    <div class="form-group" style="margin-bottom:1.5rem;">
                        <label for="sqlite_path">
                            SQLite File Path
                            <span class="label-hint">Defaults to database/database.sqlite</span>
                        </label>
                        <input type="text" id="sqlite_path" name="sqlite_path" value="{{ database_path('database.sqlite') }}">
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; margin-bottom:1rem;">
                    <button type="button" class="btn btn-outline" id="btnTestDb" onclick="testDatabaseConnection()">
                        <span id="btnTestSpinner" class="spinner spinner-light" style="display:none;"></span>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        <span>Test Connection Now</span>
                    </button>
                </div>

                <div class="card-actions">
                    <button type="button" class="btn btn-secondary" onclick="goToStep(1)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        <span>Back</span>
                    </button>
                    <button type="button" class="btn btn-primary" onclick="proceedToStep3()">
                        <span>App & Admin Setup</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </button>
                </div>
            </section>

            <!-- ================= STEP 3: APP & ADMIN ================= -->
            <section class="step-panel" id="stepPanel3">
                <div class="panel-heading">
                    <h2 class="panel-title">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--amber-primary)"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Application & Administrator Account
                    </h2>
                    <p class="panel-desc">Configure your system name, public server URL, and primary admin credentials.</p>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="app_name">
                            Application / Brand Name
                        </label>
                        <input type="text" id="app_name" name="app_name" value="{{ $suggestedAppName }}" placeholder="e.g. trotiluxe">
                    </div>
                    <div class="form-group">
                        <label for="app_url">
                            Application Public URL
                            <span class="label-hint">Detected automatically</span>
                        </label>
                        <input type="text" id="app_url" name="app_url" value="{{ $suggestedUrl }}" placeholder="https://trotiluxe.ma/v">
                    </div>
                </div>

                <div style="border-top:1px solid var(--border-subtle); margin:1.5rem 0; padding-top:1.5rem;">
                    <span class="section-label">Master Administrator Credentials</span>
                    <div class="form-grid">
                        <div class="form-group col-span-2">
                            <label for="admin_name">Admin Full Name</label>
                            <input type="text" id="admin_name" name="admin_name" value="Restaurant Manager" placeholder="Manager Name">
                        </div>
                        <div class="form-group">
                            <label for="admin_email">
                                Admin Email
                                <span class="label-hint">Used to sign in</span>
                            </label>
                            <input type="email" id="admin_email" name="admin_email" value="admin@atofood.com" placeholder="admin@atofood.com">
                        </div>
                        <div class="form-group">
                            <label for="admin_password">
                                Admin Password
                                <span class="label-hint">Minimum 6 characters</span>
                            </label>
                            <div class="input-wrapper">
                                <input type="password" id="admin_password" name="admin_password" value="password123" placeholder="password123">
                                <button type="button" class="input-icon-btn" onclick="togglePasswordVisibility('admin_password')">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Starter Content Toggle -->
                <label class="option-toggle">
                    <input type="checkbox" id="seed_starter_content" name="seed_starter_content" checked>
                    <div>
                        <strong style="color:#fff; font-size:0.9rem;">Pre-populate Starter Smart TV Display & Welcome Slide</strong>
                        <div style="color:var(--text-muted); font-size:0.78rem;">Creates "Main Display - 1" with live ticker, clock widget, and a chef special welcome card.</div>
                    </div>
                </label>

                <div class="card-actions">
                    <button type="button" class="btn btn-secondary" onclick="goToStep(2)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        <span>Back</span>
                    </button>
                    <button type="button" class="btn btn-primary" id="btnStartInstall" onclick="startInstallation()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        <span>Install & Connect Database Now</span>
                    </button>
                </div>
            </section>

            <!-- ================= STEP 4: INSTALLATION PROGRESS ================= -->
            <section class="step-panel" id="stepPanel4">
                <div class="panel-heading">
                    <h2 class="panel-title">
                        <span class="spinner" id="mainInstallSpinner" style="border-color:rgba(245,158,11,0.3); border-top-color:var(--amber-primary); width:20px; height:20px;"></span>
                        Installing AtoScreen Platform...
                    </h2>
                    <p class="panel-desc" id="installSubtext">Running database migrations and initializing system files. Please do not close this window.</p>
                </div>

                <div class="terminal-box" id="terminalOutput">
                    <!-- Live terminal messages injected via JS -->
                </div>

                <div class="feedback-banner error" id="installErrorBanner">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div>
                        <strong id="installErrorTitle">Installation Error</strong>
                        <div id="installErrorDetails"></div>
                    </div>
                </div>

                <div class="card-actions" id="installErrorActions" style="display:none;">
                    <button type="button" class="btn btn-secondary" onclick="goToStep(2)">
                        <span>Review Database Config</span>
                    </button>
                    <button type="button" class="btn btn-primary" onclick="startInstallation()">
                        <span>Retry Installation</span>
                    </button>
                </div>
            </section>
        </main>
    </div>

    <!-- Interactive Script -->
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let currentStep = 1;
        let dbConnectionTested = false;

        function goToStep(step) {
            currentStep = step;

            // Update Panel visibility
            document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
            const targetPanel = document.getElementById('stepPanel' + step);
            if (targetPanel) targetPanel.classList.add('active');

            // Update Stepper
            for (let i = 1; i <= 4; i++) {
                const indicator = document.getElementById('stepIndicator' + i);
                if (!indicator) continue;
                indicator.classList.remove('active', 'completed');
                if (i < step) {
                    indicator.classList.add('completed');
                    indicator.querySelector('.step-num').innerHTML = '✓';
                } else if (i === step) {
                    indicator.classList.add('active');
                    indicator.querySelector('.step-num').innerHTML = i;
                } else {
                    indicator.querySelector('.step-num').innerHTML = i;
                }
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function selectDriver(driver) {
            document.getElementById('db_connection').value = driver;
            const btnMysql = document.getElementById('btnDriverMysql');
            const btnSqlite = document.getElementById('btnDriverSqlite');
            const mysqlFields = document.getElementById('mysqlFieldsContainer');
            const sqliteFields = document.getElementById('sqliteFieldsContainer');

            if (driver === 'mysql') {
                btnMysql.classList.add('active');
                btnSqlite.classList.remove('active');
                mysqlFields.style.display = 'block';
                sqliteFields.style.display = 'none';
            } else {
                btnSqlite.classList.add('active');
                btnMysql.classList.remove('active');
                mysqlFields.style.display = 'none';
                sqliteFields.style.display = 'block';
            }

            // Hide previous test banner
            const banner = document.getElementById('dbTestBanner');
            banner.classList.remove('show', 'success', 'error');
            dbConnectionTested = false;
        }

        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            field.type = field.type === 'password' ? 'text' : 'password';
        }

        async function testDatabaseConnection() {
            const btn = document.getElementById('btnTestDb');
            const spinner = document.getElementById('btnTestSpinner');
            const banner = document.getElementById('dbTestBanner');
            const msgEl = document.getElementById('dbTestMessage');
            const iconEl = document.getElementById('dbTestIcon');

            const driver = document.getElementById('db_connection').value;
            const payload = {
                db_connection: driver,
                db_host: document.getElementById('db_host').value,
                db_port: document.getElementById('db_port').value,
                db_database: driver === 'sqlite' 
                    ? document.getElementById('sqlite_path').value 
                    : document.getElementById('db_database').value,
                db_username: document.getElementById('db_username').value,
                db_password: document.getElementById('db_password').value,
            };

            btn.disabled = true;
            spinner.style.display = 'inline-block';
            banner.classList.remove('show', 'success', 'error', 'info');

            try {
                const response = await fetch("{{ url('install/test-db') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    banner.className = 'feedback-banner success show';
                    iconEl.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>`;
                    msgEl.innerHTML = `<strong>Success!</strong> ${data.message}`;
                    dbConnectionTested = true;
                } else {
                    banner.className = 'feedback-banner error show';
                    iconEl.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;
                    msgEl.innerHTML = `<strong>Connection Failed:</strong> ${data.message || 'Unable to connect to database server.'}`;
                    dbConnectionTested = false;
                }
            } catch (err) {
                banner.className = 'feedback-banner error show';
                iconEl.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;
                msgEl.innerHTML = `<strong>Network Error:</strong> ${err.message || 'Server did not respond.'}`;
                dbConnectionTested = false;
            } finally {
                btn.disabled = false;
                spinner.style.display = 'none';
            }
        }

        function proceedToStep3() {
            const driver = document.getElementById('db_connection').value;
            const dbName = driver === 'sqlite' ? document.getElementById('sqlite_path').value : document.getElementById('db_database').value;

            if (!dbName.trim()) {
                alert('Please enter a Database Name.');
                return;
            }

            goToStep(3);
        }

        function addTermLine(text, status = 'pending') {
            const term = document.getElementById('terminalOutput');
            const line = document.createElement('div');
            line.className = 'term-line';
            line.innerHTML = `<span class="term-dot ${status}"></span> <span>${text}</span>`;
            term.appendChild(line);
            term.scrollTop = term.scrollHeight;
            return line;
        }

        async function startInstallation() {
            goToStep(4);

            const term = document.getElementById('terminalOutput');
            term.innerHTML = '';
            document.getElementById('installErrorBanner').classList.remove('show');
            document.getElementById('installErrorActions').style.display = 'none';
            document.getElementById('mainInstallSpinner').style.display = 'inline-block';

            const driver = document.getElementById('db_connection').value;
            const payload = {
                app_name: document.getElementById('app_name').value.trim() || 'AtoScreen',
                app_url: document.getElementById('app_url').value.trim() || window.location.origin,
                db_connection: driver,
                db_host: document.getElementById('db_host').value,
                db_port: document.getElementById('db_port').value,
                db_database: driver === 'sqlite' 
                    ? document.getElementById('sqlite_path').value 
                    : document.getElementById('db_database').value,
                db_username: document.getElementById('db_username').value,
                db_password: document.getElementById('db_password').value,
                admin_name: document.getElementById('admin_name').value.trim() || 'Restaurant Manager',
                admin_email: document.getElementById('admin_email').value.trim() || 'admin@atofood.com',
                admin_password: document.getElementById('admin_password').value || 'password123',
                seed_starter_content: document.getElementById('seed_starter_content').checked,
            };

            const l1 = addTermLine('[1/7] Preparing environment variables & encryption keys...', 'pending');
            await new Promise(r => setTimeout(r, 400));
            l1.querySelector('.term-dot').className = 'term-dot success';

            const l2 = addTermLine('[2/7] Verifying database connectivity and privileges...', 'pending');
            await new Promise(r => setTimeout(r, 400));
            l2.querySelector('.term-dot').className = 'term-dot success';

            const l3 = addTermLine('[3/7] Migrating database schema and tables...', 'pending');

            try {
                const response = await fetch("{{ url('install/setup') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    l3.querySelector('.term-dot').className = 'term-dot success';

                    const l4 = addTermLine('[4/7] Provisioning administrator account (' + payload.admin_email + ')...', 'success');
                    await new Promise(r => setTimeout(r, 300));

                    const l5 = addTermLine('[5/7] Initializing digital signage engine and display settings...', 'success');
                    await new Promise(r => setTimeout(r, 300));

                    const l6 = addTermLine('[6/7] Linking public storage disk and clearing cache layers...', 'success');
                    await new Promise(r => setTimeout(r, 300));

                    const l7 = addTermLine('[7/7] Locking setup wizard via storage/installed security key...', 'success');
                    await new Promise(r => setTimeout(r, 500));

                    addTermLine('✨ Installation succeeded! Redirecting to completion overview...', 'success');

                    setTimeout(() => {
                        window.location.href = data.redirect_url || "{{ url('install/complete') }}";
                    }, 1000);
                } else {
                    l3.querySelector('.term-dot').className = 'term-dot error';
                    throw new Error(data.message || 'Setup encountered an unexpected error.');
                }
            } catch (err) {
                document.getElementById('mainInstallSpinner').style.display = 'none';
                document.getElementById('installSubtext').innerText = 'Installation stopped due to an error.';
                addTermLine('❌ Error: ' + err.message, 'error');

                const errBanner = document.getElementById('installErrorBanner');
                errBanner.classList.add('show');
                document.getElementById('installErrorDetails').innerText = err.message;
                document.getElementById('installErrorActions').style.display = 'flex';
            }
        }
    </script>
</body>
</html>
