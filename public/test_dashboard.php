<?php
// Test script to debug dashboard issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Dashboard Debug Test</h1>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";

// Load configuration
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../src/Database.php';

echo "<h2>1. Testing Database Connection</h2>";
try {
    $db = new Database();
    echo "✓ Database connected successfully<br>";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h2>2. Testing Query</h2>";
$query = "
    SELECT 
        j.id,
        j.company,
        j.role_title,
        j.status
    FROM jobs j
    ORDER BY j.created_at DESC
";

$result = $db->query($query);
if ($result) {
    echo "✓ Query executed successfully<br>";
    echo "Total rows: " . $result->num_rows . "<br>";
} else {
    echo "✗ Query failed<br>";
    exit;
}

echo "<h2>3. Separating Active vs Non-Active Jobs</h2>";
$activeJobs = [];
$nonActiveJobs = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (($row['status'] ?? 'New') === 'Not interested') {
            $nonActiveJobs[] = $row;
        } else {
            $activeJobs[] = $row;
        }
    }
}

echo "Active Jobs Count: " . count($activeJobs) . "<br>";
echo "Non-Active Jobs Count: " . count($nonActiveJobs) . "<br>";

echo "<h2>4. Active Jobs</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Company</th><th>Role</th><th>Status</th></tr>";
foreach ($activeJobs as $job) {
    echo "<tr>";
    echo "<td>" . $job['id'] . "</td>";
    echo "<td>" . htmlspecialchars($job['company']) . "</td>";
    echo "<td>" . htmlspecialchars($job['role_title']) . "</td>";
    echo "<td>" . htmlspecialchars($job['status'] ?? 'New') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>5. Non-Active Jobs (Not Interested)</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Company</th><th>Role</th><th>Status</th></tr>";
foreach ($nonActiveJobs as $job) {
    echo "<tr>";
    echo "<td>" . $job['id'] . "</td>";
    echo "<td>" . htmlspecialchars($job['company']) . "</td>";
    echo "<td>" . htmlspecialchars($job['role_title']) . "</td>";
    echo "<td>" . htmlspecialchars($job['status'] ?? 'New') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>6. File Check</h2>";
$dashboardPath = '../src/pages/dashboard.php';
echo "Dashboard file exists: " . (file_exists($dashboardPath) ? '✓ Yes' : '✗ No') . "<br>";
echo "Dashboard file modified: " . date('Y-m-d H:i:s', filemtime($dashboardPath)) . "<br>";
echo "Dashboard file size: " . filesize($dashboardPath) . " bytes<br>";

// Check if file contains our new code
$dashboardContent = file_get_contents($dashboardPath);
echo "Contains 'activeJobs': " . (strpos($dashboardContent, 'activeJobs') !== false ? '✓ Yes' : '✗ No') . "<br>";
echo "Contains 'nonActiveJobs': " . (strpos($dashboardContent, 'nonActiveJobs') !== false ? '✓ Yes' : '✗ No') . "<br>";
echo "Contains 'Active Leads': " . (strpos($dashboardContent, 'Active Leads') !== false ? '✓ Yes' : '✗ No') . "<br>";
echo "Contains 'Non-Active Leads': " . (strpos($dashboardContent, 'Non-Active Leads') !== false ? '✓ Yes' : '✗ No') . "<br>";

echo "<h2>7. PHP Info</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "OPcache Enabled: " . (function_exists('opcache_get_status') && opcache_get_status() ? '✓ Yes' : '✗ No') . "<br>";

if (function_exists('opcache_get_status') && opcache_get_status()) {
    echo "<form method='post'>";
    echo "<button type='submit' name='clear_cache'>Clear OPcache</button>";
    echo "</form>";
    
    if (isset($_POST['clear_cache'])) {
        opcache_reset();
        echo "<p style='color: green;'>✓ OPcache cleared! Refresh the dashboard now.</p>";
    }
}

echo "<hr>";
echo "<p><a href='?page=dashboard'>← Back to Dashboard</a></p>";
?>
