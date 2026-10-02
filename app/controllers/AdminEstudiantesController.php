<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\SecurityHelper;

class AdminEstudiantesController {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
            header('Location: /edunexo/login');
            exit;
        }
    }

    public function index() {
        $db = Database::getConnection();

        $estudiantes = $db->query("
            SELECT e.*, c.nombre AS curso_nombre,
                   (SELECT GROUP_CONCAT(t.nombre_completo ORDER BY te.id SEPARATOR ', ')
                    FROM tutores_estudiantes te JOIN tutores t ON t.id = te.tutor_id
                    WHERE te.estudiante_id = e.id) AS tutores_nombres,
                   (SELECT GROUP_CONCAT(te.tutor_id ORDER BY te.id)
                    FROM tutores_estudiantes te WHERE te.estudiante_id = e.id) AS tutor_ids
            FROM estudiantes e
            LEFT JOIN cursos c ON c.id = e.curso_id
            ORDER BY e.nombre_completo
        ")->fetchAll();

        $cursos  = $db->query("SELECT * FROM cursos WHERE activo=1 ORDER BY nombre")->fetchAll();
        $tutores = $db->query("SELECT * FROM tutores WHERE activo=1 ORDER BY nombre_completo")->fetchAll();
        $csrfToken = SecurityHelper::generateCsrfToken();

        require __DIR__ . '/../views/admin/estudiantes.php';
    }

    public function store() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF invalido.';
            header('Location: /edunexo/admin/estudiantes'); exit;
        }

        $ci       = SecurityHelper::sanitize($_POST['ci']             ?? '');
        $nombre   = SecurityHelper::sanitize($_POST['nombre_completo'] ?? '');
        $curso_id = (int)($_POST['curso_id'] ?? 0);

        if (empty($ci) || empty($nombre) || $curso_id === 0) {
            $_SESSION['error'] = 'CI, nombre y curso son obligatorios.';
            header('Location: /edunexo/admin/estudiantes'); exit;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM estudiantes WHERE ci=?");
        $stmt->execute([$ci]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Ya existe un estudiante con ese CI.';
            header('Location: /edunexo/admin/estudiantes'); exit;
        }

        $stmt = $db->prepare("INSERT INTO estudiantes (ci, nombre_completo, curso_id, curso) VALUES (?,?,?,?)");
        $cursoNombre = $db->prepare("SELECT nombre FROM cursos WHERE id=?");
        $cursoNombre->execute([$curso_id]);
        $cn = $cursoNombre->fetchColumn() ?: '';
        $stmt->execute([$ci, $nombre, $curso_id, $cn]);
        $newId = $db->lastInsertId();

        $tutorIds = $this->tutoresSeleccionados($db);
        foreach ($tutorIds as $tutorId) {
            $db->prepare("INSERT IGNORE INTO tutores_estudiantes (tutor_id, estudiante_id) VALUES (?,?)")
               ->execute([$tutorId, $newId]);
        }

        $_SESSION['success'] = 'Estudiante creado correctamente.';
        header('Location: /edunexo/admin/estudiantes'); exit;
    }

    public function update() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF invalido.';
            header('Location: /edunexo/admin/estudiantes'); exit;
        }

        $id       = (int)($_POST['id']             ?? 0);
        $ci       = SecurityHelper::sanitize($_POST['ci']             ?? '');
        $nombre   = SecurityHelper::sanitize($_POST['nombre_completo'] ?? '');
        $curso_id = (int)($_POST['curso_id'] ?? 0);

        if ($id === 0 || empty($ci) || empty($nombre) || $curso_id === 0) {
            $_SESSION['error'] = 'Datos invalidos.';
            header('Location: /edunexo/admin/estudiantes'); exit;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM estudiantes WHERE ci=? AND id!=?");
        $stmt->execute([$ci, $id]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Ese CI ya pertenece a otro estudiante.';
            header('Location: /edunexo/admin/estudiantes'); exit;
        }

        $cursoNombre = $db->prepare("SELECT nombre FROM cursos WHERE id=?");
        $cursoNombre->execute([$curso_id]);
        $cn = $cursoNombre->fetchColumn() ?: '';

        $tutorIds = $this->tutoresSeleccionados($db);

        $db->beginTransaction();
        try {
            $db->prepare("UPDATE estudiantes SET ci=?, nombre_completo=?, curso_id=?, curso=? WHERE id=?")
               ->execute([$ci, $nombre, $curso_id, $cn, $id]);

            // Se sincronizan los tutores marcados en el formulario: solo se quitan los vinculos con tutores
            // ACTIVOS que se desmarcaron. Los vinculos con tutores inactivos no aparecen en el formulario
            // y se conservan tal cual. Los que ya estaban marcados no se tocan.
            if ($tutorIds) {
                $ph = implode(',', array_fill(0, count($tutorIds), '?'));
                $db->prepare("DELETE te FROM tutores_estudiantes te JOIN tutores t ON t.id = te.tutor_id
                              WHERE te.estudiante_id = ? AND t.activo = 1 AND te.tutor_id NOT IN ($ph)")
                   ->execute(array_merge([$id], $tutorIds));
            } else {
                $db->prepare("DELETE te FROM tutores_estudiantes te JOIN tutores t ON t.id = te.tutor_id
                              WHERE te.estudiante_id = ? AND t.activo = 1")
                   ->execute([$id]);
            }

            $ins = $db->prepare("INSERT IGNORE INTO tutores_estudiantes (tutor_id, estudiante_id) VALUES (?,?)");
            foreach ($tutorIds as $tutorId) {
                $ins->execute([$tutorId, $id]);
            }

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('[AdminEstudiantesController] Error al actualizar estudiante: ' . $e->getMessage());
            $_SESSION['error'] = 'No se pudo actualizar el estudiante. Intenta de nuevo.';
            header('Location: /edunexo/admin/estudiantes'); exit;
        }

        $_SESSION['success'] = 'Estudiante actualizado correctamente.';
        header('Location: /edunexo/admin/estudiantes'); exit;
    }

    /**
     * Tutores marcados en el formulario (tutor_ids[]): solo ids enteros, sin repetir,
     * que existan y esten activos.
     */
    private function tutoresSeleccionados(\PDO $db): array {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', (array)($_POST['tutor_ids'] ?? [])),
            fn($v) => $v > 0
        )));
        if (!$ids) return [];

        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("SELECT id FROM tutores WHERE activo = 1 AND id IN ($ph)");
        $stmt->execute($ids);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function toggle() {
        if (!SecurityHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF invalido.';
            header('Location: /edunexo/admin/estudiantes'); exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id === 0) { header('Location: /edunexo/admin/estudiantes'); exit; }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT estado FROM estudiantes WHERE id=?");
        $stmt->execute([$id]);
        $e = $stmt->fetch();
        if ($e) {
            $nuevo = $e['estado'] === 'activo' ? 'inactivo' : 'activo';
            $db->prepare("UPDATE estudiantes SET estado=? WHERE id=?")->execute([$nuevo, $id]);
            $_SESSION['success'] = 'Estado del estudiante actualizado.';
        }
        header('Location: /edunexo/admin/estudiantes'); exit;
    }
}
