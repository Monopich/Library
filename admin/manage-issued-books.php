<?php
session_start();
error_reporting(0);
include('includes/config.php'); // this loads $lang according to selected language

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

    $sql = "UPDATE tblissuedbookdetails SET fine=:fine, RetrunStatus=:rstatus WHERE id=:rid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':rid', $rid, PDO::PARAM_INT);
    $query->bindParam(':fine', $fine, PDO::PARAM_STR);
    $query->bindParam(':rstatus', $rstatus, PDO::PARAM_INT);
    $query->execute();

    $sql2 = "UPDATE tblbooks SET isIssued=0 WHERE id=:bookid";
    $query2 = $dbh->prepare($sql2);
    $query2->bindParam(':bookid', $bookid, PDO::PARAM_INT);
    $query2->execute();

    $_SESSION['toast'] = ["type"=>"success","msg"=>$lang['book_returned_success']];
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
        $_SESSION['toast'] = $lastInsertId 
            ? ["type"=>"success","msg"=>$lang['book_issued_success']]
            : ["type"=>"danger","msg"=>$lang['something_wrong']];
    } else {
        $_SESSION['toast'] = ["type"=>"danger","msg"=>$lang['book_not_available']];
    }
    header('Location: manage-issued-books.php');
    exit();
}

$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="<?= $lang['lang_code'] ?? 'en' ?>">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?= $lang['manage_issued_books'] ?></title>

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
    <h2 class="fw-bold text-primary"><?= $lang['manage_issued_books'] ?></h2>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#issueBookModal">
      <i class="bi bi-plus-circle"></i><?= " ".$lang['new_issue_book'] ?>
    </button>
  </div>

  <div class="table-responsive shadow-sm rounded bg-white p-3">
    <table class="table table-striped table-hover table-bordered align-middle" id="issuedBooksTable">
      <thead>
        <tr>
          <th>#</th>
          <th><?= $lang['student_name'] ?></th>
          <th><?= $lang['book_name'] ?></th>
          <th><?= $lang['isbn'] ?></th>
          <th><?= $lang['issued_date'] ?></th>
          <th><?= $lang['return_date'] ?></th>
          <th><?= $lang['action'] ?></th>
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
    <td><?= $result->ReturnDate ? htmlentities($result->ReturnDate) : $lang['not_returned_yet'] ?></td>
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
            data-return="<?= $result->ReturnDate ?: $lang['not_returned_yet'] ?>"
            data-image="bookimg/<?= htmlentities($result->bookImage) ?>"
            data-fine="<?= htmlentities($result->fine) ?>"
            data-returnstatus="<?= $result->RetrunStatus ?>"
        >
            <i class="bi bi-pencil-square"></i> <?= $lang['return_book'] ?>
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
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="editIssuedBookForm" method="post">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><?= $lang['update_issued_book'] ?></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="rid" id="editRid">
          <input type="hidden" name="bookid" id="editBookId">

          <h6><?= $lang['student_details'] ?></h6><hr>
          <div class="row mb-2">
            <div class="col-md-6"><strong><?= $lang['student_id'] ?>:</strong> <span id="editStudentId"></span></div>
            <div class="col-md-6"><strong><?= $lang['student_name'] ?>:</strong> <span id="editStudentName"></span></div>
          </div>
          <div class="row mb-2">
            <div class="col-md-6"><strong><?= $lang['email'] ?>:</strong> <span id="editStudentEmail"></span></div>
            <div class="col-md-6"><strong><?= $lang['contact'] ?>:</strong> <span id="editStudentContact"></span></div>
          </div>

          <h6><?= $lang['book_details'] ?></h6><hr>
          <div class="row mb-2">
            <div class="col-md-6"><img id="editBookImage" src="" width="120"></div>
            <div class="col-md-6">
              <strong><?= $lang['book_name'] ?>:</strong> <span id="editBookName"></span><br>
              <strong><?= $lang['isbn'] ?>:</strong> <span id="editBookISBN"></span>
            </div>
          </div>
          <div class="row mb-2">
            <div class="col-md-6"><strong><?= $lang['issued_date'] ?>:</strong> <span id="editIssueDate"></span></div>
            <div class="col-md-6"><strong><?= $lang['return_date'] ?>:</strong> <span id="editReturnDate"></span></div>
          </div>

          <div class="mb-2">
            <label for="editFine"><?= $lang['fine_usd'] ?>:</label>
            <input type="text" class="form-control" name="fine" id="editFine" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="return" class="btn btn-success"><?= $lang['return_book'] ?></button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $lang['close'] ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Issue Book Modal -->
<div class="modal fade" id="issueBookModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" id="issueBookForm">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><?= $lang['issue_book'] ?></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">

          <!-- Student Details -->
          <h6><?= $lang['student_details'] ?></h6>
          <div class="mb-3">
            <label for="issueStudentId"><?= $lang['student_id'] ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="studentid" id="issueStudentId" onBlur="getstudent()" autocomplete="off" required>
          </div>
          <!-- Student info display -->
          <div id="get_student_name" class="mb-3 p-3 border rounded" style="background-color: #f8f9fa; display: none;">
            <div class="row mb-1">
              <div class="col-md-4"><?= $lang['student_name'] ?></strong></div>
              <div class="col-md-8" id="displayStudentName">:</div>
            </div>
            <div class="row mb-1">
              <div class="col-md-4"><?= $lang['email'] ?></strong></div>
              <div class="col-md-8" id="displayStudentEmail">:</div>
            </div>
            <div class="row mb-1">
              <div class="col-md-4"><?= $lang['contact'] ?></strong></div>
              <div class="col-md-8" id="displayStudentContact">:</div>
            </div>
          </div>


          <!-- Book Details -->
          <h6><?= $lang['book_details'] ?></h6>

          <div class="mb-3">
              <label for="issueBookId"><?= $lang['isbn_or_title'] ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="bookid" id="issueBookId" onBlur="getbook()" autocomplete="off" required>
          </div>

          <!-- Book info display -->
          <div id="get_book_name" class="mb-3 p-3 border rounded" style="background-color: #f8f9fa; display: none;">
              <div class="row mb-1">
                  <div class="col-md-4"><strong><?= $lang['book_name'] ?>:</strong></div>
                  <div class="col-md-8" id="displayBookName"></div>
              </div>
              <div class="row mb-1">
                  <div class="col-md-4"><strong><?= $lang['isbn'] ?>:</strong></div>
                  <div class="col-md-8" id="displayBookISBN"></div>
              </div>
              <div class="row mb-1">
                  <div class="col-md-4"><strong><?= $lang['author'] ?>:</strong></div>
                  <div class="col-md-8" id="displayBookAuthor"></div>
              </div>
              <div class="row mb-1">
                  <div class="col-md-4"><strong><?= $lang['image'] ?>:</strong></div>
                  <div class="col-md-8"><img id="displayBookImage" src="" width="80"></div>
              </div>
          </div>

          <!-- Remark -->
          <h6><?= $lang['remark'] ?></h6>
          <div class="mb-3">
            <textarea class="form-control" name="aremark" id="aremark" placeholder="<?= $lang['enter_remarks_here'] ?>" required></textarea>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" name="issue" class="btn btn-primary text-white"><?= $lang['issue_book'] ?></button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $lang['close'] ?></button>
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

function getstudent() {
    var studentId = $("#issueStudentId").val().trim();
    if(studentId === '') {
        $("#get_student_name").hide();
        return;
    }

    $.ajax({
        url: "get_student.php",
        type: "POST",
        data: { studentid: studentId },
        dataType: "json",
        success: function(data){
            $("#get_student_name").show();
            if(data.success) {
                $("#displayStudentName").text(data.name);
                $("#displayStudentEmail").text(data.email);
                $("#displayStudentContact").text(data.contact);
                $("#submit").prop('disabled', false);
            } else {
                $("#displayStudentName").text(data.msg);
                $("#displayStudentEmail").text('');
                $("#displayStudentContact").text('');
                $("#submit").prop('disabled', true);
            }
        },
        error: function() {
            $("#displayStudentName").text("Error fetching student info");
            $("#displayStudentEmail").text('');
            $("#displayStudentContact").text('');
            $("#submit").prop('disabled', true);
        }
    });
}

function getbook() {
    var bookId = $("#issueBookId").val().trim();
    if (bookId == '') {
        $("#get_book_name").hide();
        return;
    }

    $("#get_book_name").show();
    $.ajax({
        url: "get_book.php",
        type: "POST",
        data: { bookid: bookId },
        success: function(data) {
            $("#get_book_name").html(data);

            // Handle selection of a book
            $(".selectBookBtn").click(function() {
              var card = $(this).closest(".card");

              // Hide the select button
              $(this).hide();

              // Remove all other cards
              $("#get_book_name .card").not(card).remove();

              // Remove previous hidden inputs
              $("#get_book_name input[name='bookid']").remove();

              // Append hidden input inside card footer
              var selectedBookId = $(this).data("bookid");
              card.find(".card-footer").append('<input type="hidden" name="bookid" value="' + selectedBookId + '" required>');

              // Add Cancel button below (if not exists)
              if (card.find(".cancelSelectBtn").length == 0) {
                  card.find(".card-footer").append('<button type="button" class="btn btn-danger mt-2 w-100 btn-sm cancelSelectBtn"><?= $lang["cancel"] ?></button>');
              }

              // Handle Cancel click
              card.find(".cancelSelectBtn").click(function() {
                  // Remove hidden input
                  card.find('input[name="bookid"]').remove();
                  // Remove Cancel button
                  $(this).remove();
                  // Show Select button again
                  card.find(".selectBookBtn").show();
                  // Reload the book list
                  getbook();
              });
          });
        }
    });
}


// Toast
<?php if($toast): ?>
var toastEl = document.getElementById('liveToast');
var toast = new bootstrap.Toast(toastEl, { delay: 4000 });
toast.show();
<?php endif; ?>
</script>

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
</body>
</html>
