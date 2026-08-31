<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\SecurityHelper;

class AdminTipoEvaluacionController {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
            header('Location: /edunexo/login');
            exit;
        }
    }

    public function index() {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM tipo_evaluacion ORDER BY nombre");
        $tipos = $stmt->fetchAll();

        require __DIR__ . '/../views/admin/tipos_evaluacion.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /edunexo/admin/tipos_evaluacion');
            exit;
        }

        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header('Location: /edunexo/admin/tipos_evaluacion');
            exit;
        }

        $nombre = SecurityHelper::sanitize($_POST['nombre'] ?? '');
        $peso = (float)($_POST['peso_porcentaje'] ?? 0);

        if (empty($nombre) || $peso <= 0) {
            $_SESSION['error'] = 'Datos inválidos para el tipo de evaluación.';
            header('Location: /edunexo/admin/tipos_evaluacion');
            exit;
        }

        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT id FROM tipo_evaluacion WHERE nombre = ?");
        $stmt->execute([$nombre]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Ya existe un tipo de evaluación con ese nombre.';
            header('Location: /edunexo/admin/tipos_evaluacion');
            exit;
        }

        $stmt = $db->prepare("INSERT INTO tipo_evaluacion (nombre, peso_porcentaje) VALUES (?, ?)");
        if ($stmt->execute([$nombre, $peso])) {
            $_SESSION['success'] = 'Tipo de evaluación creado correctamente.';
        } else {
            $_SESSION['error'] = 'Error al crear el tipo de evaluación.';
        }

        header('Location: /edunexo/admin/tipos_evaluacion');
        exit;
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /edunexo/admin/tipos_evaluacion');
            exit;
        }

        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header('Location: /edunexo/admin/tipos_evaluacion');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $nombre = SecurityHelper::sanitize($_POST['nombre'] ?? '');
        $peso = (float)($_POST['peso_porcentaje'] ?? 0);

        if (empty($nombre) || $id === 0 || $peso <= 0) {
            $_SESSION['error'] = 'Datos inválidos para actualizar.';
            header('Location: /edunexo/admin/tipos_evaluacion');
            exit;
        }

        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT id FROM tipo_evaluacion WHERE nombre = ? AND id != ?");
        $stmt->execute([$nombre, $id]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Ya existe otro tipo de evaluación con ese nombre.';
            header('Location: /edunexo/admin/tipos_evaluacion');
            exit;
        }

        $stmt = $db->prepare("UPDATE tipo_evaluacion SET nombre = ?, peso_porcentaje = ? WHERE id = ?");
        if ($stmt->execute([$nombre, $peso, $id])) {
            $_SESSION['success'] = 'Tipo de evaluación actualizado correctamente.';
        } else {
            $_SESSION['error'] = 'Error al actualizar el tipo de evaluación.';
        }

        header('Location: /edunexo/admin/tipos_evaluacion');
        exit;
    }

    public function toggle() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /edunexo/admin/tipos_evaluacion');
            exit;
        }

        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            header('Location: /edunexo/admin/tipos_evaluacion');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);

        if ($id === 0) {
            $_SESSION['error'] = 'ID inválido.';
            header('Location: /edunexo/admin/tipos_evaluacion');
            exit;
        }

        $db = Database::getConnection();
        
        $stmt = $db->prepare("SELECT activo FROM tipo_evaluacion WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        if ($row) {
            $newStatus = $row['activo'] ? 0 : 1;
            $stmtUpdate = $db->prepare("UPDATE tipo_evaluacion SET activo = ? WHERE id = ?");
            if ($stmtUpdate->execute([$newStatus, $id])) {
                $_SESSION['success'] = 'Estado actualizado.';
            } else {
                $_SESSION['error'] = 'Error al actualizar estado.';
            }
        } else {
            $_SESSION['error'] = 'Registro no encontrado.';
        }

        header('Location: /edunexo/admin/tipos_evaluacion');
        exit;
    }
}
