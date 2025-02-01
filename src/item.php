<?php
include "cors.php";

$DB_URL = getenv("DBURI");

$conn = new PDO($DB_URL);
if (!$conn) {
    die("db connection error: " . pg_last_error());
}

$body = file_get_contents("php://input");
$data = json_decode($body, true);

cors();
header("Content-Type: application/json; charset=utf-8");

function uname_from_token($conn, $token)
{
    $stmt = $conn->prepare("SELECT uname FROM users WHERE token = ?");
    $stmt->bindParam(1, $token);
    $stmt->execute();
    return $stmt->fetch()["uname"];
}

function validate_token($conn, $token)
{
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE token = ?");
    $stmt->bindParam(1, $token);
    $stmt->execute();

    if ($stmt->fetchColumn()) {
        return true;
    }

    return false;
}

function get_all($data, $conn)
{
    $token = $data["token"];
    $valid = validate_token($conn, $token);
    $uname = "";

    if ($valid) {
        $uname = uname_from_token($conn, $token);
    }

    $stmt = $conn->prepare("SELECT * FROM items");
    $stmt->execute();

    $resarr = [];
    foreach ($stmt as $row) {
        $resarr[] = [
            "id" => $row[0],
            "title" => $row[1],
            "category" => $row[2],
            "status" => $row[3] == "found",
            "note" => $row[4],
            "img" => $row[5],
            "owned" => $row[6] == $uname,
        ];
    }

    echo json_encode($resarr);
}

function insert_item($conn, $title, $category, $status, $note, $img, $uname)
{
    $id = time() . rand(0, 9);

    $stmt = $conn->prepare("INSERT INTO items VALUES(?, ?, ?, ?, ?, ?, ?)");
    $stmt->bindParam(1, $id);
    $stmt->bindParam(2, $title);
    $stmt->bindParam(3, $category);
    $stmt->bindParam(4, $status);
    $stmt->bindParam(5, $note);
    $stmt->bindParam(6, $img);
    $stmt->bindParam(7, $uname);
    $stmt->execute();

    return $id;
}

function add_item($data, $conn)
{
    $title = $data["title"];
    $category = $data["category"];
    $status = $data["status"] ? "found" : "lost";
    $note = $data["note"];
    $img = $data["img"];
    $token = $data["token"];
    $valid = validate_token($conn, $token);

    if ($valid) {
        $uname = uname_from_token($conn, $token);
        $id = insert_item(
            $conn,
            $title,
            $category,
            $status,
            $note,
            $img,
            $uname
        );

        echo json_encode([
            "id" => $id,
            "title" => $title,
            "category" => $category,
            "status" => $status,
            "note" => $note,
            "img" => $img,
            "owned" => true,
        ]);
    } else {
        http_response_code(403);
        echo json_encode([]);
    }
}

function delete_item($data, $conn)
{
    $id = $data["id"];
    $token = $data["token"];
    $valid = validate_token($conn, $token);

    if ($valid) {
        $uname = uname_from_token($conn, $token);

        $stmt = $conn->prepare("DELETE FROM items WHERE id = ? AND owner = ?");
        $stmt->bindParam(1, $id);
        $stmt->bindParam(2, $uname);
        $stmt->execute();
    } else {
        http_response_code(403);
    }

    echo json_encode([]);
}

switch ($_SERVER["REQUEST_METHOD"]) {
    case "POST":
        switch ($data["action"]) {
            case "ADD":
                add_item($data, $conn);
                break;

            case "GET":
                get_all($data, $conn);
                break;
        }
        break;

    case "DELETE":
        delete_item($data, $conn);
        break;
}
?>
