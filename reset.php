<?php

$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'password';
$db_dbnm = 'pricing';

$db = new mysqli($db_host, $db_user, $db_pass, $db_dbnm);

$query = "TRUNCATE intervals";
$stmn = $db->prepare($query);
$stmn->execute();
$stmn->close();

$db->close();

header('Location: frontend.php');
