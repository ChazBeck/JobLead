<?php

class JobImporter {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Clean text by removing LLM citations and cleaning URLs
     * @param string $text Text to clean
     * @return string Cleaned text
     */
    private function cleanText($text) {
        if (empty($text)) {
            return $text;
        }

        // Remove LLM citation patterns like [oai_citation:1‡finance.yahoo.com]
        $text = preg_replace('/\[oai_citation:\d+[‡†]\S+?\]/u', '', $text);
        
        // Remove citation markers like [oai_citation:0‡...] or similar patterns
        $text = preg_replace('/\[oai_citation[^\]]*\]/u', '', $text);

        return trim($text);
    }

    /**
     * Clean URLs by removing unnecessary query parameters and citation patterns
     * @param string $url URL to clean
     * @return string Cleaned URL
     */
    private function cleanURL($url) {
        if (empty($url)) {
            return $url;
        }

        // First remove citation patterns
        $url = $this->cleanText($url);
        
        // Remove leading/trailing parentheses, spaces, and whitespace
        $url = trim($url, " \t\n\r\0\x0B()");
        
        // Remove fragment identifiers with text parameters (like #:~:text=...)
        $url = preg_replace('/#:~:text=[^&\s]*/', '', $url);
        
        // Remove hash fragments that are just tracking
        $url = preg_replace('/#:~:[^\s]*/', '', $url);

        // Remove common tracking parameters
        $url = preg_replace('/[?&](utm_[^&]+|ref[^&]*|source[^&]*|campaign[^&]*)(&|$)/', '', $url);
        
        // Clean up resulting URL
        $url = preg_replace('/\?&/', '?', $url); // Fix ?& to ?
        $url = preg_replace('/[?&]$/', '', $url); // Remove trailing ? or &

        return trim($url);
    }

    /**
     * Recursively clean all fields in job data
     * Removes citations and cleans URLs from all string values
     * @param mixed $data Data to clean (string, array, or other)
     * @return mixed Cleaned data
     */
    private function cleanAllFields($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->cleanAllFields($value);
            }
            return $data;
        }
        
        if (is_string($data)) {
            // Clean the text (removes citations)
            $cleaned = $this->cleanText($data);
            
            // If it looks like a URL field, also clean URL-specific stuff
            // Check if the entire field is a URL or contains URLs
            if (preg_match('#^https?://#i', trim($cleaned, " \t\n\r\0\x0B()"))) {
                $cleaned = $this->cleanURL($cleaned);
            }
            
            return $cleaned;
        }
        
        return $data;
    }

    /**
     * Import jobs from JSON string
     * @param string $jsonString JSON data as string
     * @return array ['success' => bool, 'message' => string, 'count' => int]
     */
    public function importFromJSON($jsonString) {
        // Validate JSON
        $jobs = json_decode($jsonString, true);
        
        if ($jobs === null) {
            return [
                'success' => false,
                'message' => 'Invalid JSON format: ' . json_last_error_msg(),
                'count' => 0
            ];
        }

        if (!is_array($jobs)) {
            return [
                'success' => false,
                'message' => 'JSON must be an array of job objects',
                'count' => 0
            ];
        }

        if (empty($jobs)) {
            return [
                'success' => false,
                'message' => 'No jobs found in JSON',
                'count' => 0
            ];
        }

        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($jobs as $index => $job) {
            try {
                $result = $this->insertJob($job);
                if ($result === true) {
                    $importedCount++;
                } elseif ($result === 'duplicate') {
                    $skippedCount++;
                } else {
                    $errors[] = "Row " . ($index + 1) . ": " . $this->db->getConnection()->error;
                }
            } catch (Exception $e) {
                $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
            }
        }

        $message = "Successfully imported $importedCount job(s)";
        if ($skippedCount > 0) {
            $message .= ", skipped $skippedCount duplicate(s)";
        }
        if (!empty($errors)) {
            $message .= ". Errors: " . implode("; ", $errors);
        }

        return [
            'success' => $importedCount > 0,
            'message' => $message,
            'count' => $importedCount
        ];
    }

    /**
     * Insert a single job with its contacts
     * @param array $jobData Job data array
     * @return bool|string True if inserted, 'duplicate' if skipped, false on error
     */
    private function insertJob($jobData) {
        // Clean all fields in the job data
        $jobData = $this->cleanAllFields($jobData);
        
        // Check for duplicate (company + role_title)
        $company = $jobData['Company'] ?? null;
        $roleTitle = $jobData['Role Title'] ?? null;

        if ($company && $roleTitle) {
            $checkStmt = $this->db->prepare("SELECT id FROM jobs WHERE company = ? AND role_title = ? LIMIT 1");
            if ($checkStmt) {
                $checkStmt->bind_param('ss', $company, $roleTitle);
                $checkStmt->execute();
                $checkStmt->store_result();
                
                if ($checkStmt->num_rows > 0) {
                    $checkStmt->close();
                    return 'duplicate'; // Skip duplicate
                }
                $checkStmt->close();
            }
        }

        // Prepare job data - create variables for bind_param
        $location = $jobData['Location'] ?? null;
        $postedDate = $jobData['Posted/Updated Date'] ?? null;
        $lastSeenDate = $jobData['Last Seen Date'] ?? null;
        $whyNow = $jobData['Why Now'] ?? null;
        $verificationLevel = $jobData['Verification Level'] ?? null;
        $confidence = $jobData['Confidence'] ?? null;
        $revenueTier = $jobData['Revenue Tier'] ?? null;
        $revenueEstimate = $jobData['Revenue Estimate'] ?? null;
        $revenueConfidence = $jobData['Revenue Confidence'] ?? null;
        $fitScore = (int)($jobData['Fit Score'] ?? 0);
        $engagementType = $jobData['Engagement Type'] ?? null;
        $recommendedAngle = $jobData['Recommended Angle'] ?? null;
        $industry = $jobData['Industry'] ?? null;
        $sourceLink = $jobData['Source Link'] ?? null;
        $parentCompany = $jobData['Parent Company'] ?? null;
        $status = $jobData['Status'] ?? 'New';

        $stmt = $this->db->prepare("
            INSERT INTO jobs (
                company, role_title, location, posted_date, last_seen_date,
                why_now, verification_level, confidence, revenue_tier,
                revenue_estimate, revenue_confidence, fit_score, engagement_type,
                recommended_angle, industry, source_link, parent_company, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            throw new Exception($this->db->getConnection()->error);
        }

        $stmt->bind_param(
            'sssssssssssissssss',
            $company,
            $roleTitle,
            $location,
            $postedDate,
            $lastSeenDate,
            $whyNow,
            $verificationLevel,
            $confidence,
            $revenueTier,
            $revenueEstimate,
            $revenueConfidence,
            $fitScore,
            $engagementType,
            $recommendedAngle,
            $industry,
            $sourceLink,
            $parentCompany,
            $status
        );

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        $jobId = $this->db->getConnection()->insert_id;

        // Insert contacts if they exist
        if (!empty($jobData['Likely Buyers/Managers']) && is_array($jobData['Likely Buyers/Managers'])) {
            foreach ($jobData['Likely Buyers/Managers'] as $contact) {
                $this->insertContact($jobId, $contact);
            }
        }

        $stmt->close();
        return true;
    }

    /**
     * Insert a contact for a job
     * @param int $jobId Job ID
     * @param array $contactData Contact data
     */
    private function insertContact($jobId, $contactData) {
        // Clean all contact fields
        $contactData = $this->cleanAllFields($contactData);
        
        $name = $contactData['Name'] ?? null;
        $title = $contactData['Title'] ?? null;
        $confidenceLevel = $contactData['Confidence'] ?? null;
        $source = $contactData['Source'] ?? null;

        $stmt = $this->db->prepare("
            INSERT INTO contacts (job_id, name, title, confidence, source)
            VALUES (?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            throw new Exception($this->db->getConnection()->error);
        }

        $stmt->bind_param(
            'issss',
            $jobId,
            $name,
            $title,
            $confidenceLevel,
            $source
        );

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        $stmt->close();
    }
}
