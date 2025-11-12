<?php
session_start();
include('includes/config.php');
error_reporting(0);

if (strlen($_SESSION['login']) == 0) {
    header('location:index.php');
    exit;
}

$msg = $error = '';

if (isset($_POST['change'])) {
    $password = md5($_POST['password']);
    $newpassword = md5($_POST['newpassword']);
    $email = $_SESSION['login'];

    $sql = "SELECT Password FROM tblstudents WHERE EmailId=:email AND Password=:password";
    $query = $dbh->prepare($sql);
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->bindParam(':password', $password, PDO::PARAM_STR);
    $query->execute();

    if ($query->rowCount() > 0) {
        $con = "UPDATE tblstudents SET Password=:newpassword WHERE EmailId=:email";
        $chngpwd1 = $dbh->prepare($con);
        $chngpwd1->bindParam(':email', $email, PDO::PARAM_STR);
        $chngpwd1->bindParam(':newpassword', $newpassword, PDO::PARAM_STR);
        $chngpwd1->execute();
        $msg = $lang['password_changed_success'];
    } else {
        $error = $lang['wrong_current_password'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $lang['student_change_password'] ?></title>

<!-- Bootstrap & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
    background-color: #f1f3f6;
}
.header-line {
    font-weight: 600;
    border-bottom: 3px solid #007bff;
    padding-bottom: 10px;
    color: #007bff;
}
.errorWrap, .succWrap {
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 8px;
    font-weight: 500;
}
.errorWrap {
    background-color: #ffe6e6;
    border-left: 5px solid #e74c3c;
    color: #b03a2e;
}
.succWrap {
    background-color: #e6ffee;
    border-left: 5px solid #28a745;
    color: #1e7e34;
}
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.card-header {
    background: linear-gradient(90deg, #007bff, #00bfff);
    color: white;
    font-weight: 600;
    border-top-left-radius: 15px;
    border-top-right-radius: 15px;
}
.btn-custom {
    background: linear-gradient(90deg, #28a745, #34ce57);
    border: none;
    font-weight: 600;
    color: white;
}
.btn-custom:hover {
    background: linear-gradient(90deg, #218838, #28a745);
}
</style>

<script>
function valid() {
    if (document.chngpwd.newpassword.value != document.chngpwd.confirmpassword.value) {
        alert("<?= $lang['password_mismatch'] ?>");
        document.chngpwd.confirmpassword.focus();
        return false;
    }
    return true;
}
</script>
</head>

<body>
<?php include('includes/header.php'); ?>

<div class="container mt-3">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <h2 class="text-center fw-bold text-primary  mb-4"><i class="bi bi-key-fill"></i> <?= $lang['change_password_title'] ?></h2>

            <?php if ($error): ?>
                <div class="errorWrap"><strong><i class="bi bi-exclamation-triangle-fill"></i></strong> <?= htmlentities($error) ?></div>
            <?php elseif ($msg): ?>
                <div class="succWrap"><strong><i class="bi bi-check-circle-fill"></i></strong> <?= htmlentities($msg) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-shield-lock-fill"></i> <?= $lang['change_password_form_title'] ?>
                </div>
                <div class="card-body">
                    <form method="post" name="chngpwd" onsubmit="return valid();">
                        <div class="mb-3">
                            <label class="form-label"><?= $lang['current_password'] ?></label>
                            <input type="password" class="form-control" name="password" required autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= $lang['new_password'] ?></label>
                            <input type="password" class="form-control" name="newpassword" required autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= $lang['confirm_new_password'] ?></label>
                            <input type="password" class="form-control" name="confirmpassword" required autocomplete="off">
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="change" class="btn btn-custom"><i class="bi bi-check-circle-fill"></i> <?= $lang['btn_change_password'] ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
