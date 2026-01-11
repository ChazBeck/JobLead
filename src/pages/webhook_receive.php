<?php
// Set content type to JSON first (before any output)
header('Content-Type: application/json');

// Enable error logging
ini_set('log_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../Database.php';
    require_once __DIR__ . '/../OfferingTypes.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load required classes: ' . $e->getMessage()]);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log the incoming webhook for debugging
error_log("Webhook received: " . $input);

if (!$data || !isset($data['job_id']) || !isset($data['offerings'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields (job_id or offerings)']);
    exit;
}

$jobId = intval($data['job_id']);
$offerings = $data['offerings'];
$notes = $data['notes'] ?? null;

// Handle case where offerings might be a JSON string (from Zapier)
if (is_string($offerings)) {
    $decoded = json_decode($offerings, true);
    if ($decoded !== null) {
        // If the decoded string itself contains nested structure, extract it
        if (isset($decoded['offerings']) && is_array($decoded['offerings'])) {
            $offerings = $decoded['offerings'];
            // Also extract notes if present in nested structure
            if (!$notes && isset($decoded['notes'])) {
                $notes = $decoded['notes'];
            }
        } else {
            $offerings = $decoded;
        }
    }
}

// Ensure offerings is an array
if (!is_array($offerings)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Offerings must be an object/array']);
    exit;
}

// Validate offerings structure using centralized class
$validOfferings = OfferingTypes::getValidKeys();

// Validate that we have valid offering keys
foreach (array_keys($offerings) as $key) {
    if (!OfferingTypes::isValid($key)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Invalid offering key: $key"]);
        exit;
    }
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Check if job exists
    $stmt = $conn->prepare("SELECT id FROM jobs WHERE id = ?");
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Job not found']);
        exit;
    }
    
    // Build UPDATE query dynamically based on provided offerings
    $updateFields = [];
    $types = '';
    $values = [];
    
    foreach ($validOfferings as $offering) {
        if (isset($offerings[$offering])) {
            $updateFields[] = "$offering = ?";
            $types .= 'i';
            $values[] = $offerings[$offering] ? 1 : 0;
        }
    }
    
    // Add notes if provided
    if ($notes !== null) {
        $updateFields[] = "ai_analysis_notes = ?";
        $types .= 's';
        $values[] = $notes;
    }
    
    // Add timestamp
    $updateFields[] = "ai_analyzed_at = NOW()";
    
    // Add job_id for WHERE clause
    $types .= 'i';
    $values[] = $jobId;
    
    $sql = "UPDATE jobs SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    
    error_log("AI analysis updated for job #{$jobId}");
    
    echo json_encode([
        'success' => true,
        'message' => 'AI analysis saved successfully',
        'job_id' => $jobId,
        'offerings_updated' => array_keys($offerings)
    ]);
    exit; // Stop execution to prevent HTML rendering
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("Webhook error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit; // Stop execution to prevent HTML rendering
}
