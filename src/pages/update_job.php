<?php
require_once __DIR__ . '/../constants.php';

// Get job ID from URL
$jobId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($jobId === 0) {
    header('Location: ?page=dashboard');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = $db->getConnection();
        
        // Prepare update statement
        $stmt = $conn->prepare("
            UPDATE jobs SET 
                company = ?,
                role_title = ?,
                location = ?,
                industry = ?,
                status = ?,
                posted_date = ?,
                last_seen_date = ?,
                employment_type = ?,
                why_now = ?,
                verification_level = ?,
                confidence = ?,
                revenue_tier = ?,
                revenue_estimate = ?,
                revenue_confidence = ?,
                fit_score = ?,
                engagement_type = ?,
                job_description = ?,
                job_overview = ?,
                recommended_angle = ?,
                source_link = ?,
                parent_company = ?
            WHERE id = ?
        ");
        
        $stmt->bind_param(
            "sssssssssssssssssssssi",
            $_POST['company'],
            $_POST['role_title'],
            $_POST['location'],
            $_POST['industry'],
            $_POST['status'],
            $_POST['posted_date'],
            $_POST['last_seen_date'],
            $_POST['employment_type'],
            $_POST['why_now'],
            $_POST['verification_level'],
            $_POST['confidence'],
            $_POST['revenue_tier'],
            $_POST['revenue_estimate'],
            $_POST['revenue_confidence'],
            $_POST['fit_score'],
            $_POST['engagement_type'],
            $_POST['job_description'],
            $_POST['job_overview'],
            $_POST['recommended_angle'],
            $_POST['source_link'],
            $_POST['parent_company'],
            $jobId
        );
        
        if ($stmt->execute()) {
            header('Location: ?page=details&id=' . $jobId . '&updated=1');
            exit;
        } else {
            $error = 'Failed to update job: ' . $stmt->error;
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Fetch job data
try {
    $stmt = $db->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->bind_param('i', $jobId);
    $stmt->execute();
    $result = $stmt->get_result();
    $job = $result->fetch_assoc();
    $stmt->close();

    if (!$job) {
        header('Location: ?page=dashboard');
        exit;
    }
} catch (Exception $e) {
    showError('Failed to load job: ' . $e->getMessage());
}

renderHeader('Edit Job - ' . htmlspecialchars($job['company']));
?>

    <main>
        <div class="back-link">
            <a href="?page=details&id=<?php echo $job['id']; ?>">← Back to Job Details</a>
        </div>

        <h2>Edit Job: <?php echo htmlspecialchars($job['company']); ?></h2>

        <?php if (isset($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="job-edit-form">
            <div class="form-section">
                <h3>Basic Information</h3>
                
                <div class="form-group">
                    <label for="company">Company *</label>
                    <input type="text" id="company" name="company" value="<?php echo htmlspecialchars($job['company'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="role_title">Role Title *</label>
                    <input type="text" id="role_title" name="role_title" value="<?php echo htmlspecialchars($job['role_title'] ?? ''); ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($job['location'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="industry">Industry</label>
                        <input type="text" id="industry" name="industry" value="<?php echo htmlspecialchars($job['industry'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <?php foreach (VALID_JOB_STATUSES as $status): ?>
                                <option value="<?php echo htmlspecialchars($status); ?>" <?php echo ($job['status'] ?? 'New') === $status ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($status); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="employment_type">Employment Type</label>
                        <input type="text" id="employment_type" name="employment_type" value="<?php echo htmlspecialchars($job['employment_type'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="posted_date">Posted Date</label>
                        <input type="date" id="posted_date" name="posted_date" value="<?php echo htmlspecialchars($job['posted_date'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="last_seen_date">Last Seen Date</label>
                        <input type="date" id="last_seen_date" name="last_seen_date" value="<?php echo htmlspecialchars($job['last_seen_date'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Company Details</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="revenue_tier">Revenue Tier</label>
                        <input type="text" id="revenue_tier" name="revenue_tier" value="<?php echo htmlspecialchars($job['revenue_tier'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="revenue_estimate">Revenue Estimate</label>
                        <input type="text" id="revenue_estimate" name="revenue_estimate" value="<?php echo htmlspecialchars($job['revenue_estimate'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="revenue_confidence">Revenue Confidence</label>
                        <input type="text" id="revenue_confidence" name="revenue_confidence" value="<?php echo htmlspecialchars($job['revenue_confidence'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="parent_company">Parent Company</label>
                    <input type="text" id="parent_company" name="parent_company" value="<?php echo htmlspecialchars($job['parent_company'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-section">
                <h3>Assessment</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fit_score">Fit Score (0-10)</label>
                        <input type="number" id="fit_score" name="fit_score" min="0" max="10" value="<?php echo htmlspecialchars($job['fit_score'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="confidence">Confidence</label>
                        <input type="text" id="confidence" name="confidence" value="<?php echo htmlspecialchars($job['confidence'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="verification_level">Verification Level</label>
                        <input type="text" id="verification_level" name="verification_level" value="<?php echo htmlspecialchars($job['verification_level'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="engagement_type">Engagement Type</label>
                    <input type="text" id="engagement_type" name="engagement_type" value="<?php echo htmlspecialchars($job['engagement_type'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-section">
                <h3>Details</h3>
                
                <div class="form-group">
                    <label for="job_overview">Job Overview</label>
                    <textarea id="job_overview" name="job_overview" rows="3"><?php echo htmlspecialchars($job['job_overview'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="job_description">Job Description</label>
                    <textarea id="job_description" name="job_description" rows="8"><?php echo htmlspecialchars($job['job_description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="why_now">Why Now</label>
                    <textarea id="why_now" name="why_now" rows="4"><?php echo htmlspecialchars($job['why_now'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="recommended_angle">Recommended Angle</label>
                    <textarea id="recommended_angle" name="recommended_angle" rows="4"><?php echo htmlspecialchars($job['recommended_angle'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="source_link">Source Link</label>
                    <input type="url" id="source_link" name="source_link" value="<?php echo htmlspecialchars($job['source_link'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="?page=details&id=<?php echo $job['id']; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </main>

<?php renderFooter(); ?>
