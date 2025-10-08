<?php
session_start();
error_reporting(0);
include('includes/config.php');

if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit;
}

$sid = $_GET['stdid'] ?? null;
if (!$sid) {
    header('location:reg-students.php');
    exit;
}

// Toast notifications
$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Student History | Online Library</title>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
body { background-color: #f8f9fa; }
.table thead th, .table tbody td { text-align: center; vertical-align: middle; }
.toast-container { z-index: 1100; }
.table thead th {
    background-color: #007bff;
    color: #fff;
    text-align: center;
}
.table tbody td {
    vertical-align: middle;
    text-align: center;
}
</style>
</head>
<body>
<?php include('includes/header.php'); ?>

<div class="container my-3">
    <h2 class="fw-bold mb-4 text-primary">Student #<?= htmlentities($sid) ?> - Book Issued History</h2>

    <div class="table-responsive shadow-sm rounded bg-white p-3">
        <table class="table table-striped table-hover table-bordered align-middle" id="historyTable">
            <thead class="table-primary text-white">
                <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Issued Book</th>
                    <th>Issued Date</th>
                    <th>Returned Date</th>
                    <th>Fine (if any)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
<?php
$sql = "SELECT s.StudentId, s.FullName, b.BookName, i.IssuesDate, i.ReturnDate, i.fine, i.RetrunStatus
        FROM tblissuedbookdetails i
        JOIN tblstudents s ON s.StudentId = i.StudentId
        JOIN tblbooks b ON b.id = i.BookId
        WHERE s.StudentId = :sid
        ORDER BY i.IssuesDate DESC";
$query = $dbh->prepare($sql);
$query->bindParam(':sid', $sid, PDO::PARAM_STR);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_OBJ);
$cnt = 1;
foreach ($results as $result):
?>
<tr>
    <td><?= $cnt ?></td>
    <td><?= htmlentities($result->StudentId) ?></td>
    <td class="text-start"><?= htmlentities($result->FullName) ?></td>
    <td><?= htmlentities($result->BookName) ?></td>
    <td><?= htmlentities($result->IssuesDate) ?></td>
    <td><?= $result->ReturnDate ?: 'Not returned yet' ?></td>
    <td><?= $result->ReturnDate ? htmlentities($result->fine) : 'Not returned yet' ?></td>
    <td>
        <?php if($result->RetrunStatus==1): ?>
            <span class="badge bg-success">Returned</span>
        <?php else: ?>
            <span class="badge bg-warning text-dark">Pending</span>
        <?php endif; ?>
    </td>
</tr>
<?php $cnt++; endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('includes/footer.php'); ?>

<!-- Toast -->
<div class="position-fixed bottom-0 end-0 p-3 toast-container">
<?php if($toast): ?>
<div id="liveToast" class="toast align-items-center text-bg-<?= $toast['type'] ?> border-0" role="alert">
    <div class="d-flex">
        <div class="toast-body"><?= htmlentities($toast['msg']) ?></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
</div>
<?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function(){
    $('#historyTable').DataTable({
        "columnDefs":[{"orderable":false,"targets":[6,7]}]
    });

    <?php if($toast): ?>
        var toastEl = document.getElementById('liveToast');
        var toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();
    <?php endif; ?>
});
</script>
</body>
</html>
