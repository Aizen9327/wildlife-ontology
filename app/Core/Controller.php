<?php
declare(strict_types=1);

namespace App\Core;

class Controller
{
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data);
        $viewPath = APP_PATH . "/Views/pages/{$view}.php";
        $layoutPath = APP_PATH . "/Views/layouts/{$layout}.php";

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        require $layoutPath;
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}
