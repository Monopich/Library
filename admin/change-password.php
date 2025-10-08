<?php
session_start();
include('includes/config.php');
error_reporting(0);

if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit;
}

$msg = $error = null;
$redirect = false;

if (isset($_POST['change'])) {
    $password = md5($_POST['password']);
    $newpassword = md5($_POST['newpassword']);
    $username = $_SESSION['alogin'];

    $sql = "SELECT Password FROM admin WHERE UserName=:username AND Password=:password";
    $query = $dbh->prepare($sql);
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->bindParam(':password', $password, PDO::PARAM_STR);
    $query->execute();

    if ($query->rowCount() > 0) {
        $update = "UPDATE admin SET Password=:newpassword WHERE UserName=:username";
        $chngpwd1 = $dbh->prepare($update);
        $chngpwd1->bindParam(':username', $username, PDO::PARAM_STR);
        $chngpwd1->bindParam(':newpassword', $newpassword, PDO::PARAM_STR);
        $chngpwd1->execute();
        $msg = "Your password was successfully changed!";
        $redirect = true; // flag to redirect after toast
    } else {
        $error = "Your current password is incorrect.";
    }
}

// Toast notification
$toast = null;
if ($msg) $toast = ['msg'=>$msg, 'type'=>'success'];
if ($error) $toast = ['msg'=>$error, 'type'=>'danger'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Change Password | Online Library</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
.toast-container { z-index: 1100; }
.table thead th {
    background-color: #007bff;
    color: #fff;
    text-align: center;
}
.table tbody td {
    vertical-align: middle;
    text-align: center;
}
</style>

<script>
function valid() {
    if(document.chngpwd.newpassword.value !== document.chngpwd.confirmpassword.value) {
        alert("New Password and Confirm Password do not match!");
        document.chngpwd.confirmpassword.focus();
        return false;
    }
    return true;
}
</script>
</head>
<body>
<?php include('includes/header.php'); ?>

<div class="container my-3">
    <h2 class="fw-bold mb-4 text-primary">Change Password</h2>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white fw-bold">
                    Change Password
                </div>
                <div class="card-body">
                    <form method="post" name="chngpwd" onsubmit="return valid();">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="password" class="form-control" required autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="newpassword" class="form-control" required autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirmpassword" class="form-control" required autocomplete="off">
                        </div>
                        <button type="submit" name="change" class="btn btn-success w-100 text-white">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="position-fixed bottom-0 end-0 p-3 toast-container">
<?php if($toast): ?>
<div id="liveToast" class="toast align-items-center text-bg-<?= $toast['type'] ?> border-0" role="alert">
    <div class="d-flex">
        <div class="toast-body"><?= htmlentities($toast['msg']) ?></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
</div>
<?php endif; ?>
</div>

<?php include('includes/footer.php'); ?>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function(){
    <?php if($toast): ?>
        var toastEl = document.getElementById('liveToast');
        var toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();

        <?php if($redirect): ?>
            // redirect after toast hides
            toastEl.addEventListener('hidden.bs.toast', function () {
                window.location.href = 'dashboard.php';
            });
        <?php endif; ?>
    <?php endif; ?>
});
</script>
</body>
</html>
