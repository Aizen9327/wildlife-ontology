<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\OntologySession;

class ApiController extends Controller
{
    private function getModel(): mixed
    {
        if (!OntologySession::hasFile()) {
            $this->json(['error' => 'No file loaded'], 400);
            return null;
        }
        $model = OntologySession::getModel();
        if (!$model) {
            $this->json(['error' => 'Failed to parse OWL file'], 500);
            return null;
        }
        return $model;
    }

    public function classes(Request $request): void
    {
        $model = $this->getModel();
        if (!$model) return;
        $this->json($model->getAllClasses());
    }

    public function properties(Request $request): void
    {
        $model = $this->getModel();
        if (!$model) return;
        $this->json($model->getAllProperties());
    }

    public function hierarchy(Request $request, string $concept): void
    {
        $model = $this->getModel();
        if (!$model) return;
        $concept = urldecode($concept);
        $depth   = (int)($request->get('depth', 10));
        $this->json($model->getHierarchy($concept, $depth));
    }

    public function propertyHierarchy(Request $request, string $property): void
    {
        $model = $this->getModel();
        if (!$model) return;
        $property = urldecode($property);
        $depth    = (int)($request->get('depth', 5));
        $this->json($model->getPropertyHierarchy($property, $depth));
    }

    public function classProperties(Request $request, string $concept): void
    {
        $model = $this->getModel();
        if (!$model) return;
        $concept = urldecode($concept);
        $this->json($model->getClassProperties($concept));
    }

    public function combined(Request $request): void
    {
        $model = $this->getModel();
        if (!$model) return;

        $concept  = urldecode($request->get('concept', ''));
        $property = urldecode($request->get('property', ''));
        $depth    = (int)($request->get('depth', 3));

        if (!$concept) {
            $this->json(['error' => 'Missing concept parameter'], 400);
            return;
        }

        $this->json($model->getCombined($concept, $property, $depth));
    }

    public function fullGraph(Request $request): void
    {
        $model = $this->getModel();
        if (!$model) return;
        $this->json($model->getFullGraph());
    }

    public function instances(Request $request, string $concept): void
    {
        $model = $this->getModel();
        if (!$model) return;
        $concept = urldecode($concept);
        $this->json($model->getInstances($concept));
    }
}
