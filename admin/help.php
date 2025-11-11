<?php
session_start();
error_reporting(0);
include('includes/config.php');

// Redirect if not logged in
if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['help_title']; ?> | Library System</title>

    <!-- ✅ Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">

    <style>
        body {
            background-color: #f8f9fa;
            font-family: "Segoe UI", sans-serif;
        }

        .help-container {
            max-width: 900px;
            margin: 50px auto;
            background: #ffffff;
            padding: 60px;
            padding-top: 60px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        h1 {
            text-align: center;
            margin-bottom: 35px;
            color: #0d6efd;
            font-weight: 600;
        }

        h2 {
            margin-top: 25px;
            color: #343a40;
            font-size: 1.25rem;
        }

        p, li {
            color: #555;
            line-height: 1.6;
        }

        ul {
            margin-left: 20px;
        }

        .contact {
            margin-top: 40px;
            padding: 20px;
            background: #eafbea;
            border-left: 6px solid #198754;
            border-radius: 8px;
        }

        a {
            color: #0d6efd;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        /* Responsive fix */
        @media (max-width: 576px) {
            .help-container {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>

<!-- ✅ Include standard header -->
<?php include('includes/header.php'); ?>

<div class="container my-3">
    <div class="help-container pt-4">
        <h1>📘 <?= $lang['help_title']; ?></h1>

        <h2>1 <?= $lang['help_login_title']; ?></h2>
        <p><?= $lang['help_login_text']; ?></p>

        <h2>2 <?= $lang['help_dashboard_title']; ?></h2>
        <ul>
            <li><?= $lang['help_dashboard_1']; ?></li>
            <li><?= $lang['help_dashboard_2']; ?></li>
            <li><?= $lang['help_dashboard_3']; ?></li>
            <li><?= $lang['help_dashboard_4']; ?></li>
            <li><?= $lang['help_dashboard_5']; ?></li>
            <li><?= $lang['help_dashboard_6']; ?></li>
        </ul>

        <h2>3 <?= $lang['help_categories_title']; ?></h2>
        <ul>
            <li><?= $lang['help_categories_1']; ?></li>
            <li><?= $lang['help_categories_2']; ?></li>
        </ul>

        <h2>4 <?= $lang['help_authors_title']; ?></h2>
        <ul>
            <li><?= $lang['help_authors_1']; ?></li>
            <li><?= $lang['help_authors_2']; ?></li>
        </ul>

        <h2>5 <?= $lang['help_books_title']; ?></h2>
        <ul>
            <li><?= $lang['help_books_1']; ?></li>
            <li><?= $lang['help_books_2']; ?></li>
        </ul>

        <h2>6 <?= $lang['help_ebooks_title']; ?></h2>
        <ul>
            <li><?= $lang['help_ebooks_1']; ?></li>
            <li><?= $lang['help_ebooks_2']; ?></li>
            <li><?= $lang['help_ebooks_3']; ?></li>
        </ul>

        <h2>7 <?= $lang['help_issued_title']; ?></h2>
        <ul>
            <li><?= $lang['help_issued_1']; ?></li>
            <li><?= $lang['help_issued_2']; ?></li>
        </ul>

        <h2>8 <?= $lang['help_students_title']; ?></h2>
        <ul>
            <li><?= $lang['help_students_1']; ?></li>
            <li><?= $lang['help_students_2']; ?></li>
            <li><?= $lang['help_students_3']; ?></li>
            <li><?= $lang['help_students_4']; ?></li>
        </ul>

        <h2>9 <?= $lang['help_change_password']; ?></h2>
        <ul>
            <li><?= $lang['help_change_password_text']; ?></li>
        </ul>

        <div class="contact">
            <h2>💬 <?= $lang['help_contact_title']; ?></h2>
            <p><?= $lang['help_contact_text']; ?></p>
            <ul class="list-unstyled">
                <li><i class="fa fa-envelope me-2 text-primary"></i>
                    <a href="mailto:support@librarysystem.com">admin@rtc.edu.kh</a>
                </li>
                <li><i class="fa fa-phone me-2 text-primary"></i>
                    +855 86 318 261
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- ✅ Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
