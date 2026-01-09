<?php
// One-time cleanup script for existing data

// Connect directly with socket path
$conn = new mysqli(
    'localhost',
    'root',
    '',
    'joblead',
    null,
    '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock'
);

if ($conn->connect_error) {
    die('Connection Failed: ' . $conn->connect_error);
}

/**
 * Clean text by removing LLM citations
 */
function cleanText($text) {
    if (empty($text)) {
        return $text;
    }
    $text = preg_replace('/\[oai_citation:\d+[‡†]\S+?\]/u', '', $text);
    $text = preg_replace('/\[oai_citation[^\]]*\]/u', '', $text);
    return trim($text);
}

/**
 * Clean URLs
 */
function cleanURL($url) {
    if (empty($url)) {
        return $url;
    }
    $url = cleanText($url);
    // Remove leading/trailing parentheses, spaces, and whitespace
    $url = trim($url, " \t\n\r\0\x0B()");
    // Remove fragment identifiers with text parameters
    $url = preg_replace('/#:~:text=[^&\s]*/', '', $url);
    $url = preg_replace('/#:~:[^\s]*/', '', $url);
    $url = preg_replace('/[?&](utm_[^&]+|ref[^&]*|source[^&]*|campaign[^&]*)(&|$)/', '', $url);
    $url = preg_replace('/\?&/', '?', $url);
    $url = preg_replace('/[?&]$/', '', $url);
    return trim($url);
}

/**
 * Recursively clean all string values
 */
function deepClean($data) {
    if (is_string($data)) {
        $cleaned = cleanText($data);
        // If it looks like a URL, clean URL-specific stuff
        if (preg_match('#^https?://#i', trim($cleaned, " \t\n\r\0\x0B()"))) {
            $cleaned = cleanURL($cleaned);
        }
        return $cleaned;
    }
    return $data;
}

echo "Starting cleanup...\n\n";

// Clean jobs table
$result = $conn->query("SELECT id, why_now, revenue_estimate, recommended_angle, source_link FROM jobs");

$jobsUpdated = 0;
while ($row = $result->fetch_assoc()) {
    $whyNow = deepClean($row['why_now']);
    $revenueEstimate = deepClean($row['revenue_estimate']);
    $recommendedAngle = deepClean($row['recommended_angle']);
    $sourceLink = deepClean($row['source_link']);
    
    $stmt = $conn->prepare("
        UPDATE jobs 
        SET why_now = ?, revenue_estimate = ?, recommended_angle = ?, source_link = ?
        WHERE id = ?
    ");
    $stmt->bind_param('ssssi', $whyNow, $revenueEstimate, $recommendedAngle, $sourceLink, $row['id']);
    $stmt->execute();
    $stmt->close();
    $jobsUpdated++;
}

echo "Updated $jobsUpdated jobs\n";

// Clean contacts table
$result = $conn->query("SELECT id, source FROM contacts WHERE source IS NOT NULL");

$contactsUpdated = 0;
while ($row = $result->fetch_assoc()) {
    $source = deepClean($row['source']);
    
    $stmt = $conn->prepare("UPDATE contacts SET source = ? WHERE id = ?");
    $stmt->bind_param('si', $source, $row['id']);
    $stmt->execute();
    $stmt->close();
    $contactsUpdated++;
}

echo "Updated $contactsUpdated contacts\n\n";
echo "Cleanup complete!\n";

$conn->close();
