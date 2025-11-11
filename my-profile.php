<?php
session_start();
error_reporting(0);
include('includes/config.php');

if (strlen($_SESSION['login']) == 0) {
    header('location:index.php');
    exit;
}

// Initialize messages
$success = '';
$error = '';

// Handle profile update
if (isset($_POST['update'])) {
    $sid = $_SESSION['stdid'];
    $fullname = $_POST['FullName'];
    $email = $_POST['EmailId'];
    $mobile = $_POST['MobileNumber'];

    $sql = "UPDATE tblstudents SET FullName=:fullname, EmailId=:email, MobileNumber=:mobile WHERE StudentId=:sid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':fullname', $fullname, PDO::PARAM_STR);
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->bindParam(':mobile', $mobile, PDO::PARAM_STR);
    $query->bindParam(':sid', $sid, PDO::PARAM_STR);

    if ($query->execute()) {
        $success = "Profile updated successfully!";
    } else {
        $error = "Something went wrong. Please try again.";
    }
}

// Fetch student profile
$sid = $_SESSION['stdid'];
$sql = "SELECT * FROM tblstudents WHERE StudentId=:sid";
$query = $dbh->prepare($sql);
$query->bindParam(':sid', $sid, PDO::PARAM_STR);
$query->execute();
$student = $query->fetch(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Student | My Profile</title>

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
body { background-color: #f8f9fa; }
.profile-card { max-width: 600px; margin: auto; margin-top: 50px; }
.profile-card img { max-width: 150px; border-radius: 50%; }
</style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="container">
    <div class="card shadow profile-card">
        <div class="card-body text-center">
            <img src="assets/img/user-avatar.png" alt="Profile Picture" class="mb-3">
            <h4 class="card-title"><?= htmlentities($student->FullName) ?></h4>
            <p class="card-text"><strong>Student ID:</strong> <?= htmlentities($student->StudentId) ?></p>
            <p class="card-text"><strong>Email:</strong> <?= htmlentities($student->EmailId) ?></p>
            <p class="card-text"><strong>Mobile:</strong> <?= htmlentities($student->MobileNumber) ?></p>
            <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                <i class="bi bi-pencil-square"></i> Edit Profile
            </button>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" id="editProfileForm">
            <div class="modal-body">
                <div class="mb-3">
                    <label>Full Name</label>
                    <input type="text" class="form-control" name="FullName" value="<?= htmlentities($student->FullName) ?>" required>
                </div>
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" class="form-control" name="EmailId" value="<?= htmlentities($student->EmailId) ?>" required>
                </div>
                <div class="mb-3">
                    <label>Mobile</label>
                    <input type="text" class="form-control" name="MobileNumber" value="<?= htmlentities($student->MobileNumber) ?>" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="update" class="btn btn-success">Save Changes</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
  </div>
</div>

<!-- Toast -->
<div class=" position-fixed top-0 end-0 p-3" style="z-index: 11">
  <div id="profileToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toastMessage"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<?php include('includes/footer.php'); ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    <?php if($success) { ?>
        $('#toastMessage').text('<?= $success ?>');
        var toastEl = document.getElementById('profileToast');
        var toast = new bootstrap.Toast(toastEl);
        toast.show();
        var modalEl = document.getElementById('editProfileModal');
        var modal = bootstrap.Modal.getInstance(modalEl);
        if(modal) modal.hide();
    <?php } ?>

    <?php if($error) { ?>
        $('#toastMessage').text('<?= $error ?>');
        $('#profileToast').removeClass('bg-success').addClass('bg-danger');
        var toastEl = document.getElementById('profileToast');
        var toast = new bootstrap.Toast(toastEl);
        toast.show();
    <?php } ?>
});
</script>

</body>
</html>
