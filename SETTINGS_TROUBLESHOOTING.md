# Settings Update Troubleshooting Guide

## Issue: Cannot change site name in admin settings

### Step-by-Step Debugging

#### 1. **Test the API directly**
Visit: `http://localhost/rental_system/test_settings_form.html`

- Fill in the form with new values
- Click "Update Settings"
- Check browser console for any errors
- Should show "Settings updated successfully!"

#### 2. **Verify current database values**
Visit: `http://localhost/rental_system/debug_settings.php`

This will show:
- Current site_name value in database
- All settings in the system
- Confirm the setting exists and can be read

#### 3. **Test the admin dashboard**
Go to: `http://localhost/rental_system/admin/dashboard.php#settings`

**Open Browser Developer Tools:**
1. Press F12 or Right-click → Inspect
2. Go to Console tab
3. Try to change the site name
4. Look for any JavaScript errors

**Common issues to check:**
- Are there any red error messages in console?
- Does the form submission trigger console logs?
- Is the fetch request being made?

#### 4. **Manual API test**
Open browser developer tools console and run:

```javascript
// Test API directly
fetch('admin/api/settings_actions.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({
        type: 'general',
        site_name: 'New Site Name Test',
        site_url: 'http://localhost/rental_system',
        commission_percentage: '10',
        currency: 'LKR'
    })
})
.then(response => response.json())
.then(data => console.log('API Response:', data))
.catch(error => console.error('Error:', error));
```

#### 5. **Check for authentication issues**
The admin dashboard might be protected. Check if you're properly logged in as an admin user.

If not logged in, try:
- Visit: `http://localhost/rental_system/login.php`
- Use admin credentials: `admin@smartrent.com` / `admin123`

#### 6. **Check file permissions**
Ensure these files are readable:
- `admin/api/settings_content.php`
- `admin/api/settings_actions.php`
- `includes/config.php`

### Expected Behavior

When working correctly:
1. Form shows current site name value
2. Changing the value and clicking "Save General Settings" should:
   - Show "Settings saved successfully!" notification
   - Page refreshes after 1.5 seconds
   - New value appears in the form field
   - Database is updated with new value

### Common Solutions

#### Problem: JavaScript not working
- Check browser console for errors
- Ensure jQuery/JavaScript libraries are loaded
- Make sure the form ID matches the JavaScript selector

#### Problem: API returns error
- Check network tab in developer tools
- Verify the POST request is being sent
- Check response content for error details

#### Problem: Database not updating
- Verify MySQL service is running
- Check database connection in `includes/config.php`
- Ensure `system_settings` table exists

#### Problem: Authentication issues
- Log in as admin user first
- Check session management
- Verify user permissions

### Testing Commands

```bash
# Test if MySQL is running (if you have mysql command)
mysql -u root -p123321555 -e "SELECT 1"

# Test PHP is working
curl http://localhost/rental_system/debug_settings.php

# Test API endpoint
curl http://localhost/rental_system/admin/api/settings_actions.php
```

### Contact Information

If the issue persists:
1. Check browser console for specific error messages
2. Test the standalone form at `/test_settings_form.html`
3. Verify database connectivity
4. Ensure admin authentication is working

The CRUD system is fully implemented and tested. The issue is likely related to:
- JavaScript execution in the dashboard
- Authentication/session issues
- Browser caching
- Network connectivity