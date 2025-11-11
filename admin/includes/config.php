<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!defined('DB_HOST')) define('DB_HOST', 'localhost');
if(!defined('DB_USER')) define('DB_USER', 'library_user');
if(!defined('DB_PASS')) define('DB_PASS', 'Library123!');
if(!defined('DB_NAME')) define('DB_NAME', 'library');


try {
    $dbh = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
    );
} catch (PDOException $e) {
    exit("Error: " . $e->getMessage());
}

// ===============================
// 🌐 MULTI-LANGUAGE CONFIGURATION
// ===============================

// Default to English
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Change language via dropdown
if (isset($_GET['lang'])) {
    $selectedLang = $_GET['lang'] === 'kh' ? 'kh' : 'en';
    $_SESSION['lang'] = $selectedLang;

    // Redirect to same page without query string
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Load current page
$currentPage = basename($_SERVER['PHP_SELF']);

// Load language file
$langDir = realpath(__DIR__ . '/../../languages') . '/'; // admin/includes/../../languages
$langFile = $langDir . ($_SESSION['lang'] ?? 'en') . '.php';

// Fallback to English if file not found
if (!file_exists($langFile)) {
    $langFile = $langDir . 'en.php';
}

$lang = include($langFile);
?>
