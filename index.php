<?php
// Main entry point for AhRawr Shop E-commerce Application
require_once 'auth.php';

// Check if user is logged in, redirect accordingly
if (is_logged_in()) {
    // Logged in users go to their profile
    header('Location: profile.php');
} else {
    // Non-logged in users see the product catalog
    header('Location: view_product.php');
}
exit;
?>