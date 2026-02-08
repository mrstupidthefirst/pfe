<?php
/**
 * Logout Page
 * Handles user logout
 */
require_once 'config/config.php';

// Destroy session
session_destroy();

// Redirect to home page
redirect('index.php');
