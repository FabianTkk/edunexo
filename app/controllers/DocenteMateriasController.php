<?php
namespace App\Controllers;

use App\Config\Database;

class DocenteMateriasController {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'docente') {
            header('Location: /edunexo/login');
            exit;
        }
    }

    public function index() {
        $db = Database::getConnection();
        
        $docente_id = (int)$_SESSION['user_id'];

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

        require __DIR__ . '/../views/docente/materias.php';
    }
}
