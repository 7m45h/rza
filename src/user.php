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

function validate_user($conn, $uname, $pass)
{
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE uname = ? AND pass = ?");
    $stmt->bindParam(1, $uname);
    $stmt->bindParam(2, $pass);
    $stmt->execute();

    if ($stmt->fetchColumn()) {
        return true;
    }

    return false;
}

function is_user_exist($conn, $uname)
{
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE uname = ?");
    $stmt->bindParam(1, $uname);
    $stmt->execute();

    if ($stmt->fetchColumn()) {
        return true;
    }

    return false;
}

function set_token($conn, $uname, $pass)
{
    $token = password_hash($uname . $pass, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "UPDATE users SET token = ? WHERE uname = ? AND pass = ?"
    );
    $stmt->bindParam(1, $token);
    $stmt->bindParam(2, $uname);
    $stmt->bindParam(3, $pass);
    $stmt->execute();

    return $token;
}

function add_user($conn, $uname, $pass)
{
    $stmt = $conn->prepare("INSERT INTO users(uname, pass) VALUES(?, ?)");
    $stmt->bindParam(1, $uname);
    $stmt->bindParam(2, $pass);
    $stmt->execute();
}

function signin($data, $conn)
{
    $uname = $data["uname"];
    $pass = $data["pass"];

    $valid = validate_user($conn, $uname, $pass);

    $rcode = 200;
    $token = "";

    if ($valid) {
        $token = set_token($conn, $uname, $pass);
    } else {
        $rcode = 404;
    }

    http_response_code($rcode);
    echo json_encode(["token" => $token]);
}

function signup($data, $conn)
{
    $uname = $data["uname"];
    $pass = $data["pass"];

    $user_exist = is_user_exist($conn, $uname);

    $rcode = 200;
    $token = "";

    if ($user_exist) {
        $rcode = 403;
    } else {
        add_user($conn, $uname, $pass);
        $token = set_token($conn, $uname, $pass);
    }

    http_response_code($rcode);
    echo json_encode(["token" => $token]);
}

function auth_token($data, $conn)
{
    $token = $data["token"];

    $stmt = $conn->prepare("SELECT 1 FROM users WHERE token = ?");
    $stmt->bindParam(1, $token);
    $stmt->execute();

    if ($stmt->fetchColumn()) {
        echo json_encode(["token" => $token]);
    } else {
        http_response_code(403);
        echo json_encode(["token" => ""]);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    switch ($data["action"]) {
        case "SIGNIN":
            signin($data, $conn);
            break;

        case "SIGNUP":
            signup($data, $conn);
            break;

        case "TOKEN":
            auth_token($data, $conn);
            break;
    }
} else {
    http_response_code(403);
    echo json_encode(["token" => ""]);
}

?>
