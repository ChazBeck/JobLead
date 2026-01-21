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
     * Parse flexible date formats and return MySQL date format or null
     * @param string $dateString Date string to parse
     * @return string|null Date in YYYY-MM-DD format or null
     */
    private function parseDate($dateString) {
        if (empty($dateString)) {
            return null;
        }
        
        // If already in YYYY-MM-DD format, return as-is
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
            return $dateString;
        }
        
        // Try to parse with strtotime
        $timestamp = strtotime($dateString);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
        
        // If can't parse, return null to avoid database errors
        return null;
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
        $data = json_decode($jsonString, true);
        
        if ($data === null) {
            return [
                'success' => false,
                'message' => 'Invalid JSON format: ' . json_last_error_msg(),
                'count' => 0
            ];
        }

        // Check if it's a single job object or array of jobs
        $jobs = [];
        if (isset($data[0]) && is_array($data[0])) {
            // It's an array of job objects
            $jobs = $data;
        } elseif (is_array($data) && !empty($data)) {
            // It's a single job object, wrap it in an array
            $jobs = [$data];
        } else {
            return [
                'success' => false,
                'message' => 'JSON must be a job object or array of job objects',
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
     * Normalize field names to be case-insensitive and handle common variations
     * @param array $jobData Job data array
     * @return array Normalized job data
     */
    private function normalizeFieldNames($jobData) {
        $fieldMap = [
            // Standard name => alternative names
            'Company' => ['company', 'Company Name', 'Organization'],
            'Role Title' => ['role title', 'Job Title', 'Position', 'Title', 'role_title'],
            'Location' => ['location', 'Office Location', 'Work Location'],
            'Posted/Updated Date' => ['posted/updated date', 'Posted Date', 'Date Posted', 'posted_date'],
            'Last Seen Date' => ['last seen date', 'Last Seen', 'last_seen_date'],
            'Employment Type' => ['employment type', 'Job Type', 'Type', 'employment_type'],
            'Why Now' => ['why now', 'Rationale', 'why_now'],
            'Verification Level' => ['verification level', 'Verification', 'verification_level'],
            'Confidence' => ['confidence', 'Confidence Level'],
            'Revenue Tier' => ['revenue tier', 'Tier', 'revenue_tier'],
            'Revenue Estimate' => ['revenue estimate', 'Revenue', 'revenue_estimate'],
            'Revenue Confidence' => ['revenue confidence', 'revenue_confidence'],
            'Fit Score' => ['fit score', 'Score', 'fit_score'],
            'Industry' => ['industry', 'Sector', 'Vertical'],
            'Engagement Type' => ['engagement type', 'Engagement', 'engagement_type'],
            'Job Description' => ['job description', 'Description', 'Job Details', 'job_description'],
            'Job Overview' => ['job overview', 'Overview', 'job_overview'],
            'Likely Buyers/Managers' => ['likely buyers/managers', 'Contacts', 'Buyers', 'Managers', 'contacts'],
            'Recommended Angle' => ['recommended angle', 'Angle', 'Approach', 'recommended_angle'],
            'Source Link' => ['source link', 'Source', 'URL', 'Link', 'source_link'],
            'Parent Company' => ['parent company', 'Parent', 'parent_company'],
            'Status' => ['status', 'Job Status']
        ];
        
        $normalized = [];
        
        foreach ($fieldMap as $standardName => $alternatives) {
            // Ensure alternatives is an array
            if (!is_array($alternatives)) {
                $alternatives = [$alternatives];
            }
            
            // Check standard name first
            if (isset($jobData[$standardName])) {
                $normalized[$standardName] = $jobData[$standardName];
                continue;
            }
            
            // Check alternatives (case-insensitive)
            foreach ($alternatives as $alt) {
                foreach ($jobData as $key => $value) {
                    if (strcasecmp($key, $alt) === 0) {
                        $normalized[$standardName] = $value;
                        continue 3; // Break out of all loops
                    }
                }
            }
        }
        
        return $normalized;
    }
    
    /**
     * Insert a single job with its contacts
     * @param array $jobData Job data array
     * @return bool|string True if inserted, 'duplicate' if skipped, false on error
     */
    private function insertJob($jobData) {
        // Normalize field names first
        $jobData = $this->normalizeFieldNames($jobData);
        
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
        $postedDate = $this->parseDate($jobData['Posted/Updated Date'] ?? null);
        $lastSeenDate = $this->parseDate($jobData['Last Seen Date'] ?? null);
        $jobDescription = $jobData['Job Description'] ?? null;
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
                company, role_title, job_description, location, posted_date, last_seen_date,
                why_now, verification_level, confidence, revenue_tier,
                revenue_estimate, revenue_confidence, fit_score, engagement_type,
                recommended_angle, industry, source_link, parent_company, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            throw new Exception($this->db->getConnection()->error);
        }

        $stmt->bind_param(
            'ssssssssssssissssss',
            $company,
            $roleTitle,
            $jobDescription,
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
        // Handle both array of contacts or single contact object
        $contacts = $jobData['Likely Buyers/Managers'] ?? null;
        if (!empty($contacts)) {
            // If it's a single object (not an array of objects), wrap it in an array
            if (isset($contacts['Name']) || isset($contacts['name'])) {
                $contacts = [$contacts];
            }
            
            // Now iterate through contacts array
            if (is_array($contacts)) {
                foreach ($contacts as $contact) {
                    if (is_array($contact)) {
                        $this->insertContact($jobId, $contact);
                    }
                }
            }
        }

        $stmt->close();
        
        // Send to Zapier for AI analysis using job_description
        // Fall back to why_now if job_description is empty
        $descriptionForAnalysis = !empty($jobDescription) ? $jobDescription : $whyNow;
        $this->sendToZapier($jobId, $company, $roleTitle, $descriptionForAnalysis);
        
        return true;
    }
    
    /**
     * Send job to Zapier for AI analysis
     */
    private function sendToZapier($jobId, $company, $roleTitle, $jobDescription) {
        // Only send if WebhookHandler is available
        if (class_exists('WebhookHandler')) {
            WebhookHandler::sendToZapierForAnalysis($jobId, $company, $roleTitle, $jobDescription);
        }
    }

    /**
     * Insert a contact for a job
     * @param int $jobId Job ID
     * @param array $contactData Contact data
     */
    private function insertContact($jobId, $contactData) {
        // Clean all contact fields
        $contactData = $this->cleanAllFields($contactData);
        
        // Handle case-insensitive field names for contacts
        $name = $contactData['Name'] ?? $contactData['name'] ?? null;
        $title = $contactData['Title'] ?? $contactData['title'] ?? $contactData['Job Title'] ?? null;
        $confidenceLevel = $contactData['Confidence'] ?? $contactData['confidence'] ?? $contactData['Confidence Level'] ?? null;
        $source = $contactData['Source'] ?? $contactData['source'] ?? $contactData['URL'] ?? null;

        // Skip if no name provided
        if (empty($name)) {
            return;
        }

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
