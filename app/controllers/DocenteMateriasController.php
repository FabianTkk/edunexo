<?php
namespace App\Controllers;

use App\Config\Database;

class DocenteMateriasController {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol'] ?? '', ['docente', 'admin'])) {
            header('Location: /edunexo/login');
            exit;
        }
    }

    public function index() {
        $db = Database::getConnection();
        $docente_id = (int)$_SESSION['user_id'];

        if (($_SESSION['rol'] ?? '') === 'admin') {
            // El administrador puede visualizar todas las asignaciones para pruebas o auditoría
            $stmt = $db->query("
                SELECT cmd.id, c.nombre AS curso, c.turno, m.nombre AS materia, u.nombre AS docente_nombre 
                FROM curso_materia_docente cmd 
                JOIN cursos c ON c.id = cmd.curso_id 
                JOIN materias m ON m.id = cmd.materia_id 
                JOIN usuarios u ON u.id = cmd.docente_id
                WHERE cmd.activo = 1 
                ORDER BY c.nombre, m.nombre
            ");
            $asignaciones = $stmt->fetchAll();
        } else {
            $stmt = $db->prepare("
                SELECT cmd.id, c.nombre AS curso, c.turno, m.nombre AS materia 
                FROM curso_materia_docente cmd 
                JOIN cursos c ON c.id = cmd.curso_id 
                JOIN materias m ON m.id = cmd.materia_id 
                WHERE cmd.docente_id = ? AND cmd.activo = 1 
                ORDER BY c.nombre, m.nombre
            ");
            $stmt->execute([$docente_id]);
            $asignaciones = $stmt->fetchAll();
        }

        require __DIR__ . '/../views/docente/materias.php';
    }
}
