<?php
session_start();
error_reporting(0);
include('includes/config.php');

// Redirect if not logged in
if (strlen($_SESSION['login']) == 0) {
    header('location:index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Help & User Guide | Library System</title>

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
            padding: 40px 35px;
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
    <div class="help-container">
        <h1>📘 Library System Help & User Guide</h1>

        <h2>1️⃣ Logging In & Log Out</h2>
        <p> Use your registered email and password to log in. If you forget your password, click <strong>Forgot Password</strong> on the login page. And you also click button for <strong>Log Out</strong>.</p>

        <h2>2️⃣ Dashboard</h2>
        <ul>
            <li>Go to View <strong>Books List</strong>.</li>
            <li>Go to View <strong>EBooks List</strong>.</li>
            <li>Go to View <strong>Books Not Returned Yet</strong>.</li>
            <li>Go to View <strong>Total Issued Books</strong>.</li>
        </ul>

        <h2>3️⃣ Issued Books</h2>
        <ul>
            <li>You will see List of that you <strong>Issed</strong>.</li>
        </ul>

        <h2>4️⃣ EBooks</h2>
        <ul>
            <li>You can view list of <strong>EBooks</strong>.</li>
            <li>You can view Deatail of each <strong>EBooks</strong>.</li>
            <li>You can download of each <strong>EBooks</strong>.</li>
        </ul>

        <h2>5️⃣ My Profile & Change Password</h2>
        <ul>
            <li>You can view your <strong>Profile</strong>.</li>
            <li>You also change your <strong>Password</strong>.</li>
        </ul>

        <div class="contact">
            <h2>💬 Need More Help?</h2>
            <p>If you encounter any issues, please contact our support team:</p>
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
