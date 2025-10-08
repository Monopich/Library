<?php
session_start();
error_reporting(0);
include('includes/config.php');

if (isset($_POST['change'])) {
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $newpassword = $_POST['newpassword'];
    $confirmpassword = $_POST['confirmpassword'];

    // Client-side check is nice, but let's also check server-side
    if ($newpassword !== $confirmpassword) {
        $_SESSION['toast'] = ['type' => 'warning', 'message' => 'New Password and Confirm Password do not match!'];
    } else {
        $hashedPassword = md5($newpassword);
        $sql = "SELECT EmailId FROM tblstudents WHERE EmailId=:email AND MobileNumber=:mobile";
        $query = $dbh->prepare($sql);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->bindParam(':mobile', $mobile, PDO::PARAM_STR);
        $query->execute();
        $student = $query->fetch(PDO::FETCH_OBJ);

        if ($student) {
            // Update password
            $update = "UPDATE tblstudents SET Password=:newpassword WHERE EmailId=:email AND MobileNumber=:mobile";
            $chngpwd = $dbh->prepare($update);
            $chngpwd->bindParam(':email', $email, PDO::PARAM_STR);
            $chngpwd->bindParam(':mobile', $mobile, PDO::PARAM_STR);
            $chngpwd->bindParam(':newpassword', $hashedPassword, PDO::PARAM_STR);
            $chngpwd->execute();

            $_SESSION['toast'] = ['type' => 'success', 'message' => 'Your password was successfully changed.'];
            $_SESSION['redirect'] = 'index.php';
        } else {
            $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Email or mobile number is invalid.'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Password Recovery | Library</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background: linear-gradient(135deg, #4e73df, #224abe);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.recover-card {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    padding: 40px;
    width: 100%;
    max-width: 420px;
}
.recover-title {
    font-weight: 600;
    text-align: center;
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
a {
    text-decoration: none;
}
.small-link {
    text-align: center;
    margin-top: 15px;
}
.login-logo {
            width: auto;
            height: 90px;
            object-fit: contain;
            margin-bottom: 15px;
        }
</style>
</head>
<body>

<div class="recover-card">
    <img src="assets/img/logo.png" alt="Library Logo" class="login-logo">
    <h3 class="recover-title">Password Recovery</h3>

    <form method="post">
        <div class="mb-1">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required autocomplete="off">
        </div>
        <div class="mb-1">
            <label class="form-label">Mobile Number</label>
            <input type="text" class="form-control" name="mobile" required autocomplete="off">
        </div>
        <div class="mb-1">
            <label class="form-label">New Password</label>
            <input type="password" class="form-control" name="newpassword" required autocomplete="off">
        </div>
        <div class="mb-1">
            <label class="form-label">Confirm Password</label>
            <input type="password" class="form-control" name="confirmpassword" required autocomplete="off">
        </div>
        <button type="submit" name="change" class="btn btn-primary mt-3">Change Password</button>
        <div class="small-link mt-3">
            <span>Remembered your password? <a href="index.php">Login here</a></span>
        </div>
    </form>
</div>

<!-- Toast -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1055">
  <div id="liveToast" class="toast align-items-center text-white border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="toast-message"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
// Show toast
if (isset($_SESSION['toast'])) {
    $toast = $_SESSION['toast'];
    $bg = match ($toast['type']) {
        'success' => 'bg-success',
        'danger' => 'bg-danger',
        'warning' => 'bg-warning text-dark',
        'info' => 'bg-info text-dark',
        default => 'bg-secondary',
    };
    $redirect = isset($_SESSION['redirect']) ? $_SESSION['redirect'] : '';
    echo "
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const toastEl = document.getElementById('liveToast');
        const toastBody = document.getElementById('toast-message');
        toastEl.className = 'toast align-items-center border-0 {$bg}';
        toastBody.textContent = '{$toast['message']}';
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
        " . ($redirect ? "setTimeout(() => { window.location.href = '{$redirect}'; }, 2000);" : "") . "
    });
    </script>";
    unset($_SESSION['toast'], $_SESSION['redirect']);
}
?>
</body>
</html>
