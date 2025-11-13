<?php
session_start();
include('includes/config.php');
error_reporting(E_ALL);

if (isset($_POST['signup'])) {
    // Generate Student ID using a text file (or ideally database auto-increment)
    $count_my_page = "studentid.txt";
    if (!file_exists($count_my_page)) file_put_contents($count_my_page, "1000"); // initial ID
    $hits = file($count_my_page);
    $hits[0]++;
    file_put_contents($count_my_page, $hits[0]);
    $StudentId = $hits[0];

    $fname = trim($_POST['fullname']);
    $mobileno = trim($_POST['mobileno']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmpassword = $_POST['confirmpassword'];
    $status = 1;

    if ($password !== $confirmpassword) {
        $_SESSION['toast'] = ['type' => 'warning', 'message' => $lang['password_mismatch']];
    } else {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO tblstudents(StudentId, FullName, MobileNumber, EmailId, Password, Status) 
                VALUES(:StudentId, :fname, :mobileno, :email, :password, :status)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':StudentId', $StudentId, PDO::PARAM_STR);
        $query->bindParam(':fname', $fname, PDO::PARAM_STR);
        $query->bindParam(':mobileno', $mobileno, PDO::PARAM_STR);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        $query->bindParam(':status', $status, PDO::PARAM_INT);
        $query->execute();

        if ($dbh->lastInsertId()) {
            $_SESSION['toast'] = ['type' => 'success', 'message' => $lang['registration_success'] . " $StudentId"];
            $_SESSION['redirect'] = 'index.php';
        } else {
            $_SESSION['toast'] = ['type' => 'danger', 'message' => $lang['registration_failed']];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'en' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $lang['signup_title'] ?> | Library</title>
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
.card-signup {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    padding: 40px 30px;
    width: 100%;
    max-width: 420px;
    text-align: center;
}
.card-title {
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
.login-logo { width: 250px; margin-bottom: 20px; }
.language-switch select {
            font-weight: 500;
            color: #1a3d7c;
        }

        .language-switch select:focus {
            border-color: #1a3d7c;
            box-shadow: 0 0 0 0.2rem rgba(26, 61, 124, 0.25);
        }
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function checkAvailability() {
    $("#loaderIcon").show();
    $.ajax({
        url: "check_availability.php",
        type: "POST",
        data: { emailid: $("#emailid").val() },
        success: function(data) {
            $("#user-availability-status").html(data);
            $("#loaderIcon").hide();
        }
    });
}
</script>
</head>
<body>

<div class="card-signup">
    <img src="assets/img/login-logo.png" alt="Library Logo" class="login-logo">
    <h3 class="card-title"><?= $lang['signup_title'] ?></h3>
    <form name="signup" method="post">
        <div class="mb-1 text-start">
            <label for="fullname" class="form-label"><?= $lang['fullname'] ?></label>
            <input class="form-control" type="text" name="fullname" id="fullname" autocomplete="off" required>
        </div>
        <div class="mb-1 text-start">
            <label for="mobileno" class="form-label"><?= $lang['mobile_number'] ?></label>
            <input class="form-control" type="text" name="mobileno" id="mobileno" maxlength="10" autocomplete="off" required>
        </div>
        <div class="mb-1 text-start">
            <label for="emailid" class="form-label"><?= $lang['email'] ?></label>
            <input class="form-control" type="email" name="email" id="emailid" onBlur="checkAvailability()" autocomplete="off" required>
            <span id="user-availability-status" style="font-size:12px;"></span>
        </div>
        <div class="mb-1 text-start">
            <label class="form-label"><?= $lang['password'] ?></label>
            <div class="input-group">
                <input class="form-control" type="password" name="password" id="password" autocomplete="off" required>
                <button type="button" class="toggle-password" data-target="password"><i class="bi bi-eye"></i></button>
            </div>
        </div>
        <div class="mb-1 text-start">
            <label class="form-label"><?= $lang['confirm_password'] ?></label>
            <div class="input-group">
                <input class="form-control" type="password" name="confirmpassword" id="confirmpassword" autocomplete="off" required>
                <button type="button" class="toggle-password" data-target="confirmpassword"><i class="bi bi-eye"></i></button>
            </div>
        </div>
        <button type="submit" name="signup" class="btn btn-primary mt-3"><?= $lang['register_now'] ?></button>
        <div class="small-link mt-3">
            <a href="index.php"><?= $lang['back_to_login'] ?></a>
        </div>
    </form>

    <div class="language-switch d-flex justify-content-center mt-3">
            <form method="get" action="">
                <select name="lang" onchange="this.form.submit()" class="form-select form-select-sm fw-bold text-primary" style="width: 160px; cursor: pointer;">
                    <option value="en" <?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'selected' : '' ?>>🇬🇧 English</option>
                    <option value="kh" <?= ($_SESSION['lang'] ?? 'en') === 'kh' ? 'selected' : '' ?>>🇰🇭 ភាសាខ្មែរ</option>
                </select>
            </form>
        </div>
</div>

<!-- Toast -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1055">
  <div id="liveToast" class="toast align-items-center border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="toast-message"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
<?php
if (isset($_SESSION['toast'])) {
    $toast = $_SESSION['toast'];
    $bg = match($toast['type']) {
        'success'=>'bg-success',
        'danger'=>'bg-danger',
        'warning'=>'bg-warning text-dark',
        'info'=>'bg-info text-dark',
        default=>'bg-secondary',
    };
    $redirect = $_SESSION['redirect'] ?? '';
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
