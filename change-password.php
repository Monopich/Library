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
        $msg = "Your password was successfully changed";
    } else {
        $error = "Your current password is wrong";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Student | Change Password</title>

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
    background-color: #f8f9fa;
}
.header-line {
    margin-bottom: 20px;
    font-weight: 600;
}
.errorWrap {
    padding: 10px;
    margin-bottom: 20px;
    background: #fff;
    border-left: 4px solid #dd3d36;
    box-shadow: 0 1px 1px rgba(0,0,0,.1);
}
.succWrap {
    padding: 10px;
    margin-bottom: 20px;
    background: #fff;
    border-left: 4px solid #5cb85c;
    box-shadow: 0 1px 1px rgba(0,0,0,.1);
}
</style>

<script>
function valid() {
    if (document.chngpwd.newpassword.value != document.chngpwd.confirmpassword.value) {
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

<div class="container content-wrapper mt-4">
    <div class="row">
        <div class="col-md-12">
            <h4 class="header-line text-center">Change Password</h4>
        </div>
    </div>

    <?php if ($error) { ?>
        <div class="errorWrap"><strong>ERROR:</strong> <?php echo htmlentities($error); ?></div>
    <?php } elseif ($msg) { ?>
        <div class="succWrap"><strong>SUCCESS:</strong> <?php echo htmlentities($msg); ?></div>
    <?php } ?>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-key-fill"></i> Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="post" name="chngpwd" onsubmit="return valid();">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="password" required autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="newpassword" required autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirmpassword" required autocomplete="off">
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="change" class="btn btn-success"><i class="bi bi-check-circle-fill"></i> Change Password</button>
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
