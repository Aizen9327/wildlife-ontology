<?php /** @var string $title */ ?>
<div class="wiki-layout">
    <nav class="wiki-nav">
        <div class="wiki-nav-title">Documentation</div>
        <a href="/wiki/technique" class="wiki-nav-link">📐 Manuel Technique</a>
        <a href="/wiki/realisation" class="wiki-nav-link active">🔨 Manuel de Réalisation</a>
        <a href="/wiki/api" class="wiki-nav-link">⚡ Documentation API</a>
        <a href="/wiki/visualisations" class="wiki-nav-link">🎨 Guide des Visualisations</a>
        <div class="wiki-nav-brand"><strong>Novyra Graphis</strong>par Novyra</div>
    </nav>
    <div class="wiki-content">
        <h1>Manuel de Réalisation</h1>
        <p class="wiki-subtitle">Guide de développement et choix de conception</p>

        <h2>1. Mise en place du projet</h2>
        <h3>Prérequis</h3>
        <ul>
            <li>PHP 8.2 + Composer</li>
            <li>Docker (pour Railway) ou Apache avec mod_rewrite</li>
            <li>Git</li>
        </ul>
        <h3>Installation locale</h3>
        <pre><code>git clone &lt;votre-repo&gt; novyra-graphis
cd novyra-graphis
composer install
mkdir -p storage/uploads
chmod 777 storage/uploads
php -S localhost:8080 -t public/</code></pre>

        <h2>2. Architecture MVC — Choix de conception</h2>
        <p>Le MVC a été implémenté sans framework (Laravel, Symfony...) pour respecter la contrainte <em>"PHP 8, en MVC"</em> tout en gardant le contrôle total sur la structure.</p>
        <ul>
            <li><strong>Router</strong> : basé sur des regex, supporte les paramètres dynamiques <code>{param}</code></li>
            <li><strong>Controller</strong> : classe de base avec <code>render()</code>, <code>json()</code>, <code>redirect()</code></li>
            <li><strong>Model</strong> : <code>OntologyModel</code> encapsule toute la logique EasyRdf</li>
            <li><strong>View</strong> : templates PHP purs avec layout système (<code>ob_start/ob_get_clean</code>)</li>
        </ul>

        <h2>3. Parsing OWL avec EasyRdf</h2>
        <p>EasyRdf parse le fichier RDF/OWL en mémoire dans un objet <code>Graph</code>. Les méthodes principales utilisées :</p>
        <pre><code>$graph = new \EasyRdf\Graph();
$graph->parseFile($path, 'rdfxml');

// Lister toutes les classes OWL
$graph->allOfType('owl:Class');

// Parcourir les propriétés d'une ressource
$resource->all('rdfs:subClassOf');
$resource->get('rdfs:label');

// Lister les propriétés
$graph->allOfType('owl:ObjectProperty');</code></pre>

        <h3>Traversée de hiérarchie</h3>
        <p>La hiérarchie est construite récursivement. Pour éviter les cycles (OWL permet des graphes), chaque appel reçoit un tableau <code>$visited</code> d'URI déjà traités.</p>

        <h2>4. API REST JSON</h2>
        <p>L'<code>ApiController</code> expose 8 endpoints consommés par le JavaScript D3.js. Chaque réponse est du JSON pur avec <code>Content-Type: application/json</code>.</p>

        <h2>5. Visualisations D3.js</h2>
        <h3>Gestion de l'état inter-visualisations</h3>
        <p>Un objet global <code>AppState</code> centralise l'état. Chaque visualisation lit cet état au démarrage et le met à jour lors des interactions (clic nœud, expand/collapse, zoom).</p>
        <pre><code>// Changement de visualisation sans perte d'état
vizTabs.forEach(tab =&gt; tab.addEventListener('click', () =&gt; {
    AppState.currentViz = tab.dataset.viz;
    renderCurrentViz(); // utilise AppState.concept, depth...
}));</code></pre>

        <h3>Les 5 visualisations</h3>
        <table>
            <tr><th>Viz</th><th>API utilisée</th><th>Layout D3</th></tr>
            <tr><td>Arbre (Tree)</td><td>/api/hierarchy/{C}</td><td>d3.tree() vertical</td></tr>
            <tr><td>Radial</td><td>/api/hierarchy/{C}</td><td>d3.tree() radial</td></tr>
            <tr><td>Force</td><td>/api/graph</td><td>d3.forceSimulation()</td></tr>
            <tr><td>Coupe (Neighborhood)</td><td>/api/class-properties/{C}</td><td>d3.forceSimulation() local</td></tr>
            <tr><td>Sunburst</td><td>/api/hierarchy/{C}</td><td>d3.partition() + arc</td></tr>
            <tr><td>Combiné</td><td>/api/combined</td><td>d3.tree() + overlay</td></tr>
        </table>

        <h3>Collapse/Expand</h3>
        <p>Chaque nœud mémorise ses enfants dans <code>_children</code> (nœuds cachés) vs <code>children</code> (nœuds visibles). Le toggle inverse les deux. L'état des nœuds ouverts est sauvegardé dans <code>AppState.expandedNodes</code> (Set d'URI).</p>

        <h3>Navigation inter-vues sur clic de nœud</h3>
        <p>Cliquer sur un nœud dans une vue met à jour <code>AppState.concept</code>, puis l'utilisateur peut changer de visualisation — la nouvelle vue part du même concept.</p>

        <h2>6. Déploiement sur Railway</h2>
        <ol>
            <li>Pousser le code sur GitHub</li>
            <li>Créer un projet Railway → "Deploy from GitHub repo"</li>
            <li>Railway détecte le <code>Dockerfile</code> automatiquement</li>
            <li>Railway expose un port public (ex: <code>https://novyra-graphis.up.railway.app</code>)</li>
            <li>Aucune variable d'environnement requise</li>
        </ol>

        <h2>7. Structure Git recommandée</h2>
        <pre><code>main          ← production stable
├── develop   ← intégration
│   ├── feature/viz-force
│   ├── feature/viz-sunburst
│   └── feature/api-combined
└── hotfix/   ← corrections urgentes</code></pre>

        <h2>8. Génération de l'archive</h2>
        <pre><code># Archive tar.gz sans vendor/ ni uploads
tar --exclude='vendor' --exclude='storage/uploads/*' \
    -czf novyra-graphis.tar.gz novyra-graphis/</code></pre>
    </div>
</div>
