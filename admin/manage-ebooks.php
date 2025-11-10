<?php
session_start();
error_reporting(0);
include('includes/config.php');

if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit;
}

$pdfDir = __DIR__ . "/assets/pdf/";
$imgDir = __DIR__ . "/bookimg/";

// Ensure directories exist
if (!file_exists($pdfDir)) mkdir($pdfDir, 0775, true);
if (!file_exists($imgDir)) mkdir($imgDir, 0775, true);

// Add eBook
if (isset($_POST['addEbook'])) {
    $bookName = $_POST['bookName'];
    $catId = $_POST['catId'];
    $authorId = $_POST['authorId'];
    $isbn = $_POST['isbn'];
    $price = $_POST['price'];

    // Validate Book Image
    $bookImage = $_FILES['bookImage']['name'];
    $imageTmp = $_FILES['bookImage']['tmp_name'];
    $imageSize = $_FILES['bookImage']['size'];
    $imageExt = strtolower(pathinfo($bookImage, PATHINFO_EXTENSION));
    $allowedImageExt = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($imageExt, $allowedImageExt)) {
        $_SESSION['toast'] = ['msg' => 'Invalid image format! Only jpg, jpeg, png, gif allowed.', 'type' => 'danger'];
        header('location:manage-ebooks.php'); exit;
    }
    if ($imageSize > 5*1024*1024) { // 5 MB limit
        $_SESSION['toast'] = ['msg' => 'Image size must be less than 5 MB.', 'type' => 'danger'];
        header('location:manage-ebooks.php'); exit;
    }
    move_uploaded_file($imageTmp, $imgDir.$bookImage);

    // Validate PDF
    $bookFile = $_FILES['bookFile']['name'];
    $fileTmp = $_FILES['bookFile']['tmp_name'];
    $fileSize = $_FILES['bookFile']['size'];
    $fileExt = strtolower(pathinfo($bookFile, PATHINFO_EXTENSION));

    if ($fileExt != 'pdf') {
        $_SESSION['toast'] = ['msg' => 'Invalid file type! Only PDF allowed.', 'type' => 'danger'];
        header('location:manage-ebooks.php'); exit;
    }
    if ($fileSize > 2 * 1024 * 1024 * 1024) { // 2 GB
        $_SESSION['toast'] = ['msg' => 'PDF size must be less than 2 GB.', 'type' => 'danger'];
        header('location:manage-ebooks.php'); exit;
    }
    move_uploaded_file($fileTmp, $pdfDir.$bookFile);

    $sql = "INSERT INTO tblbooks(BookName, CatId, AuthorId, ISBNNumber, BookPrice, bookImage, BookFile) 
            VALUES(:bookName, :catId, :authorId, :isbn, :price, :bookImage, :bookFile)";
    $query = $dbh->prepare($sql);
    $query->bindParam(':bookName', $bookName, PDO::PARAM_STR);
    $query->bindParam(':catId', $catId, PDO::PARAM_INT);
    $query->bindParam(':authorId', $authorId, PDO::PARAM_INT);
    $query->bindParam(':isbn', $isbn, PDO::PARAM_STR);
    $query->bindParam(':price', $price, PDO::PARAM_STR);
    $query->bindParam(':bookImage', $bookImage, PDO::PARAM_STR);
    $query->bindParam(':bookFile', $bookFile, PDO::PARAM_STR);

    if($query->execute()){
        $_SESSION['toast'] = ['msg' => 'eBook added successfully!', 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['msg' => 'Failed to add eBook!', 'type' => 'danger'];
    }
    header('location:manage-ebooks.php'); exit;
}


// Update eBook
if (isset($_POST['updateEbook'])) {
    $bookId = $_POST['bookId'];
    $bookName = $_POST['bookName'];
    $catId = $_POST['catId'];
    $authorId = $_POST['authorId'];
    $isbn = $_POST['isbn'];
    $price = $_POST['price'];

    $sql = "UPDATE tblbooks SET BookName=:bookName, CatId=:catId, AuthorId=:authorId, 
            ISBNNumber=:isbn, BookPrice=:price";

    // Update cover if uploaded
    if (!empty($_FILES['bookImage']['name'])) {
        $bookImage = $_FILES['bookImage']['name'];
        $imageTmp = $_FILES['bookImage']['tmp_name'];
        $imageSize = $_FILES['bookImage']['size'];
        $imageExt = strtolower(pathinfo($bookImage, PATHINFO_EXTENSION));
        $allowedImages = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($imageExt, $allowedImages)) {
            $_SESSION['toast'] = ['msg' => 'Invalid image type! Allowed: jpg, jpeg, png, gif.', 'type' => 'danger'];
            header('location:manage-ebooks.php');
            exit;
        }

        if ($imageSize > 5 * 1024 * 1024) { // 5 MB
            $_SESSION['toast'] = ['msg' => 'Image size must be less than 5 MB.', 'type' => 'danger'];
            header('location:manage-ebooks.php');
            exit;
        }

        move_uploaded_file($imageTmp, $imgDir . $bookImage);
        $sql .= ", bookImage=:bookImage";
    }

    // Update PDF if uploaded
    if (!empty($_FILES['bookFile']['name'])) {
        $bookFile = $_FILES['bookFile']['name'];
        $fileTmp = $_FILES['bookFile']['tmp_name'];
        $fileSize = $_FILES['bookFile']['size'];
        $fileExt = strtolower(pathinfo($bookFile, PATHINFO_EXTENSION));

        if ($fileExt != 'pdf') {
            $_SESSION['toast'] = ['msg' => 'Invalid file type! Only PDF allowed.', 'type' => 'danger'];
            header('location:manage-ebooks.php');
            exit;
        }

        if ($fileSize > 2 * 1024 * 1024 * 1024) { // 2 GB
            $_SESSION['toast'] = ['msg' => 'PDF size must be less than 2 GB.', 'type' => 'danger'];
            header('location:manage-ebooks.php');
            exit;
        }

        move_uploaded_file($fileTmp, $pdfDir . $bookFile);
        $sql .= ", BookFile=:bookFile";
    }

    $sql .= " WHERE id=:id";
    $query = $dbh->prepare($sql);

    $query->bindParam(':bookName', $bookName, PDO::PARAM_STR);
    $query->bindParam(':catId', $catId, PDO::PARAM_INT);
    $query->bindParam(':authorId', $authorId, PDO::PARAM_INT);
    $query->bindParam(':isbn', $isbn, PDO::PARAM_STR);
    $query->bindParam(':price', $price, PDO::PARAM_STR);

    if (!empty($_FILES['bookImage']['name'])) {
        $query->bindParam(':bookImage', $bookImage, PDO::PARAM_STR);
    }

    if (!empty($_FILES['bookFile']['name'])) {
        $query->bindParam(':bookFile', $bookFile, PDO::PARAM_STR);
    }

    $query->bindParam(':id', $bookId, PDO::PARAM_INT);

    if ($query->execute()) {
        $_SESSION['toast'] = ['msg' => 'eBook updated successfully!', 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['msg' => 'Failed to update eBook!', 'type' => 'danger'];
    }

    header('location:manage-ebooks.php');
    exit;
}


// Delete eBook
if (isset($_GET['del'])) {
    $id = $_GET['del'];
    $sql = "DELETE FROM tblbooks WHERE id=:id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    if($query->execute()){
        $_SESSION['toast'] = ['msg' => 'eBook deleted successfully!', 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['msg' => 'Failed to delete eBook!', 'type' => 'danger'];
    }
    header('location:manage-ebooks.php');
    exit;
}

$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Library Management System | Manage eBooks</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
body { background-color: #f8f9fa; }
.table thead th, .table tbody td { text-align: center; vertical-align: middle; }
.toast-container { z-index: 1100; }
.table thead th { background-color: #007bff; color: #fff; text-align: center; }
.table tbody td { vertical-align: middle; text-align: center; }
</style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="container my-3" style="padding-bottom: 50px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold text-primary">Manage eBooks</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEbookModal">
            <i class="bi bi-plus-circle"></i> Add eBook
        </button>
    </div>

    <div class="table-responsive shadow-sm rounded bg-white p-3">
        <table class="table table-striped table-hover table-bordered align-middle" id="ebooksTable">
            <thead class="table-primary text-white">
                <tr>
                    <th>#</th>
                    <th>Cover</th>
                    <th>Book Name</th>
                    <th>Category</th>
                    <th>Author</th>
                    <th>ISBN</th>
                    <th>Price</th>
                    <th>PDF</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
<?php
$sql = "SELECT b.id as bookid, b.BookName, b.ISBNNumber, b.BookPrice, b.bookImage, b.BookFile,
        b.CatId, b.AuthorId, c.CategoryName, a.AuthorName 
        FROM tblbooks b 
        JOIN tblcategory c ON c.id=b.CatId 
        JOIN tblauthors a ON a.id=b.AuthorId
        WHERE b.BookFile IS NOT NULL AND b.BookFile != ''";
$query = $dbh->prepare($sql);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_OBJ);
$cnt = 1;
foreach ($results as $result):
?>
<tr>
    <td><?php echo htmlentities($cnt); ?></td>
    <td><img src="bookimg/<?php echo htmlentities($result->bookImage); ?>" width="50" height="70"></td>
    <td class="text-start"><?php echo htmlentities($result->BookName); ?></td>
    <td><?php echo htmlentities($result->CategoryName); ?></td>
    <td><?php echo htmlentities($result->AuthorName); ?></td>
    <td><?php echo htmlentities($result->ISBNNumber); ?></td>
    <td><?php echo htmlentities($result->BookPrice); ?></td>
    <td>
        <?php if ($result->BookFile): ?>
            <a href="assets/pdf/<?= htmlentities($result->BookFile) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-file-earmark-pdf me-1"></i> View PDF
            </a>
        <?php else: ?>
            <span class="text-muted">No File</span>
        <?php endif; ?>
    </td>
    <td>
        <button 
            class="btn btn-primary btn-sm editEbookBtn"
            data-id="<?php echo $result->bookid; ?>"
            data-name="<?php echo htmlentities($result->BookName); ?>"
            data-cat-id="<?php echo $result->CatId; ?>"
            data-author-id="<?php echo $result->AuthorId; ?>"
            data-isbn="<?php echo htmlentities($result->ISBNNumber); ?>"
            data-price="<?php echo htmlentities($result->BookPrice); ?>"
            data-bs-toggle="modal" data-bs-target="#editEbookModal">
            Edit <i class="bi bi-pencil-square"></i>
        </button>
        <button class="btn btn-danger btn-sm deleteEbookBtn" data-id="<?php echo $result->bookid; ?>" data-bs-toggle="modal" data-bs-target="#deleteEbookModal">Delete <i class="bi bi-trash"></i></button>
    </td>
</tr>
<?php $cnt++; endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('includes/footer.php'); ?>

<?php
// Fetch categories alphabetically
$catSql = "SELECT id, CategoryName FROM tblcategory ORDER BY CategoryName ASC";
$catQuery = $dbh->prepare($catSql);
$catQuery->execute();
$categories = $catQuery->fetchAll(PDO::FETCH_OBJ);

// Fetch authors alphabetically
$authorSql = "SELECT id, AuthorName FROM tblauthors ORDER BY AuthorName ASC";
$authorQuery = $dbh->prepare($authorSql);
$authorQuery->execute();
$authors = $authorQuery->fetchAll(PDO::FETCH_OBJ);
?>

<!-- Add eBook Modal -->
<div class="modal fade" id="addEbookModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Add eBook</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Book Name</label>
                        <input type="text" name="bookName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select select2" name="catId" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat->id; ?>"><?php echo htmlentities($cat->CategoryName); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Author</label>
                        <select class="form-select select2" name="authorId" required>
                            <option value="">Select Author</option>
                            <?php foreach ($authors as $auth): ?>
                                <option value="<?php echo $auth->id; ?>"><?php echo htmlentities($auth->AuthorName); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ISBN</label>
                        <input type="text" name="isbn" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="text" name="price" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Book Image</label>
                        <input type="file" name="bookImage" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PDF File</label>
                        <input type="file" name="bookFile" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="addEbook" class="btn btn-success">Add</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit eBook Modal -->
<div class="modal fade" id="editEbookModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data" id="editEbookForm">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit eBook</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="bookId" id="editEbookId">
                    <div class="mb-3">
                        <label class="form-label">Book Name</label>
                        <input type="text" name="bookName" id="editEbookName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="catId" id="editCatId" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat->id; ?>"><?php echo htmlentities($cat->CategoryName); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Author</label>
                        <select name="authorId" id="editAuthorId" class="form-select" required>
                            <option value="">Select Author</option>
                            <?php foreach ($authors as $auth): ?>
                                <option value="<?php echo $auth->id; ?>"><?php echo htmlentities($auth->AuthorName); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ISBN</label>
                        <input type="text" name="isbn" id="editISBN" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="text" name="price" id="editPrice" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Book Image (Optional)</label>
                        <input type="file" name="bookImage" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PDF File (Optional)</label>
                        <input type="file" name="bookFile" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="updateEbook" class="btn btn-primary">Update</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteEbookModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">Are you sure you want to delete this eBook?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" class="btn btn-danger" id="confirmDeleteBtn">Delete</a>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="position-fixed bottom-0 end-0 p-3 toast-container">
<?php if($toast): ?>
    <div id="liveToast" class="toast align-items-center text-bg-<?php echo $toast['type']; ?> border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body"><?php echo htmlentities($toast['msg']); ?></div>
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
$(document).ready(function() {
    $('#ebooksTable').DataTable({
        "columnDefs": [{ "orderable": false, "targets": 8 }]
    });

    <?php if($toast): ?>
    var toastEl = document.getElementById('liveToast');
    var toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();
    <?php endif; ?>

    // Edit eBook
    $(document).on('click', '.editEbookBtn', function() {
        $('#editEbookId').val($(this).data('id'));
        $('#editEbookName').val($(this).data('name'));
        $('#editCatId').val($(this).data('cat-id'));
        $('#editAuthorId').val($(this).data('author-id'));
        $('#editISBN').val($(this).data('isbn'));
        $('#editPrice').val($(this).data('price'));
    });

    // Delete eBook
    $(document).on('click', '.deleteEbookBtn', function() {
        const bookId = $(this).data('id');
        $('#confirmDeleteBtn').attr('href', 'manage-ebooks.php?del=' + bookId);
    });
});
</script>

</body>
</html>
