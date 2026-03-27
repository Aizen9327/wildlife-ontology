<?php
class AppController
{
    public function index(): void
    {
        if (empty($_SESSION['owl_content'])) {
            header('Location: index.php?route=landing');
            exit;
        }
        require_once APP_PATH . '/Views/layouts/app.php';
    }
}
