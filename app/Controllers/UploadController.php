<?php
class UploadController
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['owl_file'])) {
            http_response_code(405);
            echo json_encode(['error' => 'POST + fichier requis']);
            exit;
        }

        $file = $_FILES['owl_file'];
        $name = basename($file['name']);
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (!in_array($ext, ['owl', 'rdf', 'xml', 'ttl', 'nt', 'json'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Format non supporté — .owl .rdf .xml .ttl .nt .json']);
            exit;
        }

        $content = file_get_contents($file['tmp_name']);
        if (!$content || strlen($content) < 10) {
            http_response_code(400);
            echo json_encode(['error' => 'Fichier vide ou illisible']);
            exit;
        }

        $_SESSION['owl_content'] = base64_encode($content);
        $_SESSION['owl_ext']     = $ext;
        $_SESSION['owl_name']    = $name;

        echo json_encode(['success' => true, 'file' => $name, 'size' => strlen($content)]);
        exit;
    }
}
