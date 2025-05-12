<?php
include '../includes/header.php';
?>

<div class="container mt-5 text-center">
    <h1 class="mb-4 display-4 font-weight-bold text-primary">Módulo de Roles</h1>
    <div class="list-group w-50 mx-auto">
        <a href="roles.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center mb-3 rounded shadow-sm">
            <span class="h5 mb-0">Gestión de Roles</span>
            <i class="fas fa-user-shield fa-2x text-primary"></i>
        </a>
        <a href="usuarios.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center mb-3 rounded shadow-sm">
            <span class="h5 mb-0">Gestión de Usuarios</span>
            <i class="fas fa-users fa-2x text-success"></i>
        </a>
    </div>
</div>

<?php
include '../includes/footer.php';
?>