<?php
session_start();
error_reporting(0);
include('includes/config.php');

if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit;
}

// Handle Return Book submission
if(isset($_POST['return'])){
    $rid = intval($_POST['rid']);
    $fine = $_POST['fine'];
    $bookid = intval($_POST['bookid']);
    $rstatus = 1;

    // Update return status
    $sql = "UPDATE tblissuedbookdetails SET fine=:fine, RetrunStatus=:rstatus WHERE id=:rid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':rid', $rid, PDO::PARAM_INT);
    $query->bindParam(':fine', $fine, PDO::PARAM_STR);
    $query->bindParam(':rstatus', $rstatus, PDO::PARAM_INT);
    $query->execute();

    // Update book availability
    $sql2 = "UPDATE tblbooks SET isIssued=0 WHERE id=:bookid";
    $query2 = $dbh->prepare($sql2);
    $query2->bindParam(':bookid', $bookid, PDO::PARAM_INT);
    $query2->execute();

    $_SESSION['toast'] = ["type"=>"success","msg"=>"Book returned successfully"];
    header('Location: manage-issued-books.php');
    exit();
}

// Handle Issue Book submission
if (isset($_POST['issue'])) {
    $studentid = strtoupper($_POST['studentid']);
    $bookid = $_POST['bookid'];
    $aremark = $_POST['aremark'];
    $aqty = 1; // Replace with actual quantity check from DB if needed

    if ($aqty > 0) {
        $sql = "INSERT INTO tblissuedbookdetails(StudentID,BookId,remark) VALUES(:studentid,:bookid,:aremark)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':studentid', $studentid, PDO::PARAM_STR);
        $query->bindParam(':bookid', $bookid, PDO::PARAM_INT);
        $query->bindParam(':aremark', $aremark, PDO::PARAM_STR);
        $query->execute();
        $lastInsertId = $dbh->lastInsertId();
        if ($lastInsertId) {
            $_SESSION['toast'] = ["type"=>"success","msg"=>"Book issued successfully"];
        } else {
            $_SESSION['toast'] = ["type"=>"danger","msg"=>"Something went wrong. Please try again"];
        }
    } else {
        $_SESSION['toast'] = ["type"=>"danger","msg"=>"Book not available"];
    }
    header('Location: manage-issued-books.php');
    exit();
}

// Toast messages
$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Library Management System | Manage Issued Books</title>

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<style>
body { background-color: #f8f9fa; }
.table thead th, .table tbody td { text-align: center; vertical-align: middle; }
.table thead th { background-color: #007bff; color: #fff; }
.toast-container { z-index: 1100; }
</style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="container my-3" style="padding-bottom: 50px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="fw-bold text-primary">Manage Issued Books</h2>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#issueBookModal">
      <i class="bi bi-plus-circle"></i>New Issue Book
    </button>
  </div>

  <div class="table-responsive shadow-sm rounded bg-white p-3"  >
    <table class="table table-striped table-hover table-bordered align-middle" id="issuedBooksTable">
      <thead>
        <tr>
          <th>#</th>
          <th>Student Name</th>
          <th>Book Name</th>
          <th>ISBN</th>
          <th>Issued Date</th>
          <th>Return Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
<?php 
$sql = "SELECT d.id as rid, s.FullName, s.StudentId, s.EmailId, s.MobileNumber, b.BookName, b.ISBNNumber, b.id as BookId, b.bookImage, d.IssuesDate, d.ReturnDate, d.fine, d.RetrunStatus 
        FROM tblissuedbookdetails d
        JOIN tblstudents s ON s.StudentId=d.StudentId
        JOIN tblbooks b ON b.id=d.BookId
        ORDER BY d.id DESC";
$query = $dbh->prepare($sql);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_OBJ);
$cnt = 1;
foreach ($results as $result):
?>
<tr>
    <td><?= htmlentities($cnt) ?></td>
    <td class="text-start"><?= htmlentities($result->FullName) ?></td>
    <td class="text-start"><?= htmlentities($result->BookName) ?></td>
    <td><?= htmlentities($result->ISBNNumber) ?></td>
    <td><?= htmlentities($result->IssuesDate) ?></td>
    <td><?= $result->ReturnDate ? htmlentities($result->ReturnDate) : "Not Returned Yet" ?></td>
    <td>
        <button class="btn btn-primary btn-sm editIssuedBookBtn"
            data-rid="<?= $result->rid ?>"
            data-bookid="<?= $result->BookId ?>"
            data-studentid="<?= htmlentities($result->StudentId) ?>"
            data-studentname="<?= htmlentities($result->FullName) ?>"
            data-email="<?= htmlentities($result->EmailId) ?>"
            data-contact="<?= htmlentities($result->MobileNumber) ?>"
            data-bookname="<?= htmlentities($result->BookName) ?>"
            data-isbn="<?= htmlentities($result->ISBNNumber) ?>"
            data-issue="<?= htmlentities($result->IssuesDate) ?>"
            data-return="<?= $result->ReturnDate ?: 'Not Returned Yet' ?>"
            data-image="bookimg/<?= htmlentities($result->bookImage) ?>"
            data-fine="<?= htmlentities($result->fine) ?>"
            data-returnstatus="<?= $result->RetrunStatus ?>"
        >
            <i class="bi bi-pencil-square"></i> Return
        </button>
    </td>
</tr>
<?php $cnt++; endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Edit Issued Book Modal -->
<div class="modal fade" id="editIssuedBookModal" tabindex="-1" aria-labelledby="editIssuedBookModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="editIssuedBookForm" method="post">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Update Issued Book</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="rid" id="editRid">
          <input type="hidden" name="bookid" id="editBookId">

          <h6>Student Details</h6><hr>
          <div class="row mb-2">
            <div class="col-md-6"><strong>ID:</strong> <span id="editStudentId"></span></div>
            <div class="col-md-6"><strong>Name:</strong> <span id="editStudentName"></span></div>
          </div>
          <div class="row mb-2">
            <div class="col-md-6"><strong>Email:</strong> <span id="editStudentEmail"></span></div>
            <div class="col-md-6"><strong>Contact:</strong> <span id="editStudentContact"></span></div>
          </div>

          <h6>Book Details</h6><hr>
          <div class="row mb-2">
            <div class="col-md-6"><img id="editBookImage" src="" width="120"></div>
            <div class="col-md-6">
              <strong>Name:</strong> <span id="editBookName"></span><br>
              <strong>ISBN:</strong> <span id="editBookISBN"></span>
            </div>
          </div>
          <div class="row mb-2">
            <div class="col-md-6"><strong>Issued Date:</strong> <span id="editIssueDate"></span></div>
            <div class="col-md-6"><strong>Return Date:</strong> <span id="editReturnDate"></span></div>
          </div>

          <div class="mb-2">
            <label for="editFine">Fine (USD):</label>
            <input type="text" class="form-control" name="fine" id="editFine" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="return" class="btn btn-success">Return Book</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Issue Book Modal -->
<div class="modal fade" id="issueBookModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="post" id="issueBookForm">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Issue a New Book</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">

          <!-- Student Details -->
          <h6>Student Details</h6>
          <hr>
          <div class="mb-3">
            <label for="issueStudentId">Student ID <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="studentid" id="issueStudentId" onBlur="getstudent()" autocomplete="off" required>
          </div>
          <div id="get_student_name" class="mb-3" style="font-size:16px;"></div>

          <!-- Book Details -->
          <h6>Book Details</h6>
          <hr>
          <div class="mb-3">
            <label for="issueBookId">ISBN Number or Book Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="bookid" id="issueBookId" onBlur="getbook()" required>
          </div>
          <div id="get_book_name" class="mb-3"></div>

          <!-- Remark -->
          <h6>Remark</h6>
          <hr>
          <div class="mb-3">
            <textarea class="form-control" name="aremark" id="aremark" placeholder="Enter remarks here" required></textarea>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" name="issue" class="btn btn-info text-white">Issue Book</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </form>
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

    // Populate Edit Modal
    $('.editIssuedBookBtn').click(function() {
        var btn = $(this);
        $('#editRid').val(btn.data('rid'));
        $('#editBookId').val(btn.data('bookid'));
        $('#editStudentId').text(btn.data('studentid'));
        $('#editStudentName').text(btn.data('studentname'));
        $('#editStudentEmail').text(btn.data('email'));
        $('#editStudentContact').text(btn.data('contact'));
        $('#editBookName').text(btn.data('bookname'));
        $('#editBookISBN').text(btn.data('isbn'));
        $('#editIssueDate').text(btn.data('issue'));
        $('#editReturnDate').text(btn.data('return'));
        $('#editBookImage').attr('src', btn.data('image'));
        $('#editFine').val(btn.data('fine') || '');

        if(btn.data('returnstatus') == 1){
            $('#editFine').prop('readonly', true);
            $('#editIssuedBookForm button[name="return"]').hide();
        } else {
            $('#editFine').prop('readonly', false);
            $('#editIssuedBookForm button[name="return"]').show();
        }

        var editModal = new bootstrap.Modal(document.getElementById('editIssuedBookModal'));
        editModal.show();
    });
});

// AJAX functions for student/book info
function getstudent() {
    $("#loaderIcon").show();
    $.ajax({
        url: "get_student.php",
        type: "POST",
        data: { studentid: $("#issueStudentId").val() },
        success: function(data){ $("#get_student_name").html(data); $("#loaderIcon").hide(); }
    });
}

function getbook() {
    $("#loaderIcon").show();
    $.ajax({
        url: "get_book.php",
        type: "POST",
        data: { bookid: $("#issueBookId").val() },
        success: function(data){ $("#get_book_name").html(data); $("#loaderIcon").hide(); }
    });
}

// Toast
<?php if($toast): ?>
var toastEl = document.getElementById('liveToast');
var toast = new bootstrap.Toast(toastEl, { delay: 4000 });
toast.show();
<?php endif; ?>
</script>

<!-- Toast HTML -->
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

</body>
</html>
