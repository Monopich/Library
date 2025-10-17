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

<div class="container my-5">
    <div class="help-container">
        <h1>📘 Library System Help & User Guide</h1>

        <h2>1️⃣ Logging In & Log Out</h2>
        <p> Use your registered email and password to log in. If you forget your password, click <strong>Forgot Password</strong> on the login page. And you also click button for <strong>Log Out</strong>.</p>

        <h2>2️⃣ Dashboard</h2>
        <ul>
            <li>Go to <strong>Books List</strong>.</li>
            <li>Go to <strong>EBooks List</strong>.</li>
            <li>Go to <strong>Books Not Returned Yet</strong>.</li>
            <li>Go to <strong>Registered Users</strong>.</li>
            <li>Go to <strong>Authors List</strong>.</li>
            <li>Go to <strong>Categories List</strong>.</li>
        </ul>

        <h2>3️⃣ Categories</h2>
        <ul>
            <li>You can manage <strong>Categories</strong> by <strong>View</strong>, <strong>Create</strong>, <strong>Edit</strong> and  <strong>Delete</strong>.</li>
            <li>You can search <strong>Categories</strong>.</li>
        </ul>

        <h2>4️⃣ Authors</h2>
        <ul>
            <li>You can manage <strong>Authors</strong> by <strong>View</strong>, <strong>Create</strong>, <strong>Edit</strong> and  <strong>Delete</strong>.</li>
            <li>You can search <strong>Authors</strong>.</li>
        </ul>

        <h2>5️⃣ Books</h2>
        <ul>
            <li>You can manage <strong>Books</strong> by <strong>View</strong>, <strong>Create</strong>, <strong>Edit</strong> and  <strong>Delete</strong>.</li>
            <li>You can search <strong>Books</strong>.</li>
        </ul>

        <h2>6️⃣ EBooks</h2>
        <ul>
            <li>You can manage <strong>EBooks</strong> by <strong>View</strong>, <strong>Create</strong>, <strong>Edit</strong> and  <strong>Delete</strong>.</li>
            <li>You can upload <strong>PDF</strong> file of <strong>EBooks</strong>.</li>
            <li>You can search <strong>EBooks</strong>.</li>
        </ul>

        <h2>7️⃣ Issued Books</h2>
        <ul>
            <li>You can manage <strong>Issued Books</strong> by <strong>View</strong>, <strong>Create</strong>, <strong>Return</strong> Books from Students.</li>
            <li>You can search <strong>Issued Books</strong>.</li>
        </ul>

        <h2>8️⃣ Students</h2>
        <ul>
            <li>You can view list of <strong>Students</strong>.</li>
            <li>You can make action as <strong>Inactivate</strong> and <strong>Activate</strong> for students.</li>
            <li>You can view detail of each <strong>Student</strong>.</li>
            <li>You also can search <strong>Students</strong>.</li>
        </ul>

        <h2>9️⃣ Change Password</h2>
        <ul>
            <li>You can change your <strong>Password</strong>.</li>
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
