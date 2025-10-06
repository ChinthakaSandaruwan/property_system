// Search Integration Test
console.log('🔍 Starting Search Integration Test...');

// Test 1: Check if main elements exist
function testElements() {
    console.log('📋 Testing DOM Elements...');
    
    const elements = {
        searchInput: document.getElementById('user-search-input'),
        typeFilter: document.getElementById('user-type-filter'),
        statusFilter: document.getElementById('user-status-filter'),
        tableBody: document.getElementById('users-table-body'),
        tableContainer: document.querySelector('.table-container'),
        searchLoading: document.getElementById('search-loading')
    };
    
    Object.entries(elements).forEach(([name, element]) => {
        if (element) {
            console.log(`✅ ${name}: Found`);
        } else {
            console.error(`❌ ${name}: NOT FOUND`);
        }
    });
    
    return elements;
}

// Test 2: Test API directly
async function testAPI() {
    console.log('🌐 Testing API...');
    
    try {
        // Test basic API
        const response1 = await fetch('api/user_search.php');
        const data1 = await response1.json();
        console.log('✅ Basic API:', data1.success ? 'Working' : 'Failed', data1);
        
        // Test search API
        const response2 = await fetch('api/user_search.php?search=shehan');
        const data2 = await response2.json();
        console.log('✅ Search API:', data2.success ? 'Working' : 'Failed', data2);
        
        return { basicAPI: data1, searchAPI: data2 };
    } catch (error) {
        console.error('❌ API Test Failed:', error);
        return null;
    }
}

// Test 3: Test search functionality
async function testSearchFunction() {
    console.log('🔎 Testing Search Function...');
    
    try {
        // Check if performSearch function exists
        if (typeof performSearch === 'function') {
            console.log('✅ performSearch function exists');
            
            // Test search with parameters
            if (typeof currentSearchParams !== 'undefined') {
                console.log('✅ currentSearchParams exists:', currentSearchParams);
                
                // Simulate search
                currentSearchParams.search = 'test';
                console.log('🔄 Running performSearch...');
                await performSearch();
                console.log('✅ performSearch completed');
            } else {
                console.error('❌ currentSearchParams not found');
            }
        } else {
            console.error('❌ performSearch function not found');
        }
    } catch (error) {
        console.error('❌ Search Function Test Failed:', error);
    }
}

// Test 4: Test event listeners
function testEventListeners() {
    console.log('🎯 Testing Event Listeners...');
    
    const searchInput = document.getElementById('user-search-input');
    if (searchInput) {
        // Create and dispatch input event
        const event = new Event('input', { bubbles: true });
        searchInput.value = 'test';
        searchInput.dispatchEvent(event);
        console.log('✅ Input event dispatched');
    } else {
        console.error('❌ Search input not found for event test');
    }
}

// Main test runner
async function runAllTests() {
    console.log('🚀 Running Complete Search Integration Test...');
    console.log('='.repeat(50));
    
    // Wait for DOM to be ready
    await new Promise(resolve => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', resolve);
        } else {
            resolve();
        }
    });
    
    // Run tests
    const elements = testElements();
    console.log('='.repeat(50));
    
    const apiResults = await testAPI();
    console.log('='.repeat(50));
    
    await testSearchFunction();
    console.log('='.repeat(50));
    
    testEventListeners();
    console.log('='.repeat(50));
    
    console.log('🏁 Test Complete! Check results above.');
    
    // Summary
    const hasBasicElements = elements.searchInput && elements.tableBody;
    const hasAPI = apiResults && apiResults.basicAPI.success;
    
    console.log('📊 SUMMARY:');
    console.log(`DOM Elements: ${hasBasicElements ? '✅ OK' : '❌ FAIL'}`);
    console.log(`API Connection: ${hasAPI ? '✅ OK' : '❌ FAIL'}`);
    console.log(`Search Function: ${typeof performSearch === 'function' ? '✅ OK' : '❌ FAIL'}`);
    
    return {
        elements: hasBasicElements,
        api: hasAPI,
        searchFunction: typeof performSearch === 'function'
    };
}

// Auto-run tests if this script is loaded
if (typeof window !== 'undefined') {
    window.runSearchTests = runAllTests;
    window.testAPI = testAPI;
    window.testElements = testElements;
    
    console.log('🔧 Test functions loaded. Run runSearchTests() to start complete test.');
    
    // Auto-run after a delay
    setTimeout(() => {
        console.log('🔄 Auto-running search tests in 2 seconds...');
        setTimeout(runAllTests, 2000);
    }, 1000);
}