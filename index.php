<?php
/**
 * Email List Cleaner - Hostinger Optimized
 * Features: Regex, Typo Fix, Disposable Check, DNS Verify, Dedupe
 */

// Handle AJAX Request for DNS Verification (Server-side to bypass CORS)
if (isset($_POST['action']) && $_POST['action'] === 'check_dns') {
    header('Content-Type: application/json');
    $domain = filter_var($_POST['domain'], FILTER_SANITIZE_URL);
    
    if (!$domain) {
        echo json_encode(['valid' => false, 'error' => 'Invalid domain']);
        exit;
    }

    // Check for MX Records
    $mxRecords = checkdnsrr($domain, 'MX');
    // Fallback to A Record if no MX (some small domains do this)
    $aRecord = checkdnsrr($domain, 'A');

    echo json_encode(['valid' => ($mxRecords || $aRecord)]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email List Cleaner | Apex Tools</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/styles.css">
    <style>
        :root { --primary: #1e3a5f; --bg: #f7fafc; --text: #2d3748; --danger: #e53e3e; --success: #48bb78; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); padding-top: 80px; line-height: 1.6; }
        .tool-container { max-width: 900px; margin: 40px auto; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; }
        h1 { text-align: center; color: var(--primary); margin-bottom: 0.5rem; }
        p.subtitle { text-align: center; color: #718096; margin-bottom: 2rem; }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; font-size: 0.9rem; }
        .feature-item { background: #f7fafc; padding: 1rem; border-radius: 8px; border-left: 4px solid var(--primary); }
        textarea { width: 100%; height: 200px; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 8px; font-family: monospace; resize: vertical; box-sizing: border-box; }
        textarea:focus { outline: none; border-color: var(--primary); }
        .controls { display: flex; gap: 1rem; margin-top: 1rem; flex-wrap: wrap; }
        button { padding: 0.75rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: opacity 0.2s; }
        button.primary { background: var(--primary); color: white; flex: 1; }
        button.secondary { background: white; border: 1px solid #e2e8f0; color: var(--text); }
        button:hover { opacity: 0.9; }
        button:disabled { opacity: 0.5; cursor: not-allowed; }
        .stats { display: flex; justify-content: space-between; margin-top: 1.5rem; padding: 1rem; background: #f0fff4; border: 1px solid #9ae6b4; border-radius: 8px; display: none; }
        .stat-box { text-align: center; }
        .stat-number { font-size: 1.5rem; font-weight: bold; color: var(--success); }
        .stat-label { font-size: 0.85rem; color: #22543d; }
        #progressBar { width: 100%; height: 6px; background: #e2e8f0; border-radius: 3px; margin-top: 1rem; overflow: hidden; display: none; }
        #progressFill { height: 100%; background: var(--primary); width: 0%; transition: width 0.3s; }
        .log-area { margin-top: 1rem; font-size: 0.85rem; color: #718096; height: 30px; }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="nav">
                <a href="/" class="logo">
                    <img src="https://www.apexaccuracyestimatinginc.com/wp-content/uploads/2024/08/Apex-logo-2-2048x1486.png" alt="Apex" class="logo-img" width="100" height="100">
                    <span class="logo-text">Apex Tools</span>
                </a>
                <ul class="nav-links pill-nav">
                    <li><a href="/" class="nav-link">Home</a></li>
                    <li><a href="../" class="nav-link">Marketing Tools</a></li>
                    <li><a href="/#about" class="nav-link">About</a></li>
                    <li><a href="https://apexaccuracyestimatinginc.com" class="btn btn-primary">Get Estimating</a></li>
                </ul>
            </nav>
        </div>
    </header>

<div class="tool-container">
    <h1 style="color: #000000;">Email List Cleaner</h1>
    <p class="subtitle" style="color: #000000;">Paste your list below. We clean it locally in your browser.</p>

    <div class="features-grid">
        <div class="feature-item"><strong>✅ Regex Validation</strong><br>Standard format check</div>
        <div class="feature-item"><strong>🔧 Typo Correction</strong><br>Auto-fixes common domains</div>
        <div class="feature-item"><strong>🗑️ Disposable Filter</strong><br>Blocks temp emails</div>
        <div class="feature-item"><strong>🌐 DNS Verification</strong><br>Checks MX records</div>
        <div class="feature-item"><strong>✨ Duplicate Removal</strong><br>Unique list only</div>
    </div>

    <textarea id="emailInput" placeholder="Paste your email list here (one per line or comma separated)...&#10;john@example.com&#10;jane@gmil.com&#10;test@tempmail.com"></textarea>
    
    <div class="controls">
        <button class="primary" id="cleanBtn" onclick="startCleaning()">Clean List Now</button>
        <button class="secondary" onclick="downloadResult()" id="downloadBtn" disabled>Download CSV</button>
        <button class="secondary" onclick="document.getElementById('emailInput').value=''; resetUI();">Clear</button>
    </div>

    <div id="progressBar"><div id="progressFill"></div></div>
    <div class="log-area" id="statusLog">Ready to clean.</div>

    <div class="stats" id="statsBox">
        <div class="stat-box"><div class="stat-number" id="countTotal">0</div><div class="stat-label">Original</div></div>
        <div class="stat-box"><div class="stat-number" id="countFixed">0</div><div class="stat-label">Typos Fixed</div></div>
        <div class="stat-box"><div class="stat-number" id="countRemoved">0</div><div class="stat-label">Removed</div></div>
        <div class="stat-box"><div class="stat-number" id="countValid">0</div><div class="stat-label">Valid Emails</div></div>
    </div>
</div>

<script>
    // Configuration
    const DISPOSABLE_DOMAINS = [
        'tempmail.com', 'throwaway.com', 'guerrillamail.com', 'mailinator.com', 
        '10minutemail.com', 'fakeinbox.com', 'trashmail.com', 'yopmail.com',
        'getnada.com', 'maildrop.cc', 'sharklasers.com'
        // Add more as needed
    ];

    const TYPO_MAP = {
        'gmil.com': 'gmail.com', 'gmai.com': 'gmail.com', 'gnail.com': 'gmail.com',
        'yahooo.com': 'yahoo.com', 'yaho.com': 'yahoo.com',
        'hotmial.com': 'hotmail.com', 'hotmal.com': 'hotmail.com',
        'outlok.com': 'outlook.com', 'outloo.com': 'outlook.com',
        'iclod.com': 'icloud.com'
    };

    let cleanedData = [];

    function log(msg) { document.getElementById('statusLog').innerText = msg; }
    
    async function startCleaning() {
        const raw = document.getElementById('emailInput').value;
        if (!raw.trim()) { alert('Please paste some emails first.'); return; }

        // UI Reset
        document.getElementById('cleanBtn').disabled = true;
        document.getElementById('downloadBtn').disabled = true;
        document.getElementById('progressBar').style.display = 'block';
        document.getElementById('statsBox').style.display = 'none';
        cleanedData = [];
        
        let total = 0;
        let validCount = 0;
        let fixedCount = 0;
        let removedCount = 0;
        const seen = new Set();
        const outputList = [];

        // Split by newline or comma
        const emails = raw.split(/[\n,]+/).map(e => e.trim()).filter(e => e.length > 0);
        total = emails.length;
        document.getElementById('countTotal').innerText = total;

        let processed = 0;

        for (let email of emails) {
            processed++;
            updateProgress((processed / total) * 100);
            
            // 1. Lowercase
            email = email.toLowerCase();

            // 2. Regex Validation (Basic RFC5322)
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!regex.test(email)) {
                removedCount++;
                continue;
            }

            const parts = email.split('@');
            if (parts.length !== 2) { removedCount++; continue; }
            
            let [user, domain] = parts;

            // 3. Typo Correction
            let originalDomain = domain;
            if (TYPO_MAP[domain]) {
                domain = TYPO_MAP[domain];
                email = `${user}@${domain}`;
                fixedCount++;
            }

            // 4. Duplicate Removal
            if (seen.has(email)) {
                removedCount++;
                continue;
            }
            seen.add(email);

            // 5. Disposable Check
            if (DISPOSABLE_DOMAINS.includes(domain)) {
                removedCount++;
                continue;
            }

            // 6. DNS Verification (Async Batch could be faster, but doing sequential for stability)
            // We call our own PHP script to check DNS to avoid browser CORS blocks
            try {
                const dnsValid = await checkDNS(domain);
                if (!dnsValid) {
                    removedCount++;
                    continue;
                }
            } catch (e) {
                // If DNS check fails (timeout/etc), we might keep it or remove based on strictness. 
                // Here we keep it to avoid false negatives on slow networks, but log it.
                console.warn(`DNS check failed for ${domain}, keeping just in case.`);
            }

            outputList.push(email);
            validCount++;
        }

        cleanedData = outputList;
        
        // Update Stats
        document.getElementById('countFixed').innerText = fixedCount;
        document.getElementById('countRemoved').innerText = removedCount;
        document.getElementById('countValid').innerText = validCount;
        document.getElementById('statsBox').style.display = 'flex';
        
        // Output Result
        document.getElementById('emailInput').value = cleanedData.join('\n');
        document.getElementById('downloadBtn').disabled = false;
        document.getElementById('cleanBtn').disabled = false;
        document.getElementById('progressBar').style.display = 'none';
        log(`Done! ${validCount} valid emails found.`);
    }

    function updateProgress(percent) {
        document.getElementById('progressFill').style.width = percent + '%';
        if(percent < 100) log(`Processing... ${Math.round(percent)}%`);
    }

    async function checkDNS(domain) {
        const formData = new FormData();
        formData.append('action', 'check_dns');
        formData.append('domain', domain);

        try {
            const response = await fetch('', { method: 'POST', body: formData });
            const data = await response.json();
            return data.valid;
        } catch (error) {
            console.error('DNS Check Error', error);
            return true; // Fail open (assume valid) if script errors to prevent data loss
        }
    }

    function downloadResult() {
        if (cleanedData.length === 0) return;
        const csvContent = "data:text/csv;charset=utf-8," + cleanedData.join("\n");
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "cleaned_emails.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function resetUI() {
        document.getElementById('statsBox').style.display = 'none';
        document.getElementById('downloadBtn').disabled = true;
        document.getElementById('progressBar').style.display = 'none';
        log('Ready to clean.');
    }
</script>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <a href="/" class="logo">
                        <img src="https://www.apexaccuracyestimatinginc.com/wp-content/uploads/2024/07/white-blue-2048x1486.png" alt="Apex Accuracy Estimating" class="logo-img" width="100" height="100">
                        <span class="logo-text">Apex Accuracy Estimating</span>
                    </a>
                    <p class="footer-desc">Professional construction estimating services and free marketing tools for contractors.</p>
                </div>
                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="/">Home</a></li>
                        <li><a href="/#tools">All Tools</a></li>
                        <li><a href="https://apexaccuracyestimatinginc.com">Main Site</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>Marketing Tools</h4>
                    <ul>
                        <li><a href="../Apex-Email-Extractor/">Email Extractor</a></li>
                        <li><a href="./">Email Cleaner</a></li>
                        <li><a href="../Email-Sorter/">Email Sorter</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Apex Accuracy Estimating Inc. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>