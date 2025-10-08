<?php
session_start();
error_reporting(0);
include('includes/config.php');
if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit;
}

if (isset($_POST['update_status'])) {
    $id = $_POST['student_id'];
    $status = $_POST['status'];
    $sql = "UPDATE tblstudents SET Status=:status WHERE id=:id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_STR);
    $query->bindParam(':status', $status, PDO::PARAM_STR);
    $query->execute();

    // Set toast
    $_SESSION['toast'] = [
        'msg' => $status == 1 ? 'Student activated successfully!' : 'Student blocked successfully!',
        'type' => $status == 1 ? 'success' : 'danger'
    ];

    header('location:reg-students.php');
    exit;
}

// Toast from session
$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Online Library Management System | Manage Registered Students</title>

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

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
    <h4 class="mb-4 fw-bold text-primary">Manage Registered Students</h4>

    <div class="table-responsive shadow-sm rounded bg-white p-3">
        <table class="table table-striped table-bordered table-hover align-middle" id="studentsTable">
            <thead class="table-primary text-white">
                <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Email ID</th>
                    <th>Mobile Number</th>
                    <th>Reg Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
<?php
$sql = "SELECT * FROM tblstudents";
$query = $dbh->prepare($sql);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_OBJ);
$cnt = 1;
if ($query->rowCount() > 0) {
    foreach ($results as $result) { ?>
<tr>
    <td><?= htmlentities($cnt) ?></td>
    <td><?= htmlentities($result->StudentId) ?></td>
    <td class="text-start"><?= htmlentities($result->FullName) ?></td>
    <td class="text-start"><?= htmlentities($result->EmailId) ?></td>
    <td><?= htmlentities($result->MobileNumber) ?></td>
    <td><?= htmlentities($result->RegDate) ?></td>
    <td>
        <?php if ($result->Status == 1) { echo '<span class="badge bg-success">Active</span>'; } 
              else { echo '<span class="badge bg-danger">Blocked</span>'; } ?>
    </td>
    <td>
        <button class="btn btn-sm <?= $result->Status == 1 ? 'btn-danger' : 'btn-primary' ?>" 
                data-bs-toggle="modal" 
                data-bs-target="#statusModal" 
                data-student="<?= htmlentities($result->FullName) ?>" 
                data-id="<?= htmlentities($result->id) ?>" 
                data-status="<?= $result->Status == 1 ? 0 : 1 ?>">
            <?= $result->Status == 1 ? 'Inactive' : 'Active' ?>
        </button>
        <a href="student-history.php?stdid=<?= htmlentities($result->StudentId) ?>" 
           class="btn btn-success btn-sm">Details</a>
    </td>
</tr>
<?php $cnt++; } } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="statusModalLabel">Update Student Status</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p id="modalBodyText"></p>
          <input type="hidden" name="student_id" id="modalStudentId" value="">
          <input type="hidden" name="status" id="modalStatus" value="">
        </div>
        <div class="modal-footer">
          <button type="submit" name="update_status" class="btn btn-success">Confirm</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

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

<?php include('includes/footer.php'); ?>

<!-- JS Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#studentsTable').DataTable({
        "columnDefs": [{ "orderable": false, "targets": 7 }]
    });

    // Modal
    var statusModal = document.getElementById('statusModal');
    statusModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var studentName = button.getAttribute('data-student');
        var studentId = button.getAttribute('data-id');
        var status = button.getAttribute('data-status');

        document.getElementById('modalBodyText').textContent = 
            "Are you sure you want to " + (status == 1 ? "activate" : "block") + " student: " + studentName + "?";
        document.getElementById('modalStudentId').value = studentId;
        document.getElementById('modalStatus').value = status;
    });

    // Toast
    <?php if($toast): ?>
        var toastEl = document.getElementById('liveToast');
        var toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();
    <?php endif; ?>
});
</script>
</body>
</html>
