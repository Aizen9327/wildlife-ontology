<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;

class WikiController extends Controller
{
    private array $pages = [
        'technique'    => 'Manuel Technique',
        'realisation'  => 'Manuel de Réalisation',
        'api'          => 'Documentation API',
        'visualisations' => 'Guide des Visualisations',
    ];

    public function index(Request $request): void
    {
        $this->render('wiki/index', ['pages' => $this->pages], 'wiki');
    }

    public function show(Request $request, string $page): void
    {
        if (!array_key_exists($page, $this->pages)) {
            http_response_code(404);
            echo "Page wiki introuvable";
            return;
        }
        $title = $this->pages[$page];
        $this->render("wiki/{$page}", ['title' => $title, 'pages' => $this->pages], 'wiki');
    }
}
