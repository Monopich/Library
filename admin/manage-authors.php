<?php
session_start();
error_reporting(0);
include('includes/config.php');

// Check if admin logged in
if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit;
}

// Handle Add Author
if (isset($_POST['addAuthor'])) {
    $author = $_POST['author'];
    $sql = "INSERT INTO tblauthors(AuthorName, creationDate) VALUES(:author, NOW())";
    $query = $dbh->prepare($sql);
    $query->bindParam(':author', $author, PDO::PARAM_STR);

    $_SESSION['toast'] = $query->execute()
        ? ['msg' => $lang['add_success'], 'type' => 'success']
        : ['msg' => $lang['add_fail'], 'type' => 'danger'];

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

    $_SESSION['toast'] = $query->execute()
        ? ['msg' => $lang['update_success'], 'type' => 'success']
        : ['msg' => $lang['update_fail'], 'type' => 'danger'];

    header('location:manage-authors.php');
    exit;
}

// Handle Delete Author
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $sql = "DELETE FROM tblauthors WHERE id=:id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_INT);

    $_SESSION['toast'] = $query->execute()
        ? ['msg' => $lang['delete_success'], 'type' => 'success']
        : ['msg' => $lang['delete_fail'], 'type' => 'danger'];

    header('location:manage-authors.php');
    exit;
}

$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="<?= $lang['lang_code'] ?? 'en' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Library Management | <?= $lang['manage_authors'] ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<style>
body { background-color: #f8f9fa; }
.table thead th, .table tbody td { text-align: center; vertical-align: middle; }
.toast-container { z-index: 1100; }
.table thead th { background-color: #007bff; color: #fff; }
.btn-sm { min-width: 70px; }
</style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="container my-3" style="padding-bottom: 50px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold text-primary"><?= $lang['manage_authors'] ?></h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addAuthorModal">
            <i class="bi bi-plus-circle"></i> <?= $lang['add_author'] ?>
        </button>
    </div>

    <div class="table-responsive shadow-sm rounded bg-white p-3">
        <table class="table table-striped table-hover table-bordered align-middle" id="authorsTable">
            <thead class="table-primary text-white">
                <tr>
                    <th>#</th>
                    <th><?= $lang['author_name'] ?></th>
                    <th><?= $lang['created_on'] ?></th>
                    <th><?= $lang['updated_on'] ?></th>
                    <th><?= $lang['action'] ?></th>
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
                    <td><?= $cnt ?></td>
                    <td class="text-start"><?= htmlentities($result->AuthorName) ?></td>
                    <td><?= htmlentities($result->creationDate) ?></td>
                    <td><?= htmlentities($result->UpdationDate) ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm me-1 mb-1 editBtn"
                                data-id="<?= $result->id ?>"
                                data-name="<?= htmlentities($result->AuthorName) ?>"
                                data-bs-toggle="modal" data-bs-target="#editAuthorModal">
                            <i class="bi bi-pencil-square"></i> <?= $lang['edit_author'] ?>
                        </button>
                        <button class="btn btn-danger btn-sm mb-1" onclick="confirmDelete(<?= $result->id ?>)">
                            <i class="bi bi-trash"></i> <?= $lang['delete'] ?>
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
<div class="modal fade" id="addAuthorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><?= $lang['add_author'] ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= $lang['author_name'] ?></label>
                        <input type="text" name="author" class="form-control" required autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="addAuthor" class="btn btn-success"><?= $lang['add_author'] ?></button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $lang['cancel'] ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Author Modal -->
<div class="modal fade" id="editAuthorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><?= $lang['edit_author'] ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="athrid" id="modalAthrid">
                    <div class="mb-3">
                        <label class="form-label"><?= $lang['author_name'] ?></label>
                        <input type="text" name="author" id="modalAuthorName" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="updateAuthor" class="btn btn-primary"><?= $lang['update'] ?></button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $lang['cancel'] ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteAuthorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><?= $lang['delete_confirm'] ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $lang['cancel'] ?></button>
        <a href="#" class="btn btn-danger" id="deleteConfirmBtn"><?= $lang['delete'] ?></a>
      </div>
    </div>
  </div>
</div>

<!-- Toast -->
<?php if($toast): ?>
<div class="position-fixed top-0 end-0 p-3 toast-container">
    <div id="liveToast" class="toast align-items-center text-bg-<?= $toast['type'] ?> border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body"><?= htmlentities($toast['msg']) ?></div>
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

    <?php if($toast): ?>
    new bootstrap.Toast(document.getElementById('liveToast'), { delay: 4000 }).show();
    <?php endif; ?>

    var editModal = document.getElementById('editAuthorModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('modalAthrid').value = button.getAttribute('data-id');
        document.getElementById('modalAuthorName').value = button.getAttribute('data-name');
    });
});

function confirmDelete(id) {
    var deleteBtn = document.getElementById('deleteConfirmBtn');
    deleteBtn.href = `manage-authors.php?del=${id}`;
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteAuthorModal'));
    deleteModal.show();
}
</script>
</body>
</html>
