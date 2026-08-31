<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduNexo - Panel Académico</title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#"><i class="bi bi-mortarboard-fill me-2"></i>EduNexo</a>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 text-center">
                        <h2 class="card-title text-primary fw-bold mb-3">¡Sistema MVC Operativo!</h2>
                        <p class="card-text text-muted">
                            La estructura del proyecto está lista. La base de datos <code>edunexo_db</code> está conectada a MySQL en Laragon.
                        </p>
                        <hr class="my-4">
                        <div class="d-flex justify-content-center gap-2">
                            <span class="badge bg-success p-2"><i class="bi bi-check-circle me-1"></i> MySQL OK</span>
                            <span class="badge bg-primary p-2"><i class="bi bi-bootstrap me-1"></i> Bootstrap 5 OK</span>
                            <span class="badge bg-warning text-dark p-2"><i class="bi bi-clock me-1"></i> WhatsApp API: Pendiente</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>