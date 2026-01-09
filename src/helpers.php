<?php
/**
 * Helper functions for JobLead application
 */

/**
 * Extract domain from URL for display
 * @param string $url URL to extract domain from
 * @return string Domain name
 */
function getDomain($url) {
    if (empty($url)) {
        return '';
    }
    $parsed = parse_url($url);
    return $parsed['host'] ?? $url;
}

/**
 * Ensure URL has proper protocol and clean up malformed URLs
 * @param string $url URL to clean
 * @return string Cleaned URL with protocol
 */
function ensureProtocol($url) {
    if (empty($url)) {
        return '';
    }
    
    // Remove leading/trailing parentheses and spaces
    $url = trim($url, " \t\n\r\0\x0B()");
    
    // If URL doesn't start with http:// or https://, add https://
    if (!preg_match('/^https?:\/\//i', $url)) {
        // If it starts with //, add https:
        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }
        // Otherwise add https://
        return 'https://' . $url;
    }
    return $url;
}

/**
 * Convert URLs in text to clickable links showing just domain
 * Preserves parentheses around URLs for readability
 * @param string $text Text containing URLs
 * @return string Text with URLs converted to links
 */
function linkifyText($text) {
    if (empty($text)) {
        return $text;
    }
    
    // Match URLs, capturing whether they're wrapped in parentheses
    $pattern = '/(\()?(https?:\/\/[^\s\)]+)(\))?/i';
    
    $text = preg_replace_callback($pattern, function($matches) {
        $openParen = $matches[1] ?? '';
        $fullUrl = $matches[2];
        $closeParen = $matches[3] ?? '';
        
        // Clean the URL - remove trailing punctuation
        $fullUrl = rtrim($fullUrl, '.,;:!?');
        
        // Get domain for display
        $parsed = parse_url($fullUrl);
        $domain = $parsed['host'] ?? $fullUrl;
        
        // If URL was in parentheses, keep them around the link
        if ($openParen && $closeParen) {
            return '(<a href="' . htmlspecialchars($fullUrl) . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($domain) . '</a>)';
        }
        
        // Return link without parentheses
        return '<a href="' . htmlspecialchars($fullUrl) . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($domain) . '</a>';
    }, $text);
    
    return $text;
}

/**
 * Render page header
 * @param string $title Page title
 */
function renderHeader($title = 'JobLead Tracker') {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/style.css">
</head>
<body>
    <header>
        <h1>JobLead Tracker</h1>
    </header>

    <nav>
        <ul>
            <li><a href="?page=dashboard">Dashboard</a></li>
            <li><a href="?page=upload">Upload Jobs</a></li>
        </ul>
    </nav>
    <?php
}

/**
 * Render page footer
 */
function renderFooter() {
    ?>
</body>
</html>
    <?php
}

/**
 * Display error message and exit
 * @param string $message Error message
 */
function showError($message) {
    renderHeader('Error');
    ?>
    <main>
        <div class="message error">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <p><a href="?page=dashboard">Return to Dashboard</a></p>
    </main>
    <?php
    renderFooter();
    exit;
}
