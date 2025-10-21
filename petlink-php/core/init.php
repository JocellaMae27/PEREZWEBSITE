<?php
// This script checks if the application has been installed.
// If not, it redirects to the setup page.

// Check for the lock file. The path is relative to where this file is included from (the root).
if (!file_exists('config/installed.lock')) {
    // Redirect to the setup page.
    header('Location: setup/setup.php');
    // Stop script execution to prevent the rest of the page from loading.
    exit();
}