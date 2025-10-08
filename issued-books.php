<?php
session_start();
error_reporting(0);
include('includes/config.php');

if (strlen($_SESSION['login']) == 0) {
    header('location:index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Student | Issued Books</title>

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<style>
body { background-color: #f8f9fa; }
.table thead th, .table tbody td { text-align: center; vertical-align: middle; }
.table thead th { background-color: #007bff; color: #fff; }
.table tbody td { vertical-align: middle; text-align: center; }
.not-returned { color: red; font-weight: bold; }
.modal-body img { max-width: 150px; border-radius: 5px; }
</style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="container my-3">
    <h2 class="mb-4 fw-bold text-primary">My Issued Books</h2>

    <div class="table-responsive shadow-sm rounded bg-white p-3">
        <table class="table table-striped table-hover table-bordered align-middle" id="issuedBooksTable">
            <thead class="table-primary text-white">
                <tr>
                    <th>#</th>
                    <th>Book Name</th>
                    <th>ISBN</th>
                    <th>Issued Date</th>
                    <th>Return Date</th>
                    <th>Fine (USD)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
<?php 
$sid = $_SESSION['stdid'];
$sql = "SELECT d.id as rid, b.BookName, b.ISBNNumber, b.bookImage, d.IssuesDate, d.ReturnDate, d.fine, d.RetrunStatus 
        FROM tblissuedbookdetails d
        JOIN tblbooks b ON b.id=d.BookId
        WHERE d.StudentId=:sid
        ORDER BY d.id DESC";
$query = $dbh->prepare($sql);
$query->bindParam(':sid', $sid, PDO::PARAM_STR);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_OBJ);
$cnt = 1;
foreach ($results as $result):
?>
<tr>
    <td><?= htmlentities($cnt) ?></td>
    <td class="text-start"><?= htmlentities($result->BookName) ?></td>
    <td><?= htmlentities($result->ISBNNumber) ?></td>
    <td><?= htmlentities($result->IssuesDate) ?></td>
    <td><?= $result->ReturnDate ? htmlentities($result->ReturnDate) : "<span class='not-returned'>Not Returned Yet</span>" ?></td>
    <td><?= htmlentities($result->fine) ?></td>
    <td>
        <button class="btn btn-primary btn-sm viewIssuedBookBtn"
            data-bookname="<?= htmlentities($result->BookName) ?>"
            data-isbn="<?= htmlentities($result->ISBNNumber) ?>"
            data-issue="<?= htmlentities($result->IssuesDate) ?>"
            data-return="<?= $result->ReturnDate ?: 'Not Returned Yet' ?>"
            data-fine="<?= htmlentities($result->fine) ?>"
            data-image="admin/bookimg/<?= htmlentities($result->bookImage) ?>"
        >
            <i class="bi bi-eye"></i> View
        </button>
    </td>
</tr>
<?php $cnt++; endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- View Issued Book Modal -->
<div class="modal fade" id="viewIssuedBookModal" tabindex="-1" aria-labelledby="viewIssuedBookModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="viewIssuedBookModalLabel">Issued Book Details</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row mb-3">
              <div class="col-md-4">
                  <img id="viewBookImage" src="" alt="Book Image">
              </div>
              <div class="col-md-8">
                  <p><strong>Book Name:</strong> <span id="viewBookName"></span></p>
                  <p><strong>ISBN:</strong> <span id="viewBookISBN"></span></p>
                  <p><strong>Issued Date:</strong> <span id="viewIssueDate"></span></p>
                  <p><strong>Return Date:</strong> <span id="viewReturnDate"></span></p>
                  <p><strong>Fine (USD):</strong> <span id="viewFine"></span></p>
              </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
  </div>
</div>

<?php include('includes/footer.php'); ?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#issuedBooksTable').DataTable({
        "columnDefs": [{ "orderable": false, "targets": 6 }]
    });

    // Populate modal with data
    $('.viewIssuedBookBtn').click(function() {
        var btn = $(this);
        $('#viewBookName').text(btn.data('bookname'));
        $('#viewBookISBN').text(btn.data('isbn'));
        $('#viewIssueDate').text(btn.data('issue'));
        $('#viewReturnDate').text(btn.data('return'));
        $('#viewFine').text(btn.data('fine'));
        $('#viewBookImage').attr('src', btn.data('image'));
        $('#viewIssuedBookModal').modal('show');
    });
});
</script>

</body>
</html>
