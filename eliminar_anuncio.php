<?php
session_start();
if (!isset($_SESSION['admin_logged'])) { header("Location: login.php"); exit(); }
require_once 'conexion.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conexion->prepare("DELETE FROM anuncios WHERE id = ?");
    $stmt->execute([$id]);
}
header("Location: admin.php");
exit();