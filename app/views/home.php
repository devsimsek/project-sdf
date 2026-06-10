<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <meta name="description" content="SDF Framework - A fast, modern PHP framework by devsimsek.">
  <title>{{ $app_title ?? "SDF" }} - v{{ $app_version ?? SDF_VERSION }}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg: #0b0d12;
      --surface: #12151e;
      --border: #1e2330;
      --primary: #6c63ff;
      --primary-glow: rgba(108,99,255,.25);
      --accent: #00e5ff;
      --text: #e2e8f0;
      --muted: #64748b;
      --success: #22d3a5;
      --mono: 'JetBrains Mono', monospace;
      --sans: 'Inter', sans-serif;
    }

    html { scroll-behavior: smooth; }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: var(--sans);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }

    /* Ambient background */
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background:
        radial-gradient(ellipse 60% 50% at 20% 20%, rgba(108,99,255,.12) 0%, transparent 70%),
        radial-gradient(ellipse 50% 40% at 80% 80%, rgba(0,229,255,.08) 0%, transparent 70%);
      pointer-events: none;
    }

    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 3rem 3.5rem;
      max-width: 680px;
      width: 100%;
      position: relative;
      box-shadow: 0 0 0 1px var(--border), 0 32px 80px rgba(0,0,0,.5);
      animation: fadeUp .6s ease both;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      background: var(--primary-glow);
      border: 1px solid var(--primary);
      color: var(--primary);
      font-size: .72rem;
      font-weight: 600;
      letter-spacing: .08em;
      padding: .3rem .75rem;
      border-radius: 99px;
      margin-bottom: 1.5rem;
    }

    .badge .dot {
      width: 6px; height: 6px;
      background: var(--success);
      border-radius: 50%;
      animation: pulse 2s ease infinite;
    }

    @keyframes pulse {
      0%,100% { opacity: 1; } 50% { opacity: .3; }
    }

    h1 {
      font-size: 2.4rem;
      font-weight: 700;
      letter-spacing: -.03em;
      line-height: 1.15;
      margin-bottom: .75rem;
    }

    h1 span { color: var(--primary); }

    .subtitle {
      color: var(--muted);
      font-size: 1rem;
      line-height: 1.65;
      margin-bottom: 2rem;
    }

    .divider {
      border: none;
      border-top: 1px solid var(--border);
      margin: 1.75rem 0;
    }

    .meta-row {
      display: flex;
      flex-wrap: wrap;
      gap: .65rem;
      margin-bottom: 2rem;
    }

    .chip {
      display: inline-flex;
      align-items: center;
      gap: .45rem;
      background: rgba(255,255,255,.04);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: .35rem .75rem;
      font-size: .8rem;
      color: var(--muted);
      font-family: var(--mono);
    }

    .chip strong { color: var(--text); font-weight: 500; }

    .actions {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      padding: .65rem 1.4rem;
      border-radius: 10px;
      font-size: .9rem;
      font-weight: 500;
      text-decoration: none;
      cursor: pointer;
      transition: all .2s ease;
    }

    .btn-primary {
      background: var(--primary);
      color: #fff;
      box-shadow: 0 0 24px var(--primary-glow);
    }

    .btn-primary:hover {
      background: #7c74ff;
      transform: translateY(-1px);
      box-shadow: 0 0 36px var(--primary-glow);
    }

    .btn-ghost {
      background: transparent;
      color: var(--muted);
      border: 1px solid var(--border);
    }

    .btn-ghost:hover {
      border-color: var(--primary);
      color: var(--text);
      transform: translateY(-1px);
    }

    .footer-note {
      margin-top: 2rem;
      font-size: .78rem;
      color: var(--muted);
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: .5rem;
    }

    .footer-note code {
      font-family: var(--mono);
      background: rgba(255,255,255,.05);
      padding: .15rem .4rem;
      border-radius: 4px;
    }

    .bench-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1rem;
    }

    .bench-item {
      background: rgba(108,99,255,.06);
      border: 1px solid rgba(108,99,255,.2);
      border-radius: 12px;
      padding: 1rem;
      text-align: center;
    }

    .bench-value {
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--primary);
      font-family: var(--mono);
      letter-spacing: -.02em;
    }

    .bench-label {
      font-size: .78rem;
      font-weight: 500;
      color: var(--text);
      margin: .25rem 0 .15rem;
    }

    .bench-sub {
      font-size: .7rem;
      color: var(--muted);
      font-family: var(--mono);
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="badge"><strong>v{{ SDF_VERSION }}</strong></div>

    <h1>Welcome to <span>SDF</span><br>Framework</h1>
    <p class="subtitle">
      All necessary things are set. Your environment is running without errors.<br>
      Start building your application fast, modern, and PHP-native.
    </p>

    <div class="meta-row">
      <span class="chip">env <strong>{{ SDF_ENV }}</strong></span>
      @If (USE_FUSE)
      <span class="chip">view engine <strong>Fuse</strong></span>
      @Else
      <span class="chip">view engine <strong>PHP</strong></span>
      @endIf
      <span class="chip">php <strong><?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?></strong></span>
    </div>

    <hr class="divider">

    <div class="bench-grid">
      <div class="bench-item">
        <div class="bench-value">~57%</div>
        <div class="bench-label">Faster avg render</div>
        <div class="bench-sub">0.000157ms vs 0.000363ms</div>
      </div>
      <div class="bench-item">
        <div class="bench-value">0.0001ms</div>
        <div class="bench-label">Min response time</div>
        <div class="bench-sub">down from 0.0002ms</div>
      </div>
      <div class="bench-item">
        <div class="bench-value">61%</div>
        <div class="bench-label">Less variance</div>
        <div class="bench-sub">σ 0.000056ms vs 0.000143ms</div>
      </div>
    </div>

    <hr class="divider">

    <div class="actions">
      <a class="btn btn-primary" href="{{ SDF_SRC_LATEST }}/wiki" target="_blank">
        📖 Read the Docs
      </a>
      <a class="btn btn-ghost" href="{{ SDF_SRC_LATEST }}" target="_blank">
        ⭐ GitHub
      </a>
    </div>

    <div class="footer-note">
      <span>Powered by <code>sdf v{{ SDF_VERSION }}</code> &copy; devsimsek</span>
      <span>Open DevTools console to view benchmark data.</span>
    </div>
  </div>
</body>
</html>
