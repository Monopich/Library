<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');

if (isset($_POST['change'])) {
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $newpassword = $_POST['newpassword'];
    $confirmpassword = $_POST['confirmpassword'];

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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
body {
    background: #f0f2f5;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Segoe UI', sans-serif;
}
.recover-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    padding: 40px 30px;
    width: 100%;
    max-width: 420px;
    text-align: center;
}
.recover-title {
    font-weight: 600;
    color: #1a3d7c;
    margin-bottom: 25px;
}
.input-group {
    position: relative;
}
.input-group .form-control {
    border-radius: 8px !important;
    padding-right: 40px;
}
.input-group .toggle-password {
    position: absolute;
    top: 50%;
    right: 10px;
    transform: translateY(-50%);
    border: none;
    background: none;
    cursor: pointer;
    font-size: 1.1rem;
    color: #6c757d;
    padding: 0;
    margin: 0;
}
.btn-primary {
    width: 100%;
    border-radius: 8px;
    padding: 10px;
    font-weight: 500;
}
.btn-primary:hover { background: #1a3d7c; }
.small-link { font-size: 0.875rem; margin-top: 10px; display: block; }
.login-logo {
    width: 150px;
    margin-bottom: 20px;
}
</style>
</head>
<body>

<div class="recover-card">
    <img src="assets/img/login-logo.png" alt="Library Logo" class="login-logo">
    <h3 class="recover-title">Password Recovery</h3>

    <form method="post">
        <div class="mb-1 text-start">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required autocomplete="off">
        </div>
        <div class="mb-1 text-start">
            <label class="form-label">Mobile Number</label>
            <input type="text" class="form-control" name="mobile" required autocomplete="off">
        </div>
        <div class="mb-1 text-start">
            <label class="form-label">New Password</label>
            <div class="input-group">
                <input type="password" class="form-control" name="newpassword" id="newpassword" required autocomplete="off">
                <button type="button" class="toggle-password" data-target="newpassword"><i class="bi bi-eye"></i></button>
            </div>
        </div>
        <div class="mb-1 text-start">
            <label class="form-label">Confirm Password</label>
            <div class="input-group">
                <input type="password" class="form-control" name="confirmpassword" id="confirmpassword" required autocomplete="off">
                <button type="button" class="toggle-password" data-target="confirmpassword"><i class="bi bi-eye"></i></button>
            </div>
        </div>
        <button type="submit" name="change" class="btn btn-primary mt-3">Change Password</button>
        <div class="small-link mt-3">
            <span>Remembered your password? <a href="index.php">Login here</a></span>
        </div>
    </form>
</div>

<div class="position-fixed top-0 end-0 p-3" style="z-index: 1055">
  <div id="liveToast" class="toast align-items-center text-white border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="toast-message"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toast
<?php
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
    document.addEventListener('DOMContentLoaded', () => {
        const toastEl = document.getElementById('liveToast');
        const toastBody = document.getElementById('toast-message');
        toastEl.className = 'toast align-items-center border-0 {$bg}';
        toastBody.textContent = '{$toast['message']}';
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
        " . ($redirect ? "setTimeout(() => { window.location.href = '{$redirect}'; }, 2000);" : "") . "
    });";
    unset($_SESSION['toast'], $_SESSION['redirect']);
}
?>

// Toggle password visibility
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function(){
        const target = document.getElementById(this.dataset.target);
        const icon = this.querySelector('i');
        if(target.type === 'password'){
            target.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            target.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    });
});
</script>
</body>
</html>
