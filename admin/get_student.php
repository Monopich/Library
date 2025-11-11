<?php
require_once("includes/config.php");
header('Content-Type: application/json');

$response = ["success" => false, "name" => "", "email" => "", "contact" => "", "msg" => ""];

if (!empty($_POST["studentid"])) {
    $studentid = strtoupper($_POST["studentid"]);

    $sql = "SELECT FullName, Status, EmailId, MobileNumber FROM tblstudents WHERE StudentId=:studentid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':studentid', $studentid, PDO::PARAM_STR);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_OBJ);

    if ($result) {
        if ($result->Status == 0) {
            $response["msg"] = "Student ID Blocked";
        } else {
            $response["success"] = true;
            $response["name"] = $result->FullName;
            $response["email"] = $result->EmailId;
            $response["contact"] = $result->MobileNumber;
        }
    } else {
        $response["msg"] = "Invalid Student ID";
    }
}

echo json_encode($response);

?>
