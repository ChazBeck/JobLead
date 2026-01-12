<?php
require_once __DIR__ . '/../Database.php';

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

if (!$data || !isset($data['job_id']) || !isset($data['updates'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$jobId = intval($data['job_id']);
$updates = $data['updates'];

// Define allowed fields that can be updated
$allowedFields = [
    'company', 'role_title', 'location', 'industry', 'employment_type', 'status',
    'posted_date', 'last_seen_date', 'revenue_tier', 'revenue_estimate',
    'parent_company', 'fit_score', 'confidence', 'verification_level',
    'engagement_type', 'job_description', 'job_overview', 'why_now',
    'recommended_angle', 'source_link'
];

// Filter out any fields that aren't allowed
$filteredUpdates = array_intersect_key($updates, array_flip($allowedFields));

if (empty($filteredUpdates)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Verify job exists
    $stmt = $conn->prepare("SELECT id FROM jobs WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare select statement: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Job not found']);
        exit;
    }
    $stmt->close();
    
    // Build dynamic UPDATE query
    $setClauses = [];
    $types = '';
    $values = [];
    
    foreach ($filteredUpdates as $field => $value) {
        $setClauses[] = "`$field` = ?";
        
        // Determine type for bind_param
        if ($field === 'fit_score') {
            $types .= 'd'; // double
            $values[] = floatval($value);
        } else {
            $types .= 's'; // string
            $values[] = $value;
        }
    }
    
    $setClause = implode(', ', $setClauses);
    $sql = "UPDATE jobs SET $setClause WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare update statement: ' . $conn->error);
    }
    
    // Add job_id to the end of values and types
    $types .= 'i';
    $values[] = $jobId;
    
    // Bind parameters dynamically
    $stmt->bind_param($types, ...$values);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute update: ' . $stmt->error);
    }
    
    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    
    // Log the update
    $updatedFields = implode(', ', array_keys($filteredUpdates));
    error_log("Job #{$jobId}: Updated fields: {$updatedFields}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Job updated successfully',
        'affected_rows' => $affectedRows,
        'updated_fields' => array_keys($filteredUpdates)
    ]);
    
} catch (Exception $e) {
    error_log("Error updating job #{$jobId}: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
