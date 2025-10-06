<?php
try {
    // Include database configuration
    require_once '../includes/config.php';
    
    echo '<span style="color: green;">✓ Database connection successful!</span><br>';
    
    // Test basic query
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];
    echo "<span style='color: blue;'>Total users in database: $totalUsers</span><br>";
    
    // Test search query structure
    $stmt = $pdo->prepare("SELECT id, full_name, email FROM users LIMIT 3");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo '<span style="color: blue;">Sample users:</span><br>';
    foreach ($users as $user) {
        echo "- ID: {$user['id']}, Name: {$user['full_name']}, Email: {$user['email']}<br>";
    }
    
    // Test search functionality
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE full_name LIKE ? OR email LIKE ?");
    $searchTerm = "%shehan%";
    $stmt->execute([$searchTerm, $searchTerm]);
    $searchCount = $stmt->fetch()['count'];
    echo "<span style='color: blue;'>Users matching 'shehan': $searchCount</span><br>";
    
    echo '<span style="color: green;">✓ All database tests passed!</span>';
    
} catch (PDOException $e) {
    echo '<span style="color: red;">✗ Database Error: ' . $e->getMessage() . '</span>';
} catch (Exception $e) {
    echo '<span style="color: red;">✗ General Error: ' . $e->getMessage() . '</span>';
}
?>