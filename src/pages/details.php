<?php
require_once __DIR__ . '/../OfferingTypes.php';
require_once __DIR__ . '/../constants.php';

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

        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
            <div>
                <h2 class="editable-heading" contenteditable="false" data-field="company"><?php echo htmlspecialchars($job['company']); ?></h2>
                <h3 class="editable-heading" contenteditable="false" data-field="role_title"><?php echo htmlspecialchars($job['role_title']); ?></h3>
            </div>
            <div class="button-group">
                <button id="edit-button" class="edit-button">Edit</button>
                <button id="save-changes" class="save-button" style="display: none;">Save</button>
                <button id="cancel-changes" class="cancel-button" style="display: none;">Cancel</button>
            </div>
        </div>

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
                    <span class="value">
                        <input type="text" class="editable-field" data-field="location" value="<?php echo htmlspecialchars($job['location'] ?? ''); ?>" readonly />
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Industry:</span>
                    <span class="value">
                        <input type="text" class="editable-field" data-field="industry" value="<?php echo htmlspecialchars($job['industry'] ?? ''); ?>" readonly />
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Status:</span>
                    <span class="value">
                        <select class="status-dropdown editable-field" data-field="status" data-job-id="<?php echo $job['id']; ?>" disabled>
                            <?php foreach (VALID_JOB_STATUSES as $status): ?>
                                <option value="<?php echo htmlspecialchars($status); ?>" <?php echo ($job['status'] ?? 'New') === $status ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($status); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Posted Date:</span>
                    <span class="value">
                        <input type="text" class="editable-field" data-field="posted_date" value="<?php echo htmlspecialchars($job['posted_date'] ?? ''); ?>" readonly />
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Last Seen:</span>
                    <span class="value">
                        <input type="text" class="editable-field" data-field="last_seen_date" value="<?php echo htmlspecialchars($job['last_seen_date'] ?? ''); ?>" readonly />
                    </span>
                </div>
            </div>

            <div class="detail-section">
                <h4>Company Details</h4>
                <div class="detail-row">
                    <span class="label">Revenue Tier:</span>
                    <span class="value">
                        <input type="text" class="editable-field" data-field="revenue_tier" value="<?php echo htmlspecialchars($job['revenue_tier'] ?? ''); ?>" readonly />
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Revenue Estimate:</span>
                    <span class="value">
                        <input type="text" class="editable-field" data-field="revenue_estimate" value="<?php echo htmlspecialchars($job['revenue_estimate'] ?? ''); ?>" readonly />
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Parent Company:</span>
                    <span class="value">
                        <input type="text" class="editable-field" data-field="parent_company" value="<?php echo htmlspecialchars($job['parent_company'] ?? ''); ?>" readonly />
                    </span>
                </div>
            </div>

            <div class="detail-section">
                <h4>Assessment</h4>
                <div class="detail-row">
                    <span class="label">Fit Score:</span>
                    <span class="value">
                        <input type="number" class="editable-field" data-field="fit_score" value="<?php echo htmlspecialchars($job['fit_score'] ?? ''); ?>" min="0" max="10" step="0.1" style="width: 80px;" readonly /> / 10
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Confidence:</span>
                    <span class="value">
                        <input type="text" class="editable-field" data-field="confidence" value="<?php echo htmlspecialchars($job['confidence'] ?? ''); ?>" readonly />
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Verification Level:</span>
                    <span class="value">
                        <input type="text" class="editable-field" data-field="verification_level" value="<?php echo htmlspecialchars($job['verification_level'] ?? ''); ?>" readonly />
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Engagement Type:</span>
                    <span class="value">
                        <input type="text" class="editable-field" data-field="engagement_type" value="<?php echo htmlspecialchars($job['engagement_type'] ?? ''); ?>" readonly />
                    </span>
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

            <div class="detail-section">
                <h4>Job Description</h4>
                <div class="long-text">
                    <textarea class="editable-textarea" data-field="job_description" rows="10" readonly><?php echo htmlspecialchars($job['job_description'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="detail-section">
                <h4>Why Now</h4>
                <div class="long-text">
                    <textarea class="editable-textarea" data-field="why_now" rows="6" readonly><?php echo htmlspecialchars($job['why_now'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="detail-section">
                <h4>Recommended Angle</h4>
                <div class="long-text">
                    <textarea class="editable-textarea" data-field="recommended_angle" rows="6" readonly><?php echo htmlspecialchars($job['recommended_angle'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="detail-section">
                <h4>Source</h4>
                <div class="detail-row">
                    <input type="text" class="editable-field" data-field="source_link" value="<?php echo htmlspecialchars($job['source_link'] ?? ''); ?>" placeholder="Add source URL" style="width: 100%;" readonly />
                </div>
            </div>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const jobId = <?php echo $job['id']; ?>;
        const editButton = document.getElementById('edit-button');
        const saveButton = document.getElementById('save-changes');
        const cancelButton = document.getElementById('cancel-changes');
        const editableFields = document.querySelectorAll('.editable-field, .editable-textarea');
        const editableHeadings = document.querySelectorAll('.editable-heading');
        const statusDropdown = document.querySelector('.status-dropdown');
        
        let originalValues = {};
        let isEditMode = false;
        
        // Store original values
        function storeOriginalValues() {
            originalValues = {};
            editableFields.forEach(field => {
                originalValues[field.dataset.field] = field.value;
            });
            editableHeadings.forEach(heading => {
                originalValues[heading.dataset.field] = heading.textContent.trim();
            });
        }
        
        // Enable edit mode
        editButton.addEventListener('click', function() {
            isEditMode = true;
            storeOriginalValues();
            
            // Enable all fields
            editableFields.forEach(field => {
                field.removeAttribute('readonly');
                field.removeAttribute('disabled');
            });
            
            editableHeadings.forEach(heading => {
                heading.contentEditable = 'true';
            });
            
            // Toggle buttons
            editButton.style.display = 'none';
            saveButton.style.display = 'inline-block';
            cancelButton.style.display = 'inline-block';
        });
        
        // Cancel changes
        cancelButton.addEventListener('click', function() {
            isEditMode = false;
            
            // Restore original values
            editableFields.forEach(field => {
                field.value = originalValues[field.dataset.field] || '';
                field.setAttribute('readonly', 'readonly');
                if (field.tagName === 'SELECT') {
                    field.setAttribute('disabled', 'disabled');
                }
            });
            
            editableHeadings.forEach(heading => {
                heading.textContent = originalValues[heading.dataset.field] || '';
                heading.contentEditable = 'false';
            });
            
            // Toggle buttons
            editButton.style.display = 'inline-block';
            saveButton.style.display = 'none';
            cancelButton.style.display = 'none';
        });
        
        // Save all changes
        saveButton.addEventListener('click', function() {
            const updates = {};
            
            // Collect all field values
            editableFields.forEach(field => {
                updates[field.dataset.field] = field.value;
            });
            
            editableHeadings.forEach(heading => {
                updates[heading.dataset.field] = heading.textContent.trim();
            });
            
            // Disable buttons during update
            saveButton.disabled = true;
            cancelButton.disabled = true;
            saveButton.textContent = 'Saving...';
            
            // Send AJAX request to update job
            fetch('?page=update_job', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    job_id: jobId,
                    updates: updates
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Server returned ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showSuccessMessage('Changes saved successfully!');
                    
                    // Disable all fields
                    isEditMode = false;
                    editableFields.forEach(field => {
                        field.setAttribute('readonly', 'readonly');
                        if (field.tagName === 'SELECT') {
                            field.setAttribute('disabled', 'disabled');
                        }
                    });
                    
                    editableHeadings.forEach(heading => {
                        heading.contentEditable = 'false';
                    });
                    
                    // Toggle buttons
                    editButton.style.display = 'inline-block';
                    saveButton.style.display = 'none';
                    cancelButton.style.display = 'none';
                } else {
                    alert('Failed to save changes: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to save changes: ' + error.message);
            })
            .finally(() => {
                saveButton.disabled = false;
                cancelButton.disabled = false;
                saveButton.textContent = 'Save';
            });
        });
        
        // Helper function to show success messages
        function showSuccessMessage(message) {
            const successMsg = document.createElement('div');
            successMsg.className = 'success-message';
            successMsg.textContent = message;
            successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #4CAF50; color: white; padding: 15px 20px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.2); z-index: 1000;';
            document.body.appendChild(successMsg);
            
            setTimeout(() => {
                successMsg.remove();
            }, 3000);
        }
    });
    </script>

<?php renderFooter(); ?>
