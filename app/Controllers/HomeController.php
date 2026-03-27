<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\OntologySession;
use App\Models\OntologyModel;

class HomeController extends Controller
{
    public function index(Request $request): void
    {
        $hasFile = OntologySession::hasFile();
        $classes = [];
        $properties = [];
        $fileName = null;

        if ($hasFile) {
            $model = OntologySession::getModel();
            if ($model) {
                $classes    = $model->getAllClasses();
                $properties = $model->getAllProperties();
                $fileName   = basename(OntologySession::getFilePath() ?? '');
            }
        }

        $this->render('home', compact('hasFile', 'classes', 'properties', 'fileName'));
    }

    public function upload(Request $request): void
    {
        $file = $request->file('owl_file');

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Erreur lors du chargement du fichier.';
            $this->redirect('/');
            return;
        }

        $allowed = ['owl', 'rdf', 'xml', 'ttl', 'n3', 'nt', 'jsonld', 'json'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $_SESSION['error'] = "Extension non supportée: .{$ext}";
            $this->redirect('/');
            return;
        }

        $maxSize = 50 * 1024 * 1024; // 50 MB
        if ($file['size'] > $maxSize) {
            $_SESSION['error'] = 'Fichier trop grand (max 50 MB).';
            $this->redirect('/');
            return;
        }

        $uploadDir = STORAGE_PATH . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Clean old files
        $this->cleanOldFiles($uploadDir);

        $fileName = uniqid('owl_', true) . '.' . $ext;
        $dest     = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $_SESSION['error'] = 'Impossible de sauvegarder le fichier.';
            $this->redirect('/');
            return;
        }

        // Validate parseable
        try {
            new OntologyModel($dest);
        } catch (\Throwable $e) {
            unlink($dest);
            $_SESSION['error'] = 'Fichier OWL/RDF invalide : ' . htmlspecialchars($e->getMessage());
            $this->redirect('/');
            return;
        }

        OntologySession::setFilePath($dest);
        $_SESSION['success'] = "Fichier '{$file['name']}' chargé avec succès !";
        $this->redirect('/');
    }

    private function cleanOldFiles(string $dir): void
    {
        $files = glob($dir . 'owl_*');
        if (!$files) return;
        foreach ($files as $f) {
            if (filemtime($f) < time() - 3600) {
                @unlink($f);
            }
        }
    }
}
