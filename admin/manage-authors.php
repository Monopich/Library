<?php
session_start();
error_reporting(0);
include('includes/config.php');

if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit;
}

// Handle Add Author
if (isset($_POST['addAuthor'])) {
    $author = $_POST['author'];
    $sql = "INSERT INTO tblauthors(AuthorName) VALUES(:author)";
    $query = $dbh->prepare($sql);
    $query->bindParam(':author', $author, PDO::PARAM_STR);
    if ($query->execute()) {
        $_SESSION['toast'] = ['msg' => 'Author added successfully!', 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['msg' => 'Something went wrong. Please try again', 'type' => 'danger'];
    }
    header('location:manage-authors.php');
    exit;
}

// Handle Update Author
if (isset($_POST['updateAuthor'])) {
    $id = $_POST['athrid'];
    $author = $_POST['author'];
    $sql = "UPDATE tblauthors SET AuthorName=:author, UpdationDate=NOW() WHERE id=:id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':author', $author, PDO::PARAM_STR);
    $query->bindParam(':id', $id, PDO::PARAM_INT);

    if ($query->execute()) {
        $_SESSION['toast'] = ['msg' => 'Author updated successfully!', 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['msg' => 'Failed to update author!', 'type' => 'danger'];
    }
    header('location:manage-authors.php');
    exit;
}

// Handle Delete
if (isset($_GET['del'])) {
    $id = $_GET['del'];
    $sql = "DELETE FROM tblauthors WHERE id=:id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    if ($query->execute()) {
        $_SESSION['toast'] = ['msg' => 'Author deleted successfully!', 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['msg' => 'Failed to delete author!', 'type' => 'danger'];
    }
    header('location:manage-authors.php');
    exit;
}

$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Library Management System | Manage Authors</title>

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<!-- DataTables Bootstrap 5 CSS -->
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<style>
body { background-color: #f8f9fa; }
.table thead th { text-align: center; }
.table tbody td { text-align: center; }
.btn-sm { min-width: 70px; }
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

<div class="container my-3" style="padding-bottom: 50px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold text-primary">Manage Authors</h2>
        <!-- Button trigger Add Author modal -->
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addAuthorModal">
            <i class="bi bi-plus-circle"></i> Add Author
        </button>
    </div>

    <div class="table-responsive shadow-sm rounded bg-white p-3">
        <table class="table table-striped table-hover table-bordered align-middle" id="authorsTable">
            <thead class="table-primary text-white">
                <tr>
                    <th>#</th>
                    <th>Author Name</th>
                    <th>Created On</th>
                    <th>Updated On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT * FROM tblauthors ORDER BY creationDate DESC";
                $query = $dbh->prepare($sql);
                $query->execute();
                $results = $query->fetchAll(PDO::FETCH_OBJ);
                $cnt = 1;
                foreach ($results as $result):
                ?>
                <tr>
                    <td><?php echo htmlentities($cnt); ?></td>
                    <td><?php echo htmlentities($result->AuthorName); ?></td>
                    <td><?php echo htmlentities($result->creationDate); ?></td>
                    <td><?php echo htmlentities($result->UpdationDate); ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm me-1 mb-1" 
                            data-bs-toggle="modal" 
                            data-bs-target="#editAuthorModal" 
                            data-id="<?php echo $result->id; ?>" 
                            data-name="<?php echo htmlentities($result->AuthorName); ?>"> Edit
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button class="btn btn-danger btn-sm mb-1" onclick="confirmDelete(<?php echo $result->id; ?>)"> Delete
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php $cnt++; endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('includes/footer.php'); ?>

<!-- Add Author Modal -->
<div class="modal fade" id="addAuthorModal" tabindex="-1" aria-labelledby="addAuthorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addAuthorModalLabel">Add Author</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Author Name</label>
                        <input type="text" name="author" class="form-control" autocomplete="off" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="addAuthor" class="btn btn-success">Add</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Author Modal (from previous example) -->
<div class="modal fade" id="editAuthorModal" tabindex="-1" aria-labelledby="editAuthorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit Author</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="athrid" id="modalAthrid">
                    <div class="mb-3">
                        <label class="form-label">Author Name</label>
                        <input type="text" name="author" id="modalAuthorName" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="updateAuthor" class="btn btn-primary">Update</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteAuthorModal" tabindex="-1" aria-labelledby="deleteAuthorModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this author?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="#" class="btn btn-danger" id="deleteConfirmBtn">Delete</a>
      </div>
    </div>
  </div>
</div>

<!-- Toast -->
<?php if($toast): ?>
<div class="position-fixed top-0 end-0 p-3 toast-container">
    <div id="liveToast" class="toast align-items-center text-bg-<?php echo $toast['type']; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body"><?php echo htmlentities($toast['msg']); ?></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#authorsTable').DataTable({ "columnDefs": [{ "orderable": false, "targets": 4 }] });

    // Show toast
    <?php if($toast): ?>
    new bootstrap.Toast(document.getElementById('liveToast'), { delay: 4000 }).show();
    <?php endif; ?>

    // Edit modal
    var editModal = document.getElementById('editAuthorModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('modalAthrid').value = button.getAttribute('data-id');
        document.getElementById('modalAuthorName').value = button.getAttribute('data-name');
    });
});

// Delete modal
function confirmDelete(id) {
    var deleteBtn = document.getElementById('deleteConfirmBtn');
    deleteBtn.href = `manage-authors.php?del=${id}`;
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteAuthorModal'));
    deleteModal.show();
}
</script>
</body>
</html>
