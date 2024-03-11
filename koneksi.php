<?php
$conn = mysqli_connect("localhost", "root", "", "edas");

if ($conn->connect_error) {
    die('Connect Error (' . $db->connect_errno . ')' . $db->connect_error);
}
