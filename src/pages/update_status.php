<?php
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../constants.php';

// Set content type to JSON
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['job_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$jobId = intval($data['job_id']);
$newStatus = $data['status'];

// Validate status using constants (case-insensitive)
$validStatuses = array_map('strtolower', VALID_JOB_STATUSES);
$statusLower = strtolower($newStatus);

if (!in_array($statusLower, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status: ' . $newStatus]);
    exit;
}

// Find the correctly cased status from the constants
$statusIndex = array_search($statusLower, $validStatuses);
$newStatus = VALID_JOB_STATUSES[$statusIndex];

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Get current status before update
    $stmt = $conn->prepare("SELECT status FROM jobs WHERE id = ?");
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Job not found']);
        exit;
    }
    
    $row = $result->fetch_assoc();
    $oldStatus = $row['status'];
    
    // Update the status
    $stmt = $conn->prepare("UPDATE jobs SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $jobId);
    $stmt->execute();
    
    // Trigger actions based on status change
    performStatusAction($jobId, $oldStatus, $newStatus, $conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully',
        'old_status' => $oldStatus,
        'new_status' => $newStatus
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

/**
 * Perform actions based on status change
 */
function performStatusAction($jobId, $oldStatus, $newStatus, $conn) {
    // Log the status change
    error_log("Job #{$jobId}: Status changed from '{$oldStatus}' to '{$newStatus}'");
    
    // TODO: Implement specific actions for each status
    // For now, we'll just log. You can expand this later.
    
    switch ($newStatus) {
        case 'Awaiting approval':
            // TODO: Send notification to approver
            break;
            
        case 'Create Email':
            // TODO: Generate email template or redirect to email creation
            break;
            
        case 'Email sent':
            // TODO: Log timestamp, set up email tracking
            break;
            
        case 'Email Opened':
            // TODO: Record open time, send follow-up reminder
            break;
            
        case 'Responded to Email':
            // TODO: Create task for follow-up
            break;
            
        case 'Not interested':
            // TODO: Archive or mark for future follow-up
            break;
    }
}
