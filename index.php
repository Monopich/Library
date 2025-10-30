<?php
session_start();
include('includes/config.php');

// ✅ 1️⃣ Already logged in? Redirect immediately to dashboard
if (!empty($_SESSION['login']) || !empty($_SESSION['alogin'])) {
    header('Location: dashboard.php');
    exit;
}

// ✅ 2️⃣ RTC token exists? Try auto-login using SSO
if (!empty($_COOKIE['access_token'])) {
    $token = $_COOKIE['access_token'];

    try {
        // Fetch user details from RTC
        $ch = curl_init("https://api.rtc-bb.camai.kh/api/auth/get_detail_user");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $token"],
            CURLOPT_SSL_VERIFYPEER => false, // ⚠️ disable SSL verification (only for testing)
        ]);
        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception('cURL Error: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("RTC API returned HTTP code $httpCode");
        }

        $userData = json_decode($response, true);

        if (!isset($userData['user']['id'])) {
            throw new Exception("Invalid token or user not found");
        }

        // ✅ Valid RTC token → create Library session
        $user = $userData['user'];
        $detail = $user['user_detail'] ?? [];

        $_SESSION['login'] = $user['email'] ?? 'guest@rtc.edu.kh';
        $_SESSION['stdid'] = $detail['id_card'] ?? $user['id'];
        $_SESSION['username'] = $user['name'] ?? 'RTC User';
        $_SESSION['roles'] = $user['roles'] ?? ['Student'];
        $_SESSION['token_external'] = $token;

        header('Location: dashboard.php');
        exit;

    } catch (Exception $e) {
        error_log("RTC auto-login error: " . $e->getMessage());
        $_SESSION['toast'] = [
            'type' => 'danger',
            'message' => 'RTC auto-login failed: ' . $e->getMessage()
        ];
    }
}

// ✅ 3️⃣ Manual login (Library local + RTC API fallback)
if (isset($_POST['login'])) {
    $email = trim($_POST['emailid']);
    $password = $_POST['password'];

    // --- Local Admin Login ---
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

    // --- RTC API Login ---
    $apiLoginUrl = "https://api.rtc-bb.camai.kh/api/auth/login";
    $payload = json_encode(['email' => $email, 'password' => $password]);

    $ch = curl_init($apiLoginUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        $_SESSION['toast'] = ['type' => 'danger', 'message' => "Network Error: $curlError"];
        header("Location: index.php");
        exit;
    }

    $result = json_decode($response, true);
    @file_put_contents(__DIR__.'/api_debug.log', "Login response:\n" . print_r($result, true), FILE_APPEND);

    $token = $result['token'] ?? $result['access_token'] ?? ($result['data']['token'] ?? null);

    if ($statusCode === 200 && $token) {
        // Try fetching RTC user details
        $user = null;
        $detailEndpoints = [
            "https://api.rtc-bb.camai.kh/api/auth/get_detail_user",
            "https://api.rtc-bb.camai.kh/api/auth/me",
            "https://api.rtc-bb.camai.kh/api/user/me"
        ];

        foreach ($detailEndpoints as $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Accept: application/json'
                ],
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $userResponse = curl_exec($ch);
            $detailStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $userData = json_decode($userResponse, true);
            if ($detailStatus === 200 && !empty($userData)) {
                $user = $userData['data'] ?? $userData['user'] ?? null;
                break;
            }
        }

        if ($user) {
            $studentId = $user['user_detail']['id_card'] ?? null;
            $fullName  = $user['user_detail']['latin_name'] ?? ($user['first_name'] ?? 'Unknown');
            $phone     = $user['user_detail']['phone_number'] ?? '';
            $emailId   = $user['email'] ?? $email;

            // ✅ Upsert student in library DB
            $checkUser = $dbh->prepare("SELECT StudentId FROM tblstudents WHERE EmailId = :email");
            $checkUser->bindParam(':email', $emailId);
            $checkUser->execute();

            if ($checkUser->rowCount() > 0) {
                $updateUser = $dbh->prepare("
                    UPDATE tblstudents 
                    SET FullName = :name, MobileNumber = :mobile, Status = 1 
                    WHERE EmailId = :email
                ");
                $updateUser->bindParam(':name', $fullName);
                $updateUser->bindParam(':mobile', $phone);
                $updateUser->bindParam(':email', $emailId);
                $updateUser->execute();
            } else {
                $insertUser = $dbh->prepare("
                    INSERT INTO tblstudents (StudentId, FullName, EmailId, MobileNumber, Password, Status)
                    VALUES (:id, :name, :email, :mobile, :password, 1)
                ");
                $insertUser->bindParam(':id', $studentId);
                $insertUser->bindParam(':name', $fullName);
                $insertUser->bindParam(':email', $emailId);
                $insertUser->bindParam(':mobile', $phone);
                $insertUser->bindValue(':password', md5($password));
                $insertUser->execute();
            }

            // ✅ Set login session
            $_SESSION['stdid'] = $studentId;
            $_SESSION['login'] = $emailId;
            $_SESSION['token_external'] = $token;
            $_SESSION['username'] = $fullName;

            // ✅ Also set cookie to share session (SSO)
            setcookie('access_token', $token, time() + 3600, '/', '.camai.kh', false, true);

            header("Location: dashboard.php");
            exit;
        } else {
            $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Failed to fetch user details from RTC.'];
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
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
}
.btn-primary {
    width: 100%;
    border-radius: 8px;
    padding: 10px;
    font-weight: 500;
}
.btn-primary:hover {
    background: #1a3d7c;
}
.small-link {
    font-size: 0.875rem;
    margin-top: 10px;
    display: block;
}
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
<?php
if(isset($_SESSION['toast'])){
    $toast=$_SESSION['toast'];
    $bg = match($toast['type']){
        'success'=>'bg-success',
        'danger'=>'bg-danger',
        'warning'=>'bg-warning text-dark',
        'info'=>'bg-info text-dark',
        default=>'bg-secondary',
    };
    echo "document.addEventListener('DOMContentLoaded',()=>{const toastEl=document.getElementById('liveToast');const toastBody=document.getElementById('toast-message');toastEl.className='toast align-items-center border-0 $bg';toastBody.textContent='".addslashes($toast['message'])."';new bootstrap.Toast(toastEl).show();});";
    unset($_SESSION['toast']);
}
?>

document.getElementById('togglePassword').addEventListener('click', function(){
    const pw = document.getElementById('password');
    const icon = this.querySelector('i');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        pw.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
});
</script>
</body>
</html>
