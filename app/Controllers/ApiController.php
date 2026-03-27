<?php
class ApiController
{
    private ?OntologyModel $model = null;

    public function __construct()
    {
        if (empty($_SESSION['owl_content'])) return;
        try {
            $this->model = new OntologyModel(
                base64_decode($_SESSION['owl_content']),
                $_SESSION['owl_ext'] ?? 'owl'
            );
        } catch (\Throwable $e) {
            $this->model = null;
        }
    }

    private function json(mixed $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    private function requireModel(): void
    {
        if (!$this->model) {
            $this->json(['error' => 'no_file']);
        }
    }

    public function index(): void
    {
        $this->json(['status' => 'ok', 'file' => $_SESSION['owl_name'] ?? null]);
    }

    public function fullData(): void
    {
        $this->requireModel();
        $this->json([
            'file'       => $_SESSION['owl_name'] ?? '',
            'classes'    => $this->model->getClasses(),
            'relations'  => $this->model->getSubclassRelations(),
            'properties' => $this->model->getProperties(),
            'tree'       => $this->model->buildFullTree(),
        ]);
    }

    public function hierarchy(): void
    {
        $this->requireModel();
        $concept = $_GET['concept'] ?? null;
        $depth   = (int)($_GET['depth'] ?? 10);
        $this->json($concept
            ? $this->model->buildHierarchy($concept, $depth)
            : $this->model->buildFullTree()
        );
    }

    public function combined(): void
    {
        $this->requireModel();
        $concept = $_GET['concept'] ?? null;
        $depth   = (int)($_GET['depth'] ?? 5);
        $propUri = $_GET['property'] ?? null;
        $tree    = $concept
            ? $this->model->buildHierarchy($concept, $depth)
            : $this->model->buildFullTree();
        $this->json([
            'hierarchy'     => $tree,
            'properties'    => $concept ? $this->model->getPropertiesForConcept($concept) : [],
            'propertyChain' => $propUri  ? $this->model->getPropertyHierarchy($propUri)   : null,
        ]);
    }

    public function conceptDetails(): void
    {
        $this->requireModel();
        $this->json($this->model->getConceptDetails($_GET['concept'] ?? ''));
    }

    public function reset(): void
    {
        unset($_SESSION['owl_content'], $_SESSION['owl_ext'], $_SESSION['owl_name']);
        $this->json(['success' => true]);
    }
}
