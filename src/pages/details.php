<?php
require_once __DIR__ . '/../OfferingTypes.php';

// Get job ID from URL
$jobId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($jobId === 0) {
    header('Location: ?page=dashboard');
    exit;
}

try {
    // Query job details
    $stmt = $db->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->bind_param('i', $jobId);
    $stmt->execute();
    $result = $stmt->get_result();
    $job = $result->fetch_assoc();
    $stmt->close();

    if (!$job) {
        showError('Job not found');
    }

    // Query contacts for this job
    $stmt = $db->prepare("SELECT * FROM contacts WHERE job_id = ?");
    $stmt->bind_param('i', $jobId);
    $stmt->execute();
    $result = $stmt->get_result();
    $contacts = [];
    while ($row = $result->fetch_assoc()) {
        $contacts[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    showError('Failed to load job details: ' . $e->getMessage());
}

renderHeader('JobLead - ' . htmlspecialchars($job['company']));
?>

    <main>
        <div class="back-link">
            <a href="?page=dashboard">← Back to Dashboard</a>
        </div>

        <h2><?php echo htmlspecialchars($job['company']); ?></h2>
        <h3><?php echo htmlspecialchars($job['role_title']); ?></h3>

        <?php 
        // Check if AI analysis has been performed
        $hasAnalysis = $job['ai_analyzed_at'] !== null;
        
        if ($hasAnalysis):
            $offerings = OfferingTypes::getFullLabels();
            $detectedCount = array_sum(array_map(function($k) use ($job) { return $job[$k] ?? 0; }, array_keys($offerings)));
        ?>
        <div class="offerings-header">
            <?php if ($detectedCount > 0): ?>
                <div class="offering-tags">
                    <?php foreach ($offerings as $key => $label): ?>
                        <?php if (($job[$key] ?? 0) == 1): ?>
                            <span class="offering-tag detected"><?php echo htmlspecialchars($label); ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-offerings">No ESG offerings detected in this role</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="job-details">
            <div class="detail-section">
                <h4>Basic Information</h4>
                <div class="detail-row">
                    <span class="label">Location:</span>
                    <span class="value"><?php echo htmlspecialchars($job['location'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Industry:</span>
                    <span class="value"><?php echo htmlspecialchars($job['industry'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Status:</span>
                    <span class="value"><?php echo htmlspecialchars($job['status'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Posted Date:</span>
                    <span class="value"><?php echo htmlspecialchars($job['posted_date'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Last Seen:</span>
                    <span class="value"><?php echo htmlspecialchars($job['last_seen_date'] ?? 'N/A'); ?></span>
                </div>
            </div>

            <div class="detail-section">
                <h4>Company Details</h4>
                <div class="detail-row">
                    <span class="label">Revenue Tier:</span>
                    <span class="value"><?php echo htmlspecialchars($job['revenue_tier'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Revenue Estimate:</span>
                    <span class="value"><?php echo linkifyText(htmlspecialchars($job['revenue_estimate'] ?? 'N/A')); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Parent Company:</span>
                    <span class="value"><?php echo htmlspecialchars($job['parent_company'] ?? 'N/A'); ?></span>
                </div>
            </div>

            <div class="detail-section">
                <h4>Assessment</h4>
                <div class="detail-row">
                    <span class="label">Fit Score:</span>
                    <span class="value"><?php echo htmlspecialchars($job['fit_score'] ?? 'N/A'); ?>/10</span>
                </div>
                <div class="detail-row">
                    <span class="label">Confidence:</span>
                    <span class="value"><?php echo htmlspecialchars($job['confidence'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Verification Level:</span>
                    <span class="value"><?php echo htmlspecialchars($job['verification_level'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Engagement Type:</span>
                    <span class="value"><?php echo htmlspecialchars($job['engagement_type'] ?? 'N/A'); ?></span>
                </div>
            </div>



            <?php if (!empty($contacts)): ?>
            <div class="detail-section">
                <h4>Likely Buyers/Managers</h4>
                <?php foreach ($contacts as $contact): ?>
                    <div class="contact-card">
                        <div class="detail-row">
                            <span class="label">Name:</span>
                            <span class="value"><?php echo htmlspecialchars($contact['name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Title:</span>
                            <span class="value"><?php echo htmlspecialchars($contact['title'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Confidence:</span>
                            <span class="value"><?php echo htmlspecialchars($contact['confidence'] ?? 'N/A'); ?></span>
                        </div>
                        <?php if (!empty($contact['source'])): ?>
                        <div class="detail-row">
                            <span class="label">Source:</span>
                            <span class="value">
                                <?php 
                                    $contactUrl = ensureProtocol($contact['source']);
                                    $contactDomain = getDomain($contactUrl);
                                ?>
                                <a href="<?php echo htmlspecialchars($contactUrl); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo htmlspecialchars($contactDomain); ?>
                                </a>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($job['job_description'])): ?>
            <div class="detail-section">
                <h4>Job Description</h4>
                <div class="long-text">
                    <?php echo linkifyText(nl2br(htmlspecialchars($job['job_description']))); ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="detail-section">
                <h4>Why Now</h4>
                <div class="long-text">
                    <?php echo linkifyText(nl2br(htmlspecialchars($job['why_now'] ?? 'N/A'))); ?>
                </div>
            </div>

            <div class="detail-section">
                <h4>Recommended Angle</h4>
                <div class="long-text">
                    <?php echo linkifyText(nl2br(htmlspecialchars($job['recommended_angle'] ?? 'N/A'))); ?>
                </div>
            </div>

            <?php if (!empty($job['source_link'])): ?>
            <div class="detail-section">
                <h4>Source</h4>
                <div class="detail-row">
                    <?php 
                        $cleanUrl = ensureProtocol($job['source_link']);
                        $domain = getDomain($cleanUrl);
                    ?>
                    <a href="<?php echo htmlspecialchars($cleanUrl); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo htmlspecialchars($domain); ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

<?php renderFooter(); ?>
