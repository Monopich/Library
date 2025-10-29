<?php
session_name('rtc_session');
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.rtc-bb.camai.kh'); // dot for all subdomains
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');

// 1️⃣ If already logged in, redirect to dashboard
if (!empty($_SESSION['login'])) {
    header('Location: dashboard.php');
    exit;
}

// 2️⃣ Check SSO cookie from RTC
if (!isset($_SESSION['login']) && !empty($_COOKIE['access_token'])) {
    $token = $_COOKIE['access_token'];

    $checkUserUrl = "https://api.rtc-bb.camai.kh/api/auth/me";
    $ch = curl_init($checkUserUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            "Accept: application/json"
        ],
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($statusCode === 200) {
        $result = json_decode($response, true);
        $user = $result['data'] ?? $result['user'] ?? null;

        if ($user) {
            $studentId = $user['user_detail']['id_card'] ?? null;
            $fullName  = $user['user_detail']['latin_name'] ?? ($user['first_name'] ?? 'Unknown');
            $emailId   = $user['email'] ?? '';
            $phone     = $user['user_detail']['phone_number'] ?? '';

            // Sync user to local DB
            $stmt = $dbh->prepare("SELECT StudentId FROM tblstudents WHERE EmailId = :email");
            $stmt->bindParam(':email', $emailId);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $update = $dbh->prepare("UPDATE tblstudents SET FullName=:name, MobileNumber=:phone WHERE EmailId=:email");
                $update->execute([':name' => $fullName, ':phone' => $phone, ':email' => $emailId]);
            } else {
                $insert = $dbh->prepare("INSERT INTO tblstudents (StudentId, FullName, EmailId, MobileNumber, Password, Status) VALUES (:id, :name, :email, :phone, '', 1)");
                $insert->execute([':id' => $studentId, ':name' => $fullName, ':email' => $emailId, ':phone' => $phone]);
            }

            // Set session
            $_SESSION['stdid'] = $studentId;
            $_SESSION['login'] = $emailId;
            $_SESSION['username'] = $fullName;
            $_SESSION['token_external'] = $token;

            header("Location: dashboard.php");
            exit;
        }
    }
}

// 3️⃣ Handle login form submission
if (isset($_POST['login'])) {
    $email = trim($_POST['emailid']);
    $password = $_POST['password'];

    // Local admin login
    $sqlAdmin = "SELECT UserName, Password FROM admin WHERE UserName = :email";
    $queryAdmin = $dbh->prepare($sqlAdmin);
    $queryAdmin->bindParam(':email', $email, PDO::PARAM_STR);
    $queryAdmin->execute();
    $adminResult = $queryAdmin->fetch(PDO::FETCH_OBJ);

    if ($adminResult && $adminResult->Password === md5($password)) {
        $_SESSION['alogin'] = $email;
        header("Location: admin/dashboard.php");
        exit;
    }

    // External RTC API login
    $apiLoginUrl = "https://api.rtc-bb.camai.kh/api/auth/login";
    $payload = json_encode(['email' => $email, 'password' => $password]);

    $ch = curl_init($apiLoginUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        $_SESSION['toast'] = ['type' => 'danger', 'message' => "cURL Error: $curlError"];
        header("Location: index.php");
        exit;
    }

    $result = json_decode($response, true);
    $token = $result['token'] ?? $result['access_token'] ?? ($result['data']['token'] ?? null);

    if ($statusCode === 200 && $token) {
        // Fetch user details
        $userDetailUrl = "https://api.rtc-bb.camai.kh/api/auth/me";
        $ch = curl_init($userDetailUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $token", "Accept: application/json"],
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $userResponse = curl_exec($ch);
        curl_close($ch);

        $userData = json_decode($userResponse, true);
        $user = $userData['data'] ?? $userData['user'] ?? null;

        if ($user) {
            $studentId = $user['user_detail']['id_card'] ?? null;
            $fullName  = $user['user_detail']['latin_name'] ?? ($user['first_name'] ?? 'Unknown');
            $phone     = $user['user_detail']['phone_number'] ?? '';
            $emailId   = $user['email'] ?? $email;

            // Sync to local DB
            $checkUser = $dbh->prepare("SELECT StudentId FROM tblstudents WHERE EmailId = :email");
            $checkUser->bindParam(':email', $emailId);
            $checkUser->execute();

            if ($checkUser->rowCount() > 0) {
                $updateUser = $dbh->prepare("UPDATE tblstudents SET FullName=:name, MobileNumber=:mobile, Status=1 WHERE EmailId=:email");
                $updateUser->execute([':name'=>$fullName, ':mobile'=>$phone, ':email'=>$emailId]);
            } else {
                $insertUser = $dbh->prepare("INSERT INTO tblstudents (StudentId, FullName, EmailId, MobileNumber, Password, Status) VALUES (:id,:name,:email,:mobile,:password,1)");
                $insertUser->execute([':id'=>$studentId, ':name'=>$fullName, ':email'=>$emailId, ':mobile'=>$phone, ':password'=>md5($password)]);
            }

            // Set session
            $_SESSION['stdid'] = $studentId;
            $_SESSION['login'] = $emailId;
            $_SESSION['username'] = $fullName;
            $_SESSION['token_external'] = $token;

            header("Location: dashboard.php");
            exit;
        } else {
            $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Failed to fetch user details from RTC.'];
        }
    } else {
        $msg = $result['message'] ?? "Login failed (HTTP $statusCode)";
        $_SESSION['toast'] = ['type' => 'danger', 'message' => $msg];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Library Management | Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
body {
    background: #f0f2f5;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: 'Segoe UI', sans-serif;
}
.login-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    padding: 35px 30px;
    width: 100%;
    max-width: 400px;
    text-align: center;
}
.login-logo {
    width: 150px;
    margin-bottom: 20px;
}
.login-title {
    font-weight: 600;
    margin-bottom: 25px;
    color: #1a3d7c;
}
.input-group .form-control { border-radius: 8px !important; padding-right: 40px; }
.input-group .toggle-password { position: absolute; top:50%; right:10px; transform:translateY(-50%); border:none; background:none; cursor:pointer; font-size:1.1rem; color:#6c757d; }
.btn-primary { width: 100%; border-radius: 8px; padding: 10px; font-weight: 500; }
.btn-primary:hover { background: #1a3d7c; }
.small-link { font-size:0.875rem; margin-top:10px; display:block; }
</style>
</head>
<body>

<div class="login-card">
    <img src="assets/img/login-logo.png" alt="Library Logo" class="login-logo">
    <h3 class="login-title">Library Login</h3>

    <form method="post">
        <div class="mb-1 text-start">
            <label for="emailid" class="form-label">Email or Admin Username</label>
            <input type="text" class="form-control" name="emailid" id="emailid" required autocomplete="off">
        </div>

        <div class="mb-1 text-start">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <input type="password" class="form-control" name="password" id="password" required autocomplete="off">
                <button type="button" class="toggle-password"><i class="bi bi-eye"></i></button>
            </div>
            <a href="user-forgot-password.php" class="small-link">Forgot password?</a>
        </div>

        <button type="submit" name="login" class="btn btn-primary mt-2">Login</button>
        <span class="small-link mt-3">Don't have an account? <a href="signup.php">Register here</a></span>
    </form>
</div>

<!-- Toast -->
<div class="position-fixed top-0 end-0 p-3" style="z-index:1055">
  <div id="liveToast" class="toast align-items-center text-white border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="toast-message"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toast display
<?php if(isset($_SESSION['toast'])): 
    $toast = $_SESSION['toast'];
    $bg = match($toast['type']){
        'success'=>'bg-success',
        'danger'=>'bg-danger',
        'warning'=>'bg-warning text-dark',
        'info'=>'bg-info text-dark',
        default=>'bg-secondary',
    };
?>
document.addEventListener('DOMContentLoaded', ()=>{
    const toastEl = document.getElementById('liveToast');
    const toastBody = document.getElementById('toast-message');
    toastEl.className = 'toast align-items-center border-0 <?php echo $bg; ?>';
    toastBody.textContent = '<?php echo addslashes($toast['message']); ?>';
    new bootstrap.Toast(toastEl).show();
});
<?php unset($_SESSION['toast']); endif; ?>

// Toggle password visibility
document.querySelector('.toggle-password').addEventListener('click', function(){
    const pw = document.getElementById('password');
    const icon = this.querySelector('i');
    if(pw.type === 'password'){
        pw.type = 'text';
        icon.classList.remove('bi-eye'); icon.classList.add('bi-eye-slash');
    } else {
        pw.type = 'password';
        icon.classList.remove('bi-eye-slash'); icon.classList.add('bi-eye');
    }
});
</script>
</body>
</html>
