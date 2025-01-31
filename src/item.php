<?php
$DB_URL = getenv("DBURI");

$conn = new PDO($DB_URL);
if (!$conn) {
    die("db connection error: " . pg_last_error());
}

$body = file_get_contents("php://input");
$data = json_decode($body, true);

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

    if ($valid) {
        $uname = uname_from_token($conn, $token);

        $stmt = $conn->prepare("SELECT * FROM items");
        $stmt->execute();

        $resarr = [];
        foreach ($stmt as $row) {
            $resarr[] = [
                "id" => $row[0],
                "title" => $row[1],
                "category" => $row[2],
                "situation" => $row[3],
                "note" => $row[4],
                "owned" => $row[5] == $uname,
            ];
        }

        echo json_encode($resarr);
    } else {
        http_response_code(403);
        echo json_encode([]);
    }
}

function insert_item($conn, $title, $category, $situation, $note, $uname)
{
    $id = time() . rand(0, 9);

    $stmt = $conn->prepare("INSERT INTO items VALUES(?, ?, ?, ?, ?, ?)");
    $stmt->bindParam(1, $id);
    $stmt->bindParam(2, $title);
    $stmt->bindParam(3, $category);
    $stmt->bindParam(4, $situation);
    $stmt->bindParam(5, $note);
    $stmt->bindParam(6, $uname);
    $stmt->execute();

    return $id;
}

function add_item($data, $conn)
{
    $title = $data["title"];
    $category = $data["category"];
    $situation = $data["situation"];
    $note = $data["note"];
    $token = $data["token"];
    $valid = validate_token($conn, $token);

    if ($valid) {
        $uname = uname_from_token($conn, $token);
        $id = insert_item($conn, $title, $category, $situation, $note, $uname);

        echo json_encode([
            "id" => $id,
            "title" => $title,
            "category" => $category,
            "situation" => $situation,
            "note" => $note,
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
    case "GET":
        get_all($data, $conn);
        break;

    case "POST":
        add_item($data, $conn);
        break;

    case "DELETE":
        delete_item($data, $conn);
        break;
}
?>
