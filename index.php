<?php
session_start();
include('includes/config.php');

$debugFile = __DIR__ . '/sso_debug.log';
function log_debug($msg) {
    global $debugFile;
    file_put_contents($debugFile, "[".date('Y-m-d H:i:s')."] $msg\n", FILE_APPEND);
}

// Auto-login via RTC token
function rtcAutoLogin($token, $dbh) {
    log_debug("⚡ Starting RTC auto-login with token: $token");

    try {
        $ch = curl_init("https://api.rtc-bb.camai.kh/api/auth/get_detail_user");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        log_debug("RTC API response code: $httpCode");
        log_debug("RTC API response: $response");

        if ($httpCode !== 200) {
            throw new Exception("RTC API returned HTTP code $httpCode");
        }

        $user = json_decode($response, true);
        if (!isset($user['user']['id'])) {
            throw new Exception("Invalid token or user not found");
        }

        $userData = $user['user'];
        $email = $userData['email'];
        $studentId = $userData['user_detail']['id_card'] ?? null;
        $fullName = $userData['user_detail']['latin_name'] ?? ($userData['name'] ?? 'Unknown');
        $phoneNumber = $userData['user_detail']['phone_number'] ?? '';

        // Find or create user in local database
        $checkUser = $dbh->prepare("SELECT StudentId, EmailId FROM tblstudents WHERE EmailId = :email");
        $checkUser->bindParam(':email', $email);
        $checkUser->execute();

        if ($checkUser->rowCount() > 0) {
            // Update existing user
            $existingUser = $checkUser->fetch(PDO::FETCH_OBJ);
            $updateUser = $dbh->prepare("UPDATE tblstudents SET FullName = :name, MobileNumber = :mobile, Status = 1 WHERE EmailId = :email");
            $updateUser->bindParam(':name', $fullName);
            $updateUser->bindParam(':mobile', $phoneNumber);
            $updateUser->bindParam(':email', $email);
            $updateUser->execute();
            $studentId = $existingUser->StudentId; // Use existing StudentId
        } else {
            // Create new user with a generated password
            $insertUser = $dbh->prepare("
                INSERT INTO tblstudents (StudentId, FullName, EmailId, MobileNumber, Password, Status)
                VALUES (:id, :name, :email, :mobile, :password, 1)
            ");
            $defaultPassword = md5(uniqid()); // Random password since we're using token auth
            $insertUser->bindParam(':id', $studentId);
            $insertUser->bindParam(':name', $fullName);
            $insertUser->bindParam(':email', $email);
            $insertUser->bindParam(':mobile', $phoneNumber);
            $insertUser->bindValue(':password', $defaultPassword);
            $insertUser->execute();
        }

        // Create Library session (Auth.login equivalent)
        $_SESSION['login'] = true;
        $_SESSION['stdid'] = $studentId;
        $_SESSION['username'] = $fullName;
        $_SESSION['email'] = $email;
        $_SESSION['roles'] = $userData['roles'] ?? ['Student'];
        $_SESSION['token_external'] = $token;

        log_debug("✅ RTC auto-login successful for user: " . $_SESSION['username']);
        return true;

    } catch (Exception $e) {
        log_debug("⚠️ RTC auto-login error: " . $e->getMessage());
        $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Auto-login failed: ' . $e->getMessage()];
        return false;
    }
}

// 1️⃣ Check if user is already logged in locally
if (!empty($_SESSION['login'])) {
    log_debug("User already logged in, redirecting to dashboard.");
    header('Location: dashboard.php');
    exit;
}

// 2️⃣ Check for token from GET/POST for auto-login
$token = $_GET['token'] ?? $_POST['token'] ?? null;
if ($token) {
    log_debug("Token received for auto-login: $token");
    if (rtcAutoLogin($token, $dbh)) {
        header('Location: dashboard.php');
        exit;
    }
}

// 3️⃣ Handle manual form login
if (isset($_POST['login'])) {
    $email = trim($_POST['emailid']);
    $password = $_POST['password'];

    // 1️⃣ Local admin login
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

    // 2️⃣ External API login
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
    log_debug("Login response: " . print_r($result, true));

    $token = $result['token'] ?? $result['access_token'] ?? ($result['data']['token'] ?? null);

    if ($statusCode === 200 && $token) {
        // Use the token to get user details (same as auto-login flow)
        if (rtcAutoLogin($token, $dbh)) {
            header("Location: dashboard.php");
            exit;
        } else {
            $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Login successful but failed to create local session.'];
        }
    } else {
        $msg = $result['message'] ?? "External login failed (HTTP $statusCode)";
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
                <button type="button" class="toggle-password" id="togglePassword"><i class="bi bi-eye"></i></button>
            </div>
            <a href="user-forgot-password.php" class="small-link">Forgot password?</a>
        </div>

        <button type="submit" name="login" class="btn btn-primary mt-2">Login</button>
        <span class="small-link mt-3">Don't have an account? <a href="signup.php">Register here</a></span>
    </form>
</div>

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
<?php
if(isset($_SESSION['toast'])){
    $toast=$_SESSION['toast'];
    $bg=match($toast['type']){
        'success'=>'bg-success',
        'danger'=>'bg-danger',
        'warning'=>'bg-warning text-dark',
        'info'=>'bg-info text-dark',
        default=>'bg-secondary',
    };
    echo "document.addEventListener('DOMContentLoaded',()=>{const toastEl=document.getElementById('liveToast');const toastBody=document.getElementById('toast-message');toastEl.className='toast align-items-center border-0 $bg';toastBody.textContent='{$toast['message']}';new bootstrap.Toast(toastEl).show();});";
    unset($_SESSION['toast']);
}
?>

// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function(){
    const pw = document.getElementById('password');
    const icon = this.querySelector('i');
    if(pw.type === 'password'){
        pw.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    }else{
        pw.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
});
</script>
</body>
</html>