<?php
session_start();
include('includes/config.php'); // this already loads language file

if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit;
}

// $lang is already available from config.php (from your en.php / kh.php)
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
        $msg = $lang["password_changed_success"];
        $redirect = true;
    } else {
        $error = $lang["current_password_wrong"];
    }
}

$toast = null;
if ($msg) $toast = ['msg'=>$msg, 'type'=>'success'];
if ($error) $toast = ['msg'=>$error, 'type'=>'danger'];
?>
<!DOCTYPE html>
<html lang="<?= isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $lang["change_password"] ?> | Online Library</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
    background: #f3f6fa;
}
.container {
    max-width: 700px;
}
.card {
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.card-header {
    font-size: 1.2rem;
    font-weight: 600;
}
.btn-primary, .btn-success {
    border-radius: 8px;
}
.form-label {
    font-weight: 600;
}
.toast-container {
    z-index: 1100;
}
.lang-switch {
    position: absolute;
    right: 20px;
    top: 20px;
}
</style>

<script>
function valid() {
    if(document.chngpwd.newpassword.value !== document.chngpwd.confirmpassword.value) {
        alert("<?= addslashes($lang['password_mismatch']) ?>");
        document.chngpwd.confirmpassword.focus();
        return false;
    }
    return true;
}
</script>
</head>
<body>
<?php include('includes/header.php'); ?>

<div class="container py-3 position-relative">
    <h2 class="text-center fw-bold text-primary mb-4"><?= $lang["change_password"] ?></h2>

    <div class="card border-0">
        <div class="card-header bg-primary text-white text-center">
            <i class="bi bi-key-fill me-2"></i> <?= $lang["change_password"] ?>
        </div>
        <div class="card-body p-4">
            <form method="post" name="chngpwd" onsubmit="return valid();">
                <div class="mb-3">
                    <label class="form-label"><?= $lang["current_password"] ?></label>
                    <input type="password" name="password" class="form-control" required autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= $lang["new_password"] ?></label>
                    <input type="password" name="newpassword" class="form-control" required autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= $lang["confirm_new_password"] ?></label>
                    <input type="password" name="confirmpassword" class="form-control" required autocomplete="off">
                </div>
                <button type="submit" name="change" class="btn btn-success w-100">
                    <i class="bi bi-check-circle-fill me-2"></i><?= $lang["change"] ?>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="position-fixed top-0 end-0 p-3 toast-container">
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
            toastEl.addEventListener('hidden.bs.toast', function () {
                window.location.href = 'dashboard.php';
            });
        <?php endif; ?>
    <?php endif; ?>
});
</script>
</body>
</html>
