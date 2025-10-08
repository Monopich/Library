<?php
session_start();
include('includes/config.php');
error_reporting(0);

if (isset($_POST['signup'])) {

    // Generate student ID
    $count_my_page = "studentid.txt";
    $hits = file($count_my_page);
    $hits[0]++;
    $fp = fopen($count_my_page, "w");
    fputs($fp, "$hits[0]");
    fclose($fp);
    $StudentId = $hits[0];

    $fname = $_POST['fullname'];
    $mobileno = $_POST['mobileno'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmpassword = $_POST['confirmpassword'];
    $status = 1;

    if ($password !== $confirmpassword) {
        $_SESSION['toast'] = ['type' => 'warning', 'message' => 'Password and Confirm Password do not match!'];
    } else {
        $hashedPassword = md5($password);
        $sql = "INSERT INTO tblstudents(StudentId, FullName, MobileNumber, EmailId, Password, Status) 
                VALUES(:StudentId, :fname, :mobileno, :email, :password, :status)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':StudentId', $StudentId, PDO::PARAM_STR);
        $query->bindParam(':fname', $fname, PDO::PARAM_STR);
        $query->bindParam(':mobileno', $mobileno, PDO::PARAM_STR);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        $query->bindParam(':status', $status, PDO::PARAM_STR);
        $query->execute();
        $lastInsertId = $dbh->lastInsertId();

        if ($lastInsertId) {
            $_SESSION['toast'] = ['type' => 'success', 'message' => "Registration successful! Your Student ID is $StudentId"];
            $_SESSION['redirect'] = 'index.php'; // redirect to login page
        } else {
            $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Something went wrong. Please try again.'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Library Management | Signup</title>

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: linear-gradient(135deg, #4e73df, #224abe);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.card-signup {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    padding: 40px;
}
.card-title {
    font-weight: 600;
    text-align: center;
    margin-bottom: 25px;
    color: #224abe;
}
.form-control {
    border-radius: 10px;
}
.btn-success {
    width: 100%;
    border-radius: 10px;
    background: #28a745;
    border: none;
}
.btn-success:hover {
    background: #218838;
}
.small-link {
    text-align: center;
    margin-top: 10px;
}
.login-logo-container {
    display: flex;
    justify-content: center; /* centers horizontally */
    margin-bottom: 15px;    /* space below logo */
}

.login-logo {
    width: auto;
    height: 90px;
    object-fit: contain;
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function checkAvailability() {
    $("#loaderIcon").show();
    $.ajax({
        url: "check_availability.php",
        data: 'emailid=' + $("#emailid").val(),
        type: "POST",
        success: function(data) {
            $("#user-availability-status").html(data);
            $("#loaderIcon").hide();
        },
        error: function() {}
    });
}
</script>

</head>
<body>

<div class="card-signup">
    <div class="login-logo-container">
        <img src="assets/img/login-logo.png" alt="Logo" class="login-logo">
    </div>
    <h3 class="card-title">Student Signup</h3>
    <form name="signup" method="post">
        <div class="mb-1">
            <label for="fullname" class="form-label">Full Name</label>
            <input class="form-control" type="text" name="fullname" id="fullname" autocomplete="off" required>
        </div>
        <div class="mb-1">
            <label for="mobileno" class="form-label">Mobile Number</label>
            <input class="form-control" type="text" name="mobileno" id="mobileno" maxlength="10" autocomplete="off" required>
        </div>
        <div class="mb-1">
            <label for="emailid" class="form-label">Email</label>
            <input class="form-control" type="email" name="email" id="emailid" onBlur="checkAvailability()" autocomplete="off" required>
            <span id="user-availability-status" style="font-size:12px;"></span>
        </div>
        <div class="mb-1">
            <label for="password" class="form-label">Password</label>
            <input class="form-control" type="password" name="password" id="password" autocomplete="off" required>
        </div>
        <div class="mb-1">
            <label for="confirmpassword" class="form-label">Confirm Password</label>
            <input class="form-control" type="password" name="confirmpassword" id="confirmpassword" autocomplete="off" required>
        </div>
        <button type="submit" name="signup" class="btn btn-success mt-3">Register Now</button>
        <div class="small-link mt-3">
            <a href="index.php">Back to Login</a>
        </div>
    </form>
</div>

<!-- Toast Container -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1055">
  <div id="liveToast" class="toast align-items-center text-white border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="toast-message"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
// Show Toast
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
