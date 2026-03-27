<?php
class OntologyModel
{
    private \EasyRdf\Graph $graph;

    public function __construct(string $content, string $ext = 'owl')
    {
        require_once ROOT_PATH . '/vendor/autoload.php';
        $this->graph = new \EasyRdf\Graph();
        $this->graph->parse($content, $this->detectFormat($content, $ext));
    }

    private function detectFormat(string $content, string $ext): string
    {
        $t = ltrim($content);
        if ($t[0] === '[' || $t[0] === '{') return 'jsonld';
        if (str_contains($content, '@prefix') || str_contains($content, '@base')) return 'turtle';
        if ($ext === 'nt') return 'ntriples';
        return 'rdfxml';
    }

    public function getClasses(): array
    {
        $classes = [];
        $seen    = [];
        foreach ($this->graph->allOfType('owl:Class') as $class) {
            $uri = $class->getUri();
            if (!$uri || isset($seen[$uri]) || str_contains($uri, 'owl#Thing')) continue;
            $seen[$uri] = true;
            $label      = $class->label() ? (string)$class->label() : $this->local($uri);
            $comment    = '';
            foreach ($class->all('rdfs:comment') as $c) { $comment = (string)$c; break; }
            $classes[]  = ['id' => $uri, 'label' => $label, 'comment' => $comment];
        }
        usort($classes, fn($a, $b) => strcmp($a['label'], $b['label']));
        return $classes;
    }

    public function getSubclassRelations(): array
    {
        $out  = [];
        $seen = [];
        foreach ($this->graph->allOfType('owl:Class') as $class) {
            $child = $class->getUri();
            if (!$child) continue;
            foreach ($class->allResources('rdfs:subClassOf') as $parent) {
                $par = $parent->getUri();
                if (!$par || str_contains($par, 'owl#Thing')) continue;
                $key = "$par|$child";
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $out[]      = ['parent' => $par, 'child' => $child];
            }
        }
        return $out;
    }

    public function getProperties(): array
    {
        $props = [];
        foreach (['owl:ObjectProperty', 'owl:DatatypeProperty'] as $type) {
            foreach ($this->graph->allOfType($type) as $prop) {
                $uri = $prop->getUri();
                if (!$uri) continue;
                $domain  = $prop->get('rdfs:domain');
                $range   = $prop->get('rdfs:range');
                $inverse = $prop->get('owl:inverseOf');
                $props[] = [
                    'id'        => $uri,
                    'label'     => $prop->label() ? (string)$prop->label() : $this->local($uri),
                    'type'      => $type === 'owl:ObjectProperty' ? 'object' : 'datatype',
                    'domain'    => $domain  ? $domain->getUri()  : null,
                    'range'     => $range   ? $range->getUri()   : null,
                    'inverseOf' => $inverse ? $inverse->getUri() : null,
                ];
            }
        }
        return $props;
    }

    public function buildFullTree(): array
    {
        $relations = $this->getSubclassRelations();
        $classes   = $this->getClasses();
        $classMap  = array_column($classes, null, 'id');
        $childUris = array_column($relations, 'child');
        $subMap    = [];
        foreach ($relations as $r) $subMap[$r['parent']][] = $r['child'];

        $roots = [];
        foreach ($classes as $c) {
            if (!in_array($c['id'], $childUris)) $roots[] = $c['id'];
        }
        if (empty($roots) && !empty($classes)) $roots = [$classes[0]['id']];
        if (count($roots) === 1) return $this->buildNode($roots[0], $subMap, $classMap, 0, 10);

        return [
            'id'       => 'root',
            'name'     => 'Ontologie',
            'comment'  => '',
            'children' => array_map(fn($r) => $this->buildNode($r, $subMap, $classMap, 0, 10), $roots),
        ];
    }

    public function buildHierarchy(string $rootUri, int $depth = 10): array
    {
        $subMap   = [];
        $classMap = [];
        foreach ($this->getSubclassRelations() as $r) $subMap[$r['parent']][] = $r['child'];
        foreach ($this->getClasses() as $c) $classMap[$c['id']] = $c;
        return $this->buildNode($rootUri, $subMap, $classMap, 0, $depth);
    }

    private function buildNode(string $uri, array &$sub, array &$cm, int $d, int $max): array
    {
        $info = $cm[$uri] ?? ['id' => $uri, 'label' => $this->local($uri), 'comment' => ''];
        $node = ['id' => $uri, 'name' => $info['label'], 'comment' => $info['comment']];
        if ($d < $max && isset($sub[$uri])) {
            $children = [];
            foreach (array_unique($sub[$uri]) as $c) {
                $children[] = $this->buildNode($c, $sub, $cm, $d + 1, $max);
            }
            if ($children) $node['children'] = $children;
        }
        return $node;
    }

    public function getPropertiesForConcept(string $uri): array
    {
        return array_values(array_filter($this->getProperties(), fn($p) => $p['domain'] === $uri));
    }

    public function getPropertyHierarchy(string $propUri): array
    {
        foreach ($this->getProperties() as $p) {
            if ($p['id'] === $propUri) return [
                'id'     => $p['id'],
                'name'   => $p['label'],
                'type'   => $p['type'],
                'domain' => $p['domain'] ? ['id' => $p['domain'], 'name' => $this->local($p['domain'])] : null,
                'range'  => $p['range']  ? ['id' => $p['range'],  'name' => $this->local($p['range'])]  : null,
            ];
        }
        return [];
    }

    public function getConceptDetails(string $uri): array
    {
        $resource     = $this->graph->resource($uri);
        $subclasses   = [];
        $disjoint     = [];
        $restrictions = [];

        foreach ($this->getSubclassRelations() as $r) {
            if ($r['parent'] === $uri) $subclasses[] = ['id' => $r['child'], 'label' => $this->local($r['child'])];
        }

        if ($resource) {
            foreach ($resource->allResources('owl:disjointWith') as $d) {
                $u = $d->getUri();
                if ($u) $disjoint[] = ['id' => $u, 'label' => $this->local($u)];
            }
            foreach ($resource->allResources('rdfs:subClassOf') as $parent) {
                if (!$parent->getUri()) {
                    $onProp = $parent->get('owl:onProperty');
                    $some   = $parent->get('owl:someValuesFrom');
                    $all    = $parent->get('owl:allValuesFrom');
                    if ($onProp) {
                        $r = ['property' => $this->local($onProp->getUri() ?? '')];
                        if ($some) $r['some'] = $this->local($some->getUri() ?? '');
                        if ($all)  $r['all']  = $this->local($all->getUri() ?? '');
                        $restrictions[] = $r;
                    }
                }
            }
        }

        return [
            'properties'   => $this->getPropertiesForConcept($uri),
            'subclasses'   => $subclasses,
            'disjoint'     => $disjoint,
            'restrictions' => $restrictions,
        ];
    }

    private function local(string $uri): string
    {
        $h = strrpos($uri, '#');
        if ($h !== false) return substr($uri, $h + 1);
        $s = strrpos($uri, '/');
        if ($s !== false) return substr($uri, $s + 1);
        return $uri;
    }
}
