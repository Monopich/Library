<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');

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
    file_put_contents('api_debug.log', "Login response:\n" . print_r($result, true));

    // Extract token
    $token = $result['token'] ?? $result['access_token'] ?? ($result['data']['token'] ?? null);

    if ($statusCode === 200 && $token) {
        // Fetch user details
        $userDetailUrl = "https://api.rtc-bb.camai.kh/api/auth/get_detail_user";

        $ch = curl_init($userDetailUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $userResponse = curl_exec($ch);
        $detailStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $userData = json_decode($userResponse, true);
        file_put_contents('api_debug.log', "User detail response:\n" . print_r($userData, true), FILE_APPEND);

        if ($detailStatus === 200 && !empty($userData['user'])) {
            $user = $userData['user'];
            $detail = $user['user_detail'] ?? [];

            // ✅ Map external data to local fields
            if ($user) {
                $studentId = $user['user_detail']['id_card'] ?? null;
                $fullName = $user['user_detail']['latin_name'] ?? ($user['name'] ?? 'Unknown');
                $mobile = $user['user_detail']['phone_number'] ?? '';
                $emailId = $user['email'] ?? $email;

                // Initialize $checkUser
                $checkUser = $dbh->prepare("SELECT StudentId FROM tblstudents WHERE EmailId = :email");
                $checkUser->bindParam(':email', $emailId);
                $checkUser->execute();

                if ($checkUser->rowCount() > 0) {
                    // Update existing user
                    $updateUser = $dbh->prepare("
                        UPDATE tblstudents 
                        SET FullName = :name, MobileNumber = :mobile, StudentId = :id, Status = 1 
                        WHERE EmailId = :email
                    ");
                    $updateUser->bindParam(':name', $fullName);
                    $updateUser->bindParam(':mobile', $mobile);
                    $updateUser->bindParam(':id', $studentId);
                    $updateUser->bindParam(':email', $emailId);
                    $updateUser->execute();
                } else {
                    // Insert new user
                    $insertUser = $dbh->prepare("
                        INSERT INTO tblstudents (StudentId, FullName, EmailId, MobileNumber, Password, Status)
                        VALUES (:id, :name, :email, :mobile, :password, 1)
                    ");
                    $insertUser->bindParam(':id', $studentId);
                    $insertUser->bindParam(':name', $fullName);
                    $insertUser->bindParam(':email', $emailId);
                    $insertUser->bindParam(':mobile', $mobile);
                    $insertUser->bindValue(':password', md5($password));
                    $insertUser->execute();
                }
            }


            $_SESSION['stdid'] = $studentId;
            $_SESSION['login'] = $emailId;
            $_SESSION['token_external'] = $token;
            $_SESSION['username'] = $fullName;

            header("Location: dashboard.php");
            exit;
        } else {
            $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Failed to fetch user details from external system.'];
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
    <base href="/library/">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #4e73df, #224abe);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            padding: 40px;
            width: 100%;
            max-width: 420px;
            text-align: center;
        }
        .login-logo {
            width: 300px;
            object-fit: contain;
            margin-bottom: 15px;
        }
        .login-title {
            font-weight: 600;
            margin-bottom: 25px;
            color: #224abe;
        }
        .form-control {
            border-radius: 10px;
        }
        .btn-primary {
            width: 100%;
            border-radius: 10px;
            background: #4e73df;
            border: none;
        }
        .btn-primary:hover {
            background: #3752c4;
        }
        .small-link {
            text-align: center;
            margin-top: 15px;
        }
        a {
            text-decoration: none;
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
            <input type="password" class="form-control" name="password" id="password" required autocomplete="off">
            <div class="small-link mt-2">
                <a href="user-forgot-password.php">Forgot password?</a>
            </div>
        </div>
        <button type="submit" name="login" class="btn btn-primary mt-3">Login</button>
        <div class="small-link mt-3">
            <span>Don't have an account? <a href="signup.php">Register here</a></span>
        </div>
    </form>
</div>

<!-- ✅ Toast Container -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1055">
  <div id="liveToast" class="toast align-items-center text-white border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="toast-message"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
// ✅ Toast Display
if (isset($_SESSION['toast'])) {
    $toast = $_SESSION['toast'];
    $bg = match ($toast['type']) {
        'success' => 'bg-success',
        'danger' => 'bg-danger',
        'warning' => 'bg-warning text-dark',
        'info' => 'bg-info text-dark',
        default => 'bg-secondary',
    };
    echo "
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const toastEl = document.getElementById('liveToast');
        const toastBody = document.getElementById('toast-message');
        toastEl.className = 'toast align-items-center border-0 {$bg}';
        toastBody.textContent = '{$toast['message']}';
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    });
    </script>";
    unset($_SESSION['toast']);
}
?>
</body>
</html>
