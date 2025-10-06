<?php
require_once 'includes/config.php';

echo "<h1>Database Structure Check - Payment Tables</h1>";
echo "<hr>";

try {
    // Check if payments table exists and show structure
    echo "<h2>Payments Table Structure</h2>";
    $stmt = $pdo->query("DESCRIBE payments");
    if ($stmt->rowCount() > 0) {
        echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Payments table not found</p>";
    }

    // Check if bookings table exists
    echo "<h2>Bookings Table Structure</h2>";
    $stmt = $pdo->query("DESCRIBE bookings");
    if ($stmt->rowCount() > 0) {
        echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Bookings table not found</p>";
    }

    // Check current payment records
    echo "<h2>Current Payment Records (Last 10)</h2>";
    $stmt = $pdo->query("SELECT * FROM payments ORDER BY created_at DESC LIMIT 10");
    if ($stmt->rowCount() > 0) {
        echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        $first = true;
        while ($row = $stmt->fetch()) {
            if ($first) {
                echo "<tr style='background: #f0f0f0;'>";
                foreach (array_keys($row) as $key) {
                    if (!is_numeric($key)) {
                        echo "<th>" . htmlspecialchars($key) . "</th>";
                    }
                }
                echo "</tr>";
                $first = false;
            }
            echo "<tr>";
            foreach ($row as $key => $value) {
                if (!is_numeric($key)) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No payment records found.</p>";
    }

    // Check rental agreements table
    echo "<h2>Rental Agreements Table Structure</h2>";
    $stmt = $pdo->query("DESCRIBE rental_agreements");
    if ($stmt->rowCount() > 0) {
        echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Rental agreements table not found</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>