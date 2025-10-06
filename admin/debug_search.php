<!DOCTYPE html>
<html>
<head>
    <title>Search Debug Test</title>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
        .debug-output { background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 3px; }
        button { padding: 10px 15px; margin: 5px; cursor: pointer; }
        input { padding: 8px; margin: 5px; width: 200px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>User Search Debug Tool</h1>
    
    <div class="debug-section">
        <h2>1. API Test</h2>
        <button onclick="testAPI()">Test API Direct</button>
        <button onclick="testAPIWithSearch()">Test API with Search</button>
        <div id="api-result" class="debug-output"></div>
    </div>
    
    <div class="debug-section">
        <h2>2. Database Connection Test</h2>
        <button onclick="testDB()">Test Database</button>
        <div id="db-result" class="debug-output"></div>
    </div>
    
    <div class="debug-section">
        <h2>3. Manual Search Test</h2>
        <input type="text" id="search-term" placeholder="Enter search term" value="shehan">
        <button onclick="manualSearch()">Search</button>
        <div id="search-result" class="debug-output"></div>
    </div>
    
    <div class="debug-section">
        <h2>4. Console Logs</h2>
        <button onclick="clearLogs()">Clear Logs</button>
        <div id="console-logs" class="debug-output"></div>
    </div>

    <script>
        // Console log capture
        let logs = [];
        const originalLog = console.log;
        const originalError = console.error;
        
        console.log = function(...args) {
            logs.push({type: 'log', message: args.join(' '), time: new Date().toLocaleTimeString()});
            originalLog.apply(console, args);
            updateConsoleLogs();
        };
        
        console.error = function(...args) {
            logs.push({type: 'error', message: args.join(' '), time: new Date().toLocaleTimeString()});
            originalError.apply(console, args);
            updateConsoleLogs();
        };
        
        function updateConsoleLogs() {
            const logsDiv = document.getElementById('console-logs');
            logsDiv.innerHTML = logs.map(log => 
                `<div class="${log.type}">[${log.time}] ${log.message}</div>`
            ).join('');
        }
        
        function clearLogs() {
            logs = [];
            updateConsoleLogs();
        }
        
        // Test functions
        async function testAPI() {
            const resultDiv = document.getElementById('api-result');
            resultDiv.innerHTML = 'Testing API...';
            
            try {
                const response = await fetch('api/user_search.php');
                const data = await response.json();
                console.log('API Test Result:', data);
                
                if (data.success) {
                    resultDiv.innerHTML = `<span class="success">✓ API Working!</span><br>Found ${data.data.pagination.total_users} users`;
                } else {
                    resultDiv.innerHTML = `<span class="error">✗ API Error:</span> ${data.message}`;
                }
            } catch (error) {
                console.error('API Test Error:', error);
                resultDiv.innerHTML = `<span class="error">✗ API Failed:</span> ${error.message}`;
            }
        }
        
        async function testAPIWithSearch() {
            const resultDiv = document.getElementById('api-result');
            resultDiv.innerHTML = 'Testing API with search...';
            
            try {
                const response = await fetch('api/user_search.php?search=shehan');
                const data = await response.json();
                console.log('API Search Test Result:', data);
                
                if (data.success) {
                    resultDiv.innerHTML = `<span class="success">✓ Search API Working!</span><br>Found ${data.data.pagination.total_users} users for "shehan"`;
                } else {
                    resultDiv.innerHTML = `<span class="error">✗ Search API Error:</span> ${data.message}`;
                }
            } catch (error) {
                console.error('Search API Test Error:', error);
                resultDiv.innerHTML = `<span class="error">✗ Search API Failed:</span> ${error.message}`;
            }
        }
        
        async function testDB() {
            const resultDiv = document.getElementById('db-result');
            resultDiv.innerHTML = 'Testing database connection...';
            
            try {
                const response = await fetch('debug_db.php');
                const result = await response.text();
                console.log('DB Test Result:', result);
                resultDiv.innerHTML = result;
            } catch (error) {
                console.error('DB Test Error:', error);
                resultDiv.innerHTML = `<span class="error">✗ DB Test Failed:</span> ${error.message}`;
            }
        }
        
        async function manualSearch() {
            const searchTerm = document.getElementById('search-term').value;
            const resultDiv = document.getElementById('search-result');
            
            if (!searchTerm) {
                resultDiv.innerHTML = '<span class="error">Please enter a search term</span>';
                return;
            }
            
            resultDiv.innerHTML = `Searching for "${searchTerm}"...`;
            
            try {
                const url = `api/user_search.php?search=${encodeURIComponent(searchTerm)}`;
                console.log('Manual search URL:', url);
                
                const response = await fetch(url);
                const data = await response.json();
                console.log('Manual search result:', data);
                
                if (data.success) {
                    const users = data.data.users;
                    let html = `<span class="success">✓ Found ${users.length} users:</span><br>`;
                    users.forEach(user => {
                        html += `- ${user.full_name} (${user.email}) - ${user.user_type}<br>`;
                    });
                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = `<span class="error">✗ Search Error:</span> ${data.message}`;
                }
            } catch (error) {
                console.error('Manual search error:', error);
                resultDiv.innerHTML = `<span class="error">✗ Search Failed:</span> ${error.message}`;
            }
        }
        
        // Test on page load
        console.log('Debug page loaded');
        setTimeout(() => {
            console.log('Running automatic API test...');
            testAPI();
        }, 1000);
    </script>
</body>
</html>