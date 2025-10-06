<?php
require_once '../../includes/config.php';

$customer_id = $_SESSION['customer_id'];

// Get customer messages (if messages table exists)
$messages = []; // Placeholder for when messages functionality is implemented
?>

<div class="messages-page">
    <div class="page-header">
        <h2><i class="fas fa-envelope"></i> Messages</h2>
        <p>Communicate with property owners and support</p>
    </div>

    <div class="messages-container">
        <div class="messages-sidebar">
            <div class="compose-button">
                <button class="btn-primary" onclick="composeMessage()">
                    <i class="fas fa-plus"></i> New Message
                </button>
            </div>
            
            <div class="message-filters">
                <button class="filter-btn active" onclick="filterMessages('all')">All Messages</button>
                <button class="filter-btn" onclick="filterMessages('unread')">Unread (0)</button>
                <button class="filter-btn" onclick="filterMessages('owners')">From Owners</button>
                <button class="filter-btn" onclick="filterMessages('support')">Support</button>
            </div>
        </div>

        <div class="messages-main">
            <div class="messages-list" id="messages-list">
                <div class="no-messages">
                    <i class="fas fa-envelope-open"></i>
                    <h3>No Messages Yet</h3>
                    <p>Your messages and conversations will appear here. Start by browsing properties and contacting owners!</p>
                    <button class="btn-primary" onclick="loadContent('browse-properties', 'content/browse_properties.php')">
                        <i class="fas fa-search"></i> Browse Properties
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Compose Modal (hidden by default) -->
    <div class="modal" id="compose-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>New Message</h3>
                <button class="close-btn" onclick="closeComposeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="compose-form">
                    <div class="form-group">
                        <label for="recipient">To:</label>
                        <select id="recipient" name="recipient" required>
                            <option value="">Select recipient...</option>
                            <option value="support">Support Team</option>
                            <!-- Property owners would be loaded dynamically -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject:</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message:</label>
                        <textarea id="message" name="message" rows="5" required></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                        <button type="button" class="btn-secondary" onclick="closeComposeModal()">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function composeMessage() {
    document.getElementById('compose-modal').style.display = 'flex';
}

function closeComposeModal() {
    document.getElementById('compose-modal').style.display = 'none';
    document.getElementById('compose-form').reset();
}

function filterMessages(type) {
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Filter logic would go here when messages are implemented
    showNotification(`Filtering by: ${type}`, 'info');
}

document.getElementById('compose-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const messageData = Object.fromEntries(formData.entries());
    
    // Send message via API
    fetch('api/messages_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'send_message',
            data: messageData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Message sent successfully', 'success');
            closeComposeModal();
            loadMessages(); // Reload messages list
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while sending the message', 'error');
    });
});

function loadMessages() {
    // This would load messages from the server
    // For now, we'll show a placeholder
    console.log('Loading messages...');
}

// Close modal when clicking outside
document.getElementById('compose-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeComposeModal();
    }
});
</script>

<style>
.messages-page {
    padding: 20px;
}

.messages-container {
    display: flex;
    gap: 20px;
    height: calc(100vh - 200px);
}

.messages-sidebar {
    width: 250px;
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.compose-button {
    margin-bottom: 20px;
}

.compose-button button {
    width: 100%;
}

.message-filters {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-btn {
    padding: 12px 16px;
    background: transparent;
    border: none;
    border-radius: 6px;
    text-align: left;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-btn:hover,
.filter-btn.active {
    background: #2563eb;
    color: white;
}

.messages-main {
    flex: 1;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.messages-list {
    padding: 20px;
    height: 100%;
}

.no-messages {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    text-align: center;
    color: #6b7280;
}

.no-messages i {
    font-size: 4rem;
    margin-bottom: 1rem;
    color: #d1d5db;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    padding: 0;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.modal-body {
    padding: 20px;
}

.close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
}

.close-btn:hover {
    color: #374151;
}
</style>