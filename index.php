<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOC HTTP Trigger - Network Operations Center</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" href="data:">
    
    <style>
        :root {
            --noc-bg: #0a0a0f;
            --noc-surface: #12121a;
            --noc-border: #2a2a3a;
            --noc-accent: #00d4aa;
            --noc-accent-dim: #00d4aa3d;
            --noc-error: #ff4757;
            --noc-warning: #ffa502;
            --noc-success: #00d4aa;
            --noc-info: #38bdf8;
            --noc-text: #e8e8f0;
            --noc-text-muted: #71718a;
        }
        
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--noc-bg); color: var(--noc-text); min-height: 100vh; margin: 0; }
        .noc-theme { background: var(--noc-bg); color: var(--noc-text); }
        .noc-surface { background: var(--noc-surface); border: 1px solid var(--noc-border); }
        .noc-accent { color: var(--noc-accent); }
        .noc-error { color: var(--noc-error); }
        .noc-success { color: var(--noc-success); }
        .noc-warning { color: var(--noc-warning); }
        .noc-info { color: var(--noc-info); }
        .noc-text-muted { color: var(--noc-text-muted); }
        .noc-header { padding: 1.5rem 2rem; border-bottom: 1px solid var(--noc-border); }
        .noc-header h1 { font-size: 1.5rem; font-weight: 600; letter-spacing: -0.02em; }
        .noc-header .status-indicator { width: 8px; height: 8px; border-radius: 50%; background: var(--noc-error); animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .noc-response-panel { background: var(--noc-surface); border: 1px solid var(--noc-border); border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .noc-response-status { padding: 4px 8px; border-radius: 4px; font-size: 0.875rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; }
        .status-success { background: var(--noc-accent-dim); color: var(--noc-accent); }
        .status-error { background: rgba(255, 71, 87, 0.15); color: var(--noc-error); }
        .status-pending { background: var(--noc-border); color: var(--noc-text-muted); }
        .noc-input-panel { background: var(--noc-surface); border: 1px solid var(--noc-border); border-radius: 8px; padding: 1.5rem; }
        .noc-textarea { width: 100%; background: var(--noc-bg); border: 1px solid var(--noc-border); border-radius: 6px; padding: 0.875rem 1rem; font-family: 'SF Mono', 'Monaco', 'Cascadia Mono', 'Roboto Mono', monospace; font-size: 1rem; color: var(--noc-text); resize: vertical; min-height: 120px; line-height: 1.6; }
        .noc-textarea:focus { outline: none; border-color: var(--noc-accent); box-shadow: 0 0 0 3px var(--noc-accent-dim); }
        .noc-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.2rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; cursor: pointer; border: none; font-family: inherit; }
        .noc-btn:focus-visible { outline: 2px solid var(--noc-accent); outline-offset: 2px; }
        .noc-btn:hover:not(:disabled) { transform: translateY(-1px); }
        .noc-btn:active { transform: translateY(0); }
        .noc-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .noc-btn-primary { background: var(--noc-accent); color: var(--noc-bg); }
        .noc-btn-primary:hover:not(:disabled) { background: #00f5c4; }
        .noc-btn-secondary { background: var(--noc-bg); color: var(--noc-text); border: 1px solid var(--noc-border); }
        .noc-btn-secondary:hover:not(:disabled) { background: var(--noc-surface); }
        .noc-btn-danger { background: rgba(255, 71, 87, 0.1); color: var(--noc-error); border: 1px solid rgba(255, 71, 87, 0.3); }
        .noc-btn-danger:hover:not(:disabled) { background: rgba(255, 71, 87, 0.2); }
        .noc-char-counter { font-size: 0.75rem; color: var(--noc-text-muted); margin-top: 0.5rem; }
        .noc-loading { display: none; align-items: center; gap: 0.5rem; margin-top: 1rem; }
        .noc-loading.active { display: flex; }
        .noc-loading-spinner { width: 16px; height: 16px; border: 2px solid var(--noc-border); border-top-color: var(--noc-accent); border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .noc-result-card { background: var(--noc-surface); border: 1px solid var(--noc-border); border-radius: 8px; padding: 1rem; margin-top: 0.5rem; }
        .noc-result-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; }
        .noc-result-title { font-size: 0.875rem; font-weight: 500; color: var(--noc-text-muted); }
        .noc-result-status { padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
        .result-status-get { background: var(--noc-accent-dim); color: var(--noc-accent); }
        .result-status-post { background: var(--noc-accent-dim); color: var(--noc-accent); }
        .noc-json-expander { background: var(--noc-bg); border: 1px solid var(--noc-border); border-radius: 6px; overflow: hidden; }
        .noc-json-header { padding: 0.5rem 0.75rem; border-bottom: 1px solid var(--noc-border); cursor: pointer; user-select: none; }
        .noc-json-header:hover { background: var(--noc-surface); }
        .noc-json-content { max-height: 200px; overflow-y: auto; font-family: 'SF Mono', 'Monaco', 'Cascadia Mono', 'Roboto Mono', monospace; font-size: 0.75rem; color: var(--noc-text-muted); padding: 0.75rem; }
        .noc-json-content pre { margin: 0; padding: 0; }
        .noc-json-expanded .noc-json-content { max-height: 500px; }
        .noc-json-expander-icon { float: right; color: var(--noc-text-muted); transition: transform 0.3s ease; }
        .noc-json-expander-icon.rotated { transform: rotate(90deg); }
        .noc-submit-pending { opacity: 0.5; pointer-events: none; }
        @media (max-width: 640px) { .noc-textarea { min-height: 100px; } }
    </style>
</head>
<body class="noc-theme min-h-screen">
    <header class="noc-header">
        <div class="flex items-center justify-between">
            <h1>NOC HTTP Trigger</h1>
            <div class="flex items-center gap-2">
                <div class="status-indicator" id="n8n-status" title="n8n Webhook Status"></div>
                <span class="text-sm noc-text-muted" id="n8n-status-text">Disconnected</span>
            </div>
        </div>
    </header>
    <main class="max-w-5xl mx-auto px-4 py-6">
        <div class="noc-response-panel" id="responsePanel">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 flex-shrink-0 flex items-center justify-center">
                    <svg class="w-4 h-4 noc-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.94-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="noc-text-muted mb-1" id="response-status">Ready</p>
                    <p class="text-xs noc-text-muted" id="response-timestamp">Waiting for execution...</p>
                </div>
            </div>
            <div class="noc-result-card" id="getResult" style="display: none;">
                <div class="noc-result-header">
                    <span class="noc-result-title">GET Request (https://httpbin.org/get)</span>
                    <span class="noc-result-status result-status-get" id="get-status">Pending</span>
                </div>
                <div class="noc-json-expander">
                    <div class="noc-json-header">Response <span class="noc-json-expander-icon">▼</span></div>
                    <div class="noc-json-content" id="get-json"><pre>Waiting for GET response...</pre></div>
                </div>
            </div>
            <div class="noc-result-card" id="postResult" style="display: none;">
                <div class="noc-result-header">
                    <span class="noc-result-title">POST Request (https://httpbin.org/post)</span>
                    <span class="noc-result-status result-status-post" id="post-status">Pending</span>
                </div>
                <div class="noc-json-expander">
                    <div class="noc-json-header">Response <span class="noc-json-expander-icon">▼</span></div>
                    <div class="noc-json-content" id="post-json"><pre>Waiting for POST response...</pre></div>
                </div>
            </div>
            <p class="text-xs noc-text-muted" id="execution-timestamp">--:</p>
        </div>
        <div class="noc-input-panel">
            <textarea class="noc-textarea" id="inputText" placeholder="Paste your text here to trigger the n8n workflow..." oninput="updateCharCounter()"></textarea>
            <div class="noc-char-counter"><span id="charCount">0</span> / 5000 characters</div>
            <div class="noc-loading" id="loadingIndicator"><div class="noc-loading-spinner"></div><span>Submitting to n8n...</span></div>
            <div style="margin-top: 1rem; display: flex; gap: 0.75rem;">
                <button class="noc-btn noc-btn-primary flex-1" id="submitBtn" type="submit" disabled><span id="btn-text">Submit</span><span id="btn-loading" style="display: none;">Submitting...</span></button>
                <button class="noc-btn noc-btn-secondary" type="button" onclick="clearInput()" title="Clear">Clear</button>
            </div>
        </div>
    </main>
    <script src="func.js"></script>
</body>
</html>