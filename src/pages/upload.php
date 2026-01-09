<?php
// Load required classes
require_once SRC_PATH . '/JobImporter.php';

$message = null;
$messageType = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['json_data'])) {
    $jsonData = trim($_POST['json_data']);

    if (empty($jsonData)) {
        $message = 'Please paste JSON data';
        $messageType = 'error';
    } else {
        try {
            $importer = new JobImporter($db);
            $result = $importer->importFromJSON($jsonData);

            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

renderHeader('JobLead - Upload Jobs (Internal)');
?>

    <main>
        <h2>Upload Job Leads (JSON)</h2>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <textarea name="json_data" placeholder="Paste JSON here..." required></textarea>
            <button type="submit">Upload</button>
        </form>
    </main>

<?php renderFooter(); ?>
