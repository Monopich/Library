<?php
session_start();
error_reporting(0);
include('includes/config.php');

// 🔹 If already logged in, clear session (optional)
if ($_SESSION['login'] != '$email') {
    $_SESSION['login'] = '$student_id';
}

// 🔸 When user clicks LOGIN
if (isset($_POST['login'])) {
    $email = $_POST['emailid'];
    $password = md5($_POST['password']);

    // 🔸 1. Check Admin Login first
    $sqlAdmin = "SELECT UserName, Password FROM admin WHERE UserName=:email";
    $queryAdmin = $dbh->prepare($sqlAdmin);
    $queryAdmin->bindParam(':email', $email, PDO::PARAM_STR);
    $queryAdmin->execute();
    $adminResult = $queryAdmin->fetch(PDO::FETCH_OBJ);

    if ($adminResult) {
        if ($adminResult->Password === $password) {
            $_SESSION['alogin'] = $email;
            header("Location: admin/dashboard.php");
            exit;
        } else {
            $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Incorrect password for admin.'];
        }
    } else {
        // 🔸 2. Check Student Login
        $sqlStudent = "SELECT EmailId, Password, StudentId, Status FROM tblstudents WHERE EmailId=:email";
        $queryStudent = $dbh->prepare($sqlStudent);
        $queryStudent->bindParam(':email', $email, PDO::PARAM_STR);
        $queryStudent->execute();
        $student = $queryStudent->fetch(PDO::FETCH_OBJ);

        if ($student) {
            if ($student->Password === $password) {
                if ($student->Status == 1) {
                    $_SESSION['stdid'] = $student->StudentId;
                    $_SESSION['login'] = $email;
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Your account has been blocked. Please contact admin.'];
                }
            } else {
                $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Incorrect password.'];
            }
        } else {
            $_SESSION['toast'] = ['type' => 'warning', 'message' => 'Email not found.'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management | Login</title>
    <base href="/library/">

    <!-- ✅ Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #4e73df, #224abe);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            padding: 40px;
            width: 100%;
            max-width: 420px;
            text-align: center;
        }
        .login-logo {
            width: 300px;
            object-fit: contain;
            margin-bottom: 15px;
        }
        .login-title {
            font-weight: 600;
            margin-bottom: 25px;
            color: #224abe;
        }
        .form-control {
            border-radius: 10px;
        }
        .btn-primary {
            width: 100%;
            border-radius: 10px;
            background: #4e73df;
            border: none;
        }
        .btn-primary:hover {
            background: #3752c4;
        }
        .small-link {
            text-align: center;
            margin-top: 15px;
        }
        a {
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="login-card">
    <!-- ✅ Centered Logo -->
    <img src="assets/img/login-logo.png" alt="Library Logo" class="login-logo">
    <h3 class="login-title">Library Login</h3>

    <form method="post">
        <div class="mb-1 text-start">
            <label for="emailid" class="form-label">Email or Admin Username</label>
            <input type="text" class="form-control" name="emailid" id="emailid" required autocomplete="off">
        </div>
        <div class="mb-1 text-start">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" name="password" id="password" required autocomplete="off">
            <div class="small-link mt-2">
                <a href="user-forgot-password.php">Forgot password?</a>
            </div>
        </div>
        <button type="submit" name="login" class="btn btn-primary mt-3">Login</button>
        <div class="small-link mt-3">
            <span>Don't have an account? <a href="signup.php">Register here</a></span>
        </div>
    </form>
</div>

<!-- ✅ Toast Container -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1055">
  <div id="liveToast" class="toast align-items-center text-white border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="toast-message"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<!-- ✅ Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
// ✅ Toast Display
if (isset($_SESSION['toast'])) {
    $toast = $_SESSION['toast'];
    $bg = match ($toast['type']) {
        'success' => 'bg-success',
        'danger' => 'bg-danger',
        'warning' => 'bg-warning text-dark',
        'info' => 'bg-info text-dark',
        default => 'bg-secondary',
    };
    echo "
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const toastEl = document.getElementById('liveToast');
        const toastBody = document.getElementById('toast-message');
        toastEl.className = 'toast align-items-center border-0 {$bg}';
        toastBody.textContent = '{$toast['message']}';
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    });
    </script>";
    unset($_SESSION['toast']);
}
?>

</body>
</html>
