<?php /** @var string $title */ ?>
<div class="wiki-layout">
    <nav class="wiki-nav">
        <div class="wiki-nav-title">Documentation</div>
        <a href="/wiki/technique" class="wiki-nav-link">📐 Manuel Technique</a>
        <a href="/wiki/realisation" class="wiki-nav-link">🔨 Manuel de Réalisation</a>
        <a href="/wiki/api" class="wiki-nav-link active">⚡ Documentation API</a>
        <a href="/wiki/visualisations" class="wiki-nav-link">🎨 Guide des Visualisations</a>
        <div class="wiki-nav-brand"><strong>Novyra Graphis</strong>par Novyra</div>
    </nav>
    <div class="wiki-content">
        <h1>Documentation API</h1>
        <p class="wiki-subtitle">Endpoints REST JSON consommés par D3.js</p>
        <p>Tous les endpoints retournent du JSON avec <code>Content-Type: application/json</code>. Si aucun fichier OWL n'est chargé, la réponse est <code>{"error": "No file loaded"}</code> avec HTTP 400.</p>

        <h2>GET /api/classes</h2>
        <p>Liste toutes les classes OWL/RDFS de l'ontologie.</p>
        <pre><code>[
  { "uri": "http://...", "label": "Person", "local": "Person" },
  ...
]</code></pre>

        <h2>GET /api/properties</h2>
        <p>Liste toutes les propriétés (ObjectProperty, DatatypeProperty, AnnotationProperty).</p>
        <pre><code>[
  { "uri": "http://...", "label": "hasName", "local": "hasName", "type": "DatatypeProperty" },
  ...
]</code></pre>

        <h2>GET /api/hierarchy/{concept}?depth=N</h2>
        <p>Arbre de descendants (sous-classes) d'un concept C sur N niveaux.</p>
        <pre><code>{
  "uri": "http://...",
  "label": "Animal",
  "local": "Animal",
  "children": [
    { "uri": "...", "label": "Dog", "children": [] },
    { "uri": "...", "label": "Cat", "children": [] }
  ]
}</code></pre>

        <h2>GET /api/property-hierarchy/{property}?depth=N</h2>
        <p>Hiérarchie d'une propriété P (sous-propriétés + super-propriétés).</p>
        <pre><code>{
  "uri": "...", "label": "knows",
  "domain": { "uri": "...", "label": "Person" },
  "range":  { "uri": "...", "label": "Person" },
  "superProps": [...],
  "children": [...]
}</code></pre>

        <h2>GET /api/class-properties/{concept}</h2>
        <p>Propriétés dont le concept est dans le domaine ou le codomaine.</p>
        <pre><code>{
  "domain": [
    { "uri": "...", "label": "hasAge", "type": "DatatypeProperty",
      "range": { "uri": "xsd:integer", "label": "integer" } }
  ],
  "range": [...]
}</code></pre>

        <h2>GET /api/combined?concept=URI&property=URI&depth=N</h2>
        <p>Combine : hiérarchie du concept C + ses propriétés + chaîne de la propriété P sur N liens.</p>
        <pre><code>{
  "concept": "...", "label": "Person",
  "hierarchy": { ... },
  "properties": { "domain": [...], "range": [...] },
  "chain": { "uri": "...", "children": [...] }
}</code></pre>

        <h2>GET /api/graph</h2>
        <p>Graphe complet : tous les nœuds (classes) et arêtes (subClassOf + propriétés).</p>
        <pre><code>{
  "nodes": [
    { "id": "http://...", "label": "Person", "type": "class" }
  ],
  "links": [
    { "source": "...", "target": "...", "type": "subClassOf" },
    { "source": "...", "target": "...", "type": "property", "label": "knows" }
  ]
}</code></pre>

        <h2>GET /api/instances/{concept}</h2>
        <p>Instances d'un concept dans l'ontologie (individus).</p>
        <pre><code>[
  {
    "uri": "...", "label": "John",
    "properties": { "hasAge": ["30"], "hasName": ["John"] }
  }
]</code></pre>

        <h2>POST /upload</h2>
        <p>Upload d'un fichier OWL/RDF. Paramètre multipart : <code>owl_file</code>. Redirige vers <code>/</code> avec message de succès ou d'erreur en session.</p>
    </div>
</div>
