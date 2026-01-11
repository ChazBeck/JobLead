<?php
require_once __DIR__ . '/../OfferingTypes.php';
require_once __DIR__ . '/../constants.php';

// Query all jobs with their primary contact
try {
    $query = "
        SELECT 
            j.id,
            j.company,
            j.role_title,
            j.industry,
            j.status,
            j.ai_analyzed_at,
            j.sustainability_reporting,
            j.data_management_esg,
            j.esg_strategy_roadmapping,
            j.regulatory_compliance,
            j.esg_ratings_rankings,
            j.stakeholder_engagement,
            j.governance_policy,
            j.technology_tools,
            c.name as contact_name,
            c.title as contact_title
        FROM jobs j
        LEFT JOIN contacts c ON j.id = c.job_id
        GROUP BY j.id
        ORDER BY j.created_at DESC
    ";

    $result = $db->query($query);
    $jobs = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $jobs[] = $row;
        }
    }
} catch (Exception $e) {
    showError('Failed to load jobs: ' . $e->getMessage());
}

renderHeader('JobLead - Jobs Dashboard (Internal)');
?>

    <main>
        <h2>Job Leads Dashboard</h2>
        
        <?php if (empty($jobs)): ?>
            <p>No jobs found. <a href="?page=upload">Upload some jobs</a> to get started.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Job Role</th>
                        <th>Industry</th>
                        <th>Hiring Manager Name</th>
                        <th>Hiring Manager Title</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): ?>
                        <?php
                        // Get detected offerings using centralized class
                        $offeringLabels = OfferingTypes::getLabels();
                        $detectedOfferings = [];
                        foreach ($offeringLabels as $key => $label) {
                            if (($job[$key] ?? 0) == 1) {
                                $detectedOfferings[] = $label;
                            }
                        }
                        $hasAnalysis = $job['ai_analyzed_at'] !== null;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($job['company']); ?></td>
                            <td>
                                <div class="role-title"><?php echo htmlspecialchars($job['role_title']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($job['industry'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($job['contact_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($job['contact_title'] ?? 'N/A'); ?></td>
                            <td>
                                <select class="status-dropdown" data-job-id="<?php echo $job['id']; ?>">
                                    <?php foreach (VALID_JOB_STATUSES as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>" <?php echo ($job['status'] ?? 'New') === $status ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($status); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><a href="?page=details&id=<?php echo $job['id']; ?>">View Details</a></td>
                        </tr>
                        <?php if ($hasAnalysis && !empty($detectedOfferings)): ?>
                        <tr class="offerings-row">
                            <td colspan="7">
                                <div class="role-offerings">
                                    <?php foreach ($detectedOfferings as $offering): ?>
                                        <span class="offering-badge"><?php echo htmlspecialchars($offering); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const statusDropdowns = document.querySelectorAll('.status-dropdown');
        
        statusDropdowns.forEach(dropdown => {
            dropdown.addEventListener('change', function() {
                const jobId = this.dataset.jobId;
                const newStatus = this.value;
                const originalValue = this.querySelector('option[selected]').value;
                
                // Disable dropdown during update
                this.disabled = true;
                
                // Send AJAX request to update status
                fetch('?page=update_status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        job_id: jobId,
                        status: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the selected attribute
                        this.querySelectorAll('option').forEach(opt => {
                            opt.removeAttribute('selected');
                        });
                        this.querySelector(`option[value="${newStatus}"]`).setAttribute('selected', 'selected');
                        
                        // Show success feedback (optional)
                        console.log('Status updated successfully');
                    } else {
                        alert('Failed to update status: ' + (data.message || 'Unknown error'));
                        this.value = originalValue;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to update status');
                    this.value = originalValue;
                })
                .finally(() => {
                    this.disabled = false;
                });
            });
        });
    });
    </script>

<?php renderFooter(); ?>
