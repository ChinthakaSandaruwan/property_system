// Quick console debug script - paste this in browser console
console.log('🔍 DASHBOARD SEARCH DEBUG');
console.log('========================');

// Check if elements exist
const elements = {
    searchInput: document.getElementById('user-search-input'),
    typeFilter: document.getElementById('user-type-filter'), 
    statusFilter: document.getElementById('user-status-filter'),
    tableBody: document.getElementById('users-table-body'),
    clearBtn: document.getElementById('clear-filters')
};

console.log('1. DOM Elements:');
Object.entries(elements).forEach(([name, el]) => {
    console.log(`   ${name}: ${el ? '✅ Found' : '❌ Missing'}`);
});

// Check if functions exist
const functions = {
    performSearch: window.performSearch,
    clearAllFilters: window.clearAllFilters,
    handleSearchInput: window.handleSearchInput,
    currentSearchParams: window.currentSearchParams
};

console.log('\n2. Functions:');
Object.entries(functions).forEach(([name, fn]) => {
    console.log(`   ${name}: ${fn ? '✅ Available' : '❌ Missing'} (${typeof fn})`);
});

// Test search manually
console.log('\n3. Manual Search Test:');
if (elements.searchInput && window.performSearch) {
    console.log('   🔄 Testing search...');
    elements.searchInput.value = 'shehan';
    
    // Simulate typing
    const event = new Event('input', { bubbles: true });
    elements.searchInput.dispatchEvent(event);
    
    console.log('   ✅ Search event dispatched');
    
    // Also try direct search
    if (window.currentSearchParams) {
        window.currentSearchParams.search = 'shehan';
        window.performSearch().then(() => {
            console.log('   ✅ Direct search completed');
        }).catch(err => {
            console.log('   ❌ Direct search failed:', err);
        });
    }
} else {
    console.log('   ❌ Cannot test - missing elements or functions');
}

console.log('\n========================');
console.log('Debug complete! Check results above.');