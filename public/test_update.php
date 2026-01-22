<?php
// Test the update_status endpoint
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Update Status Test</h1>";

// Load configuration
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../src/Database.php';
require_once '../src/constants.php';

echo "<h2>1. Valid Status Values</h2>";
echo "<pre>";
print_r(VALID_JOB_STATUSES);
echo "</pre>";

echo "<h2>2. Database Connection Test</h2>";
try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "✓ Database connected<br>";
    echo "Connection type: " . get_class($conn) . "<br>";
} catch (Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h2>3. Test Update Job Status</h2>";

// Get a job to test with
$result = $conn->query("SELECT id, company, status FROM jobs LIMIT 1");
if ($result && $result->num_rows > 0) {
    $testJob = $result->fetch_assoc();
    echo "Test Job ID: " . $testJob['id'] . "<br>";
    echo "Company: " . htmlspecialchars($testJob['company']) . "<br>";
    echo "Current Status: " . htmlspecialchars($testJob['status'] ?? 'New') . "<br>";
    
    echo "<h3>Test Status Update Form</h3>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='job_id' value='" . $testJob['id'] . "'>";
    echo "<select name='new_status'>";
    foreach (VALID_JOB_STATUSES as $status) {
        $selected = ($testJob['status'] === $status) ? 'selected' : '';
        echo "<option value='" . htmlspecialchars($status) . "' $selected>" . htmlspecialchars($status) . "</option>";
    }
    echo "</select>";
    echo " <button type='submit' name='test_update'>Update Status</button>";
    echo "</form>";
    
    if (isset($_POST['test_update'])) {
        $jobId = intval($_POST['job_id']);
        $newStatus = $_POST['new_status'];
        
        echo "<h3>Update Result</h3>";
        echo "Attempting to update job $jobId to '$newStatus'<br>";
        
        $stmt = $conn->prepare("UPDATE jobs SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $jobId);
        
        if ($stmt->execute()) {
            echo "✓ Update successful!<br>";
            echo "Rows affected: " . $stmt->affected_rows . "<br>";
            
            // Verify the update
            $verify = $conn->query("SELECT status FROM jobs WHERE id = $jobId");
            $verifyRow = $verify->fetch_assoc();
            echo "New status in DB: " . htmlspecialchars($verifyRow['status']) . "<br>";
            echo "<p style='color: green;'><strong>Success! Now refresh the dashboard to see if it moves between sections.</strong></p>";
        } else {
            echo "✗ Update failed: " . $stmt->error . "<br>";
        }
    }
} else {
    echo "No jobs found in database<br>";
}

echo "<hr>";
echo "<p><a href='?page=dashboard'>← Back to Dashboard</a> | <a href='test_dashboard.php'>Run Dashboard Test</a></p>";
?>
