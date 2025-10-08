<?php
session_start();
error_reporting(0);
include('includes/config.php');

if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit;
}

// Handle update
if (isset($_POST['update'])) {
    $athrid = intval($_GET['athrid']);
    $author = $_POST['author'];
    $sql = "UPDATE tblauthors SET AuthorName=:author WHERE id=:athrid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':author', $author, PDO::PARAM_STR);
    $query->bindParam(':athrid', $athrid, PDO::PARAM_INT);
    if ($query->execute()) {
        $_SESSION['toast'] = ['msg' => 'Author info updated successfully!', 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['msg' => 'Failed to update author!', 'type' => 'danger'];
    }
    header('location:manage-authors.php');
    exit;
}

// Fetch author info
$athrid = intval($_GET['athrid']);
$sql = "SELECT * FROM tblauthors WHERE id=:athrid";
$query = $dbh->prepare($sql);
$query->bindParam(':athrid', $athrid, PDO::PARAM_INT);
$query->execute();
$result = $query->fetch(PDO::FETCH_OBJ);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Library Management System | Update Author</title>

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
    body {
        background-color: #f8f9fa;
    }
    .toast-container {
        z-index: 1100;
    }
</style>
</head>
<body>

<!-- HEADER -->
<?php include('includes/header.php'); ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Update Author</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Author Name</label>
                            <input type="text" name="author" class="form-control" value="<?php echo htmlentities($result->AuthorName); ?>" required>
                        </div>
                        <button type="submit" name="update" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update
                        </button>
                        <a href="manage-authors.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<?php include('includes/footer.php'); ?>

<!-- Toast -->
<?php if(isset($_SESSION['toast'])): ?>
<div class="position-fixed bottom-0 end-0 p-3 toast-container">
    <div id="liveToast" class="toast align-items-center text-bg-<?php echo $_SESSION['toast']['type']; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <?php echo htmlentities($_SESSION['toast']['msg']); ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<?php unset($_SESSION['toast']); endif; ?>

<!-- JS Files -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toastEl = document.getElementById('liveToast');
    if(toastEl){
        const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();
    }
});
</script>
</body>
</html>
