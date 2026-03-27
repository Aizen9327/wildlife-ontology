<?php
declare(strict_types=1);

namespace App\Models;

use EasyRdf\Graph;
use EasyRdf\RdfNamespace;

class OntologyModel
{
    private Graph $graph;
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->graph = new Graph();

        // Register common namespaces
        RdfNamespace::set('owl', 'http://www.w3.org/2002/07/owl#');
        RdfNamespace::set('rdfs', 'http://www.w3.org/2000/01/rdf-schema#');
        RdfNamespace::set('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
        RdfNamespace::set('xsd', 'http://www.w3.org/2001/XMLSchema#');

        $format = $this->detectFormat($filePath);
        $this->graph->parseFile($filePath, $format);
    }

    private function detectFormat(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match($ext) {
            'ttl'   => 'turtle',
            'n3'    => 'n3',
            'nt'    => 'ntriples',
            'jsonld', 'json' => 'jsonld',
            default => 'rdfxml',
        };
    }

    private function localName(string $uri): string
    {
        if (str_contains($uri, '#')) {
            return substr($uri, strrpos($uri, '#') + 1);
        }
        return basename($uri);
    }

    /**
     * Get all OWL classes
     */
    public function getAllClasses(): array
    {
        $classes = [];
        $owlClass  = $this->graph->allOfType('owl:Class');
        $rdfsClass = $this->graph->allOfType('rdfs:Class');
        $all = array_merge($owlClass, $rdfsClass);

        foreach ($all as $resource) {
            $uri = $resource->getUri();
            if (!$uri) continue;
            $label = $resource->get('rdfs:label')?->getValue() ?? $this->localName($uri);
            $classes[] = [
                'uri'   => $uri,
                'label' => $label,
                'local' => $this->localName($uri),
            ];
        }

        usort($classes, fn($a, $b) => strcmp($a['label'], $b['label']));
        return $classes;
    }

    /**
     * Get all OWL properties (object + datatype)
     */
    public function getAllProperties(): array
    {
        $props = [];
        $types = ['owl:ObjectProperty', 'owl:DatatypeProperty', 'owl:AnnotationProperty', 'rdf:Property'];

        foreach ($types as $type) {
            foreach ($this->graph->allOfType($type) as $resource) {
                $uri = $resource->getUri();
                if (!$uri) continue;
                $label = $resource->get('rdfs:label')?->getValue() ?? $this->localName($uri);
                $props[$uri] = [
                    'uri'   => $uri,
                    'label' => $label,
                    'local' => $this->localName($uri),
                    'type'  => $this->localName($type),
                ];
            }
        }

        $result = array_values($props);
        usort($result, fn($a, $b) => strcmp($a['label'], $b['label']));
        return $result;
    }

    /**
     * Get inheritance hierarchy from concept C (children + parents)
     */
    public function getHierarchy(string $conceptUri, int $depth = 5): array
    {
        $node = $this->buildHierarchyNode($conceptUri, $depth, 'down', []);
        return $node;
    }

    private function buildHierarchyNode(string $uri, int $depth, string $direction, array $visited): array
    {
        $resource = $this->graph->resource($uri);
        $label = $resource->get('rdfs:label')?->getValue() ?? $this->localName($uri);

        $node = [
            'uri'      => $uri,
            'label'    => $label,
            'local'    => $this->localName($uri),
            'children' => [],
        ];

        if ($depth <= 0 || in_array($uri, $visited)) {
            return $node;
        }
        $visited[] = $uri;

        // Find subclasses
        foreach ($this->graph->allOfType('owl:Class') as $class) {
            $classUri = $class->getUri();
            if (!$classUri || $classUri === $uri) continue;
            foreach ($class->all('rdfs:subClassOf') as $parent) {
                if ($parent->getUri() === $uri) {
                    $node['children'][] = $this->buildHierarchyNode($classUri, $depth - 1, $direction, $visited);
                }
            }
        }

        return $node;
    }

    /**
     * Get full inheritance tree (up and down) from concept
     */
    public function getFullHierarchy(string $conceptUri): array
    {
        $resource = $this->graph->resource($conceptUri);
        $label = $resource->get('rdfs:label')?->getValue() ?? $this->localName($conceptUri);

        // Get ancestors
        $ancestors = $this->getAncestors($conceptUri, []);

        // Get descendants
        $descendants = $this->getHierarchy($conceptUri, 10);

        return [
            'uri'         => $conceptUri,
            'label'       => $label,
            'local'       => $this->localName($conceptUri),
            'ancestors'   => $ancestors,
            'descendants' => $descendants,
        ];
    }

    private function getAncestors(string $uri, array $visited): array
    {
        if (in_array($uri, $visited)) return [];
        $visited[] = $uri;
        $resource = $this->graph->resource($uri);
        $ancestors = [];

        foreach ($resource->all('rdfs:subClassOf') as $parent) {
            $parentUri = $parent->getUri();
            if (!$parentUri) continue;
            $parentLabel = $parent->get('rdfs:label')?->getValue() ?? $this->localName($parentUri);
            $ancestors[] = [
                'uri'      => $parentUri,
                'label'    => $parentLabel,
                'local'    => $this->localName($parentUri),
                'parents'  => $this->getAncestors($parentUri, $visited),
            ];
        }
        return $ancestors;
    }

    /**
     * Get properties of a concept C (domain/range)
     */
    public function getClassProperties(string $conceptUri): array
    {
        $result = ['domain' => [], 'range' => []];
        $types = ['owl:ObjectProperty', 'owl:DatatypeProperty', 'rdf:Property'];

        foreach ($types as $type) {
            foreach ($this->graph->allOfType($type) as $prop) {
                $propUri = $prop->getUri();
                if (!$propUri) continue;
                $label = $prop->get('rdfs:label')?->getValue() ?? $this->localName($propUri);
                $propData = [
                    'uri'   => $propUri,
                    'label' => $label,
                    'local' => $this->localName($propUri),
                    'type'  => $this->localName($type),
                ];

                foreach ($prop->all('rdfs:domain') as $domain) {
                    if ($domain->getUri() === $conceptUri) {
                        $range = $prop->get('rdfs:range');
                        $propData['range'] = $range ? [
                            'uri'   => $range->getUri(),
                            'label' => $range->get('rdfs:label')?->getValue() ?? $this->localName($range->getUri() ?? ''),
                        ] : null;
                        $result['domain'][] = $propData;
                    }
                }

                foreach ($prop->all('rdfs:range') as $range) {
                    if ($range->getUri() === $conceptUri) {
                        $domain = $prop->get('rdfs:domain');
                        $propData['domain'] = $domain ? [
                            'uri'   => $domain->getUri(),
                            'label' => $domain->get('rdfs:label')?->getValue() ?? $this->localName($domain->getUri() ?? ''),
                        ] : null;
                        $result['range'][] = $propData;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get property hierarchy (sub/super properties)
     */
    public function getPropertyHierarchy(string $propertyUri, int $depth = 5): array
    {
        return $this->buildPropertyNode($propertyUri, $depth, []);
    }

    private function buildPropertyNode(string $uri, int $depth, array $visited): array
    {
        $resource = $this->graph->resource($uri);
        $label = $resource->get('rdfs:label')?->getValue() ?? $this->localName($uri);

        $node = [
            'uri'        => $uri,
            'label'      => $label,
            'local'      => $this->localName($uri),
            'domain'     => null,
            'range'      => null,
            'children'   => [],
            'superProps' => [],
        ];

        // Domain/Range
        $domain = $resource->get('rdfs:domain');
        $range  = $resource->get('rdfs:range');
        if ($domain) $node['domain'] = ['uri' => $domain->getUri(), 'label' => $this->localName($domain->getUri() ?? '')];
        if ($range)  $node['range']  = ['uri' => $range->getUri(),  'label' => $this->localName($range->getUri() ?? '')];

        // Super-properties
        foreach ($resource->all('rdfs:subPropertyOf') as $super) {
            $superUri = $super->getUri();
            if ($superUri) {
                $node['superProps'][] = ['uri' => $superUri, 'label' => $this->localName($superUri)];
            }
        }

        if ($depth <= 0 || in_array($uri, $visited)) return $node;
        $visited[] = $uri;

        // Sub-properties
        $types = ['owl:ObjectProperty', 'owl:DatatypeProperty', 'rdf:Property'];
        foreach ($types as $type) {
            foreach ($this->graph->allOfType($type) as $prop) {
                $propUri = $prop->getUri();
                if (!$propUri || $propUri === $uri) continue;
                foreach ($prop->all('rdfs:subPropertyOf') as $super) {
                    if ($super->getUri() === $uri) {
                        $node['children'][] = $this->buildPropertyNode($propUri, $depth - 1, $visited);
                    }
                }
            }
        }

        return $node;
    }

    /**
     * Get property chain: follow a property P up to p links
     */
    public function getPropertyChain(string $conceptUri, string $propertyUri, int $depth = 3): array
    {
        return $this->followProperty($conceptUri, $propertyUri, $depth, []);
    }

    private function followProperty(string $classUri, string $propertyUri, int $depth, array $visited): array
    {
        $resource = $this->graph->resource($classUri);
        $label = $resource->get('rdfs:label')?->getValue() ?? $this->localName($classUri);

        $node = [
            'uri'      => $classUri,
            'label'    => $label,
            'local'    => $this->localName($classUri),
            'children' => [],
        ];

        if ($depth <= 0 || in_array($classUri, $visited)) return $node;
        $visited[] = $classUri;

        // Find classes that are the range of this property when domain is classUri
        $prop = $this->graph->resource($propertyUri);
        foreach ($this->graph->allOfType('owl:Class') as $class) {
            $cUri = $class->getUri();
            if (!$cUri) continue;
            // Check if property links classUri -> cUri
            foreach ($prop->all('rdfs:domain') as $domain) {
                if ($domain->getUri() === $classUri) {
                    foreach ($prop->all('rdfs:range') as $range) {
                        if ($range->getUri() === $cUri) {
                            $node['children'][] = $this->followProperty($cUri, $propertyUri, $depth - 1, $visited);
                        }
                    }
                }
            }
        }

        return $node;
    }

    /**
     * Get combined: hierarchy of C + its properties + property chain
     */
    public function getCombined(string $conceptUri, string $propertyUri = '', int $chainDepth = 3): array
    {
        $hierarchy   = $this->getHierarchy($conceptUri, 5);
        $properties  = $this->getClassProperties($conceptUri);
        $chain       = $propertyUri ? $this->getPropertyChain($conceptUri, $propertyUri, $chainDepth) : null;

        return [
            'concept'    => $conceptUri,
            'label'      => $hierarchy['label'] ?? $this->localName($conceptUri),
            'hierarchy'  => $hierarchy,
            'properties' => $properties,
            'chain'      => $chain,
        ];
    }

    /**
     * Get full graph for global visualization
     */
    public function getFullGraph(): array
    {
        $nodes = [];
        $links = [];
        $nodeIndex = [];

        $classes = $this->getAllClasses();
        foreach ($classes as $class) {
            $nodeIndex[$class['uri']] = count($nodes);
            $nodes[] = ['id' => $class['uri'], 'label' => $class['label'], 'type' => 'class'];
        }

        // SubClass links
        foreach ($this->graph->allOfType('owl:Class') as $resource) {
            $uri = $resource->getUri();
            if (!$uri || !isset($nodeIndex[$uri])) continue;
            foreach ($resource->all('rdfs:subClassOf') as $parent) {
                $pUri = $parent->getUri();
                if ($pUri && isset($nodeIndex[$pUri])) {
                    $links[] = ['source' => $uri, 'target' => $pUri, 'type' => 'subClassOf'];
                }
            }
        }

        // Property links
        $types = ['owl:ObjectProperty', 'owl:DatatypeProperty', 'rdf:Property'];
        foreach ($types as $type) {
            foreach ($this->graph->allOfType($type) as $prop) {
                $propUri = $prop->getUri();
                if (!$propUri) continue;
                $label = $prop->get('rdfs:label')?->getValue() ?? $this->localName($propUri);
                $domain = $prop->get('rdfs:domain');
                $range  = $prop->get('rdfs:range');

                if ($domain && $range) {
                    $dUri = $domain->getUri();
                    $rUri = $range->getUri();
                    if ($dUri && $rUri && isset($nodeIndex[$dUri], $nodeIndex[$rUri])) {
                        $links[] = ['source' => $dUri, 'target' => $rUri, 'type' => 'property', 'label' => $label, 'uri' => $propUri];
                    }
                }
            }
        }

        return ['nodes' => $nodes, 'links' => $links];
    }

    /**
     * Get instances of a class
     */
    public function getInstances(string $conceptUri): array
    {
        $instances = [];
        foreach ($this->graph->allOfType($conceptUri) as $instance) {
            $uri = $instance->getUri();
            if (!$uri) continue;
            $label = $instance->get('rdfs:label')?->getValue() ?? $this->localName($uri);
            $props = [];
            foreach ($instance->properties() as $propUri => $values) {
                $propLabel = $this->localName($propUri);
                $vals = [];
                foreach ($values as $v) {
                    $vals[] = method_exists($v, 'getValue') ? $v->getValue() : (string)$v;
                }
                $props[$propLabel] = $vals;
            }
            $instances[] = ['uri' => $uri, 'label' => $label, 'properties' => $props];
        }
        return $instances;
    }
}
