<?php

function validateSession($requiredRole = null)
{

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        header("Location: ../index.php");
        exit();
    }

    if ($requiredRole && $_SESSION['user_role'] !== $requiredRole) {
        header("Location: ../index.php");
        exit();
    }
}
?>