<?php
session_start();
error_reporting(0);
include('includes/config.php');

if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit;
}

// Handle Add Category
if (isset($_POST['addCategory'])) {
    $name = $_POST['CategoryName'];
    $status = $_POST['Status'];
    $sql = "INSERT INTO tblcategory(CategoryName, Status, CreationDate) VALUES(:name, :status, NOW())";
    $query = $dbh->prepare($sql);
    $query->bindParam(':name', $name, PDO::PARAM_STR);
    $query->bindParam(':status', $status, PDO::PARAM_INT);
    if ($query->execute()) {
        $_SESSION['toast'] = ['msg' => 'Category added successfully!', 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['msg' => 'Failed to add category!', 'type' => 'danger'];
    }
    header('location:manage-categories.php');
    exit;
}

// Handle Edit Category
if (isset($_POST['editCategory'])) {
    $id = $_POST['catid'];
    $name = $_POST['CategoryName'];
    $status = $_POST['Status'];
    $sql = "UPDATE tblcategory SET CategoryName=:name, Status=:status, UpdationDate=NOW() WHERE id=:id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':name', $name, PDO::PARAM_STR);
    $query->bindParam(':status', $status, PDO::PARAM_INT);
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    if ($query->execute()) {
        $_SESSION['toast'] = ['msg' => 'Category updated successfully!', 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['msg' => 'Failed to update category!', 'type' => 'danger'];
    }
    header('location:manage-categories.php');
    exit;
}

// Handle Delete Category
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $sql = "DELETE FROM tblcategory WHERE id=:id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    if ($query->execute()) {
        $_SESSION['toast'] = ['msg' => 'Category deleted successfully!', 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['msg' => 'Failed to delete category!', 'type' => 'danger'];
    }
    header('location:manage-categories.php');
    exit;
}

$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage Categories | Online Library</title>

<!-- Bootstrap 5 -->
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

<div class="container my-3" style="padding-bottom: 50px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold text-primary">Manage Categories</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="bi bi-plus-circle"></i> Add Category
        </button>
    </div>

    <div class="table-responsive shadow-sm rounded bg-white p-3"  >
        <table class="table table-striped table-hover table-bordered align-middle" id="categoriesTable">
            <thead class="table-primary text-white">
                <tr>
                    <th>#</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Creation Date</th>
                    <th>Updation Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $sql = "SELECT * FROM tblcategory ORDER BY CreationDate DESC";
$query = $dbh->prepare($sql);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_OBJ);

            $cnt = 1;
            foreach($results as $result):
            ?>
                <tr>
                    <td><?= $cnt ?></td>
                    <td class="text-start" style="width:300px;"><?= htmlentities($result->CategoryName) ?></td>
                    <td>
                        <?php if($result->Status==1): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlentities($result->CreationDate) ?></td>
                    <td><?= htmlentities($result->UpdationDate) ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm editCatBtn" 
                                data-id="<?= $result->id ?>" 
                                data-name="<?= htmlentities($result->CategoryName) ?>" 
                                data-status="<?= $result->Status ?>"
                                data-bs-toggle="modal" data-bs-target="#editCategoryModal">
                            <i class="bi bi-pencil-square"></i> Edit
                        </button>
                        <button class="btn btn-danger btn-sm delCatBtn" 
                                data-id="<?= $result->id ?>" 
                                data-bs-toggle="modal" data-bs-target="#deleteCategoryModal">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            <?php $cnt++; endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('includes/footer.php'); ?>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Add Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="CategoryName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="Status" class="form-select" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="addCategory" class="btn btn-success">Add</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" id="editCategoryForm">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="catid" id="editCatId">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="CategoryName" id="editCatName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="Status" id="editStatus" class="form-select" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="editCategory" class="btn btn-primary">Update</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">Are you sure you want to delete this category?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" class="btn btn-danger" id="confirmDeleteCatBtn">Delete</a>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="position-fixed top-0 end-0 p-3 toast-container">
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
    $('#categoriesTable').DataTable({"columnDefs":[{"orderable":false,"targets":5}]});

    <?php if($toast): ?>
        var toastEl = document.getElementById('liveToast');
        var toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();
    <?php endif; ?>

    // Edit Category
    $(document).on('click', '.editCatBtn', function(){
        $('#editCatId').val($(this).data('id'));
        $('#editCatName').val($(this).data('name'));
        $('#editStatus').val($(this).data('status'));
    });

    // Delete Category
    $(document).on('click', '.delCatBtn', function(){
        const id = $(this).data('id');
        $('#confirmDeleteCatBtn').attr('href','manage-categories.php?del=' + id);
    });
});
</script>
</body>
</html>
