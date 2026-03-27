<?php /** @var string $title */ ?>
<div class="wiki-layout">
    <nav class="wiki-nav">
        <div class="wiki-nav-title">Documentation</div>
        <a href="/wiki/technique" class="wiki-nav-link active">📐 Manuel Technique</a>
        <a href="/wiki/realisation" class="wiki-nav-link">🔨 Manuel de Réalisation</a>
        <a href="/wiki/api" class="wiki-nav-link">⚡ Documentation API</a>
        <a href="/wiki/visualisations" class="wiki-nav-link">🎨 Guide des Visualisations</a>
        <div class="wiki-nav-brand"><strong>Novyra Graphis</strong>par Novyra</div>
    </nav>
    <div class="wiki-content">
        <h1>Manuel Technique</h1>
        <p class="wiki-subtitle">Architecture, stack et déploiement</p>

        <h2>Stack technologique</h2>
        <table>
            <tr><th>Composant</th><th>Technologie</th><th>Version</th></tr>
            <tr><td>Langage serveur</td><td>PHP</td><td>8.2</td></tr>
            <tr><td>Patron architectural</td><td>MVC maison</td><td>—</td></tr>
            <tr><td>Parseur RDF/OWL</td><td>EasyRdf</td><td>^1.1</td></tr>
            <tr><td>Visualisation</td><td>D3.js</td><td>7.8.5</td></tr>
            <tr><td>Serveur HTTP</td><td>Apache 2 (Docker)</td><td>—</td></tr>
            <tr><td>Hébergement</td><td>Railway</td><td>—</td></tr>
            <tr><td>Gestionnaire de dépendances</td><td>Composer</td><td>2.x</td></tr>
        </table>

        <h2>Architecture MVC</h2>
        <pre><code>novyra-graphis/
├── public/               ← DocumentRoot Apache
│   ├── index.php         ← Point d'entrée unique
│   ├── .htaccess         ← Réécriture d'URL
│   ├── css/app.css       ← Styles globaux
│   └── js/app.js         ← Logique D3.js (5 visualisations)
│
├── app/
│   ├── Core/
│   │   ├── Router.php    ← Routeur REST (regex)
│   │   ├── Request.php   ← Encapsulation $_GET/$_POST
│   │   └── Controller.php← Classe de base (render, json, redirect)
│   │
│   ├── Controllers/
│   │   ├── HomeController.php  ← Upload + page principale
│   │   ├── ApiController.php   ← Endpoints JSON pour D3.js
│   │   └── WikiController.php  ← Documentation
│   │
│   ├── Models/
│   │   ├── OntologyModel.php   ← Logique EasyRdf (parsing + requêtes)
│   │   └── OntologySession.php ← Persistance du fichier en session
│   │
│   └── Views/
│       ├── layouts/main.php    ← Layout principal (nav, scripts)
│       ├── layouts/wiki.php    ← Layout wiki
│       └── pages/              ← Vues par page
│
├── config/routes.php     ← Déclaration des routes
├── storage/uploads/      ← Fichiers OWL temporaires (1h)
├── docker/apache.conf    ← Config Apache
├── Dockerfile            ← Image Docker pour Railway
└── composer.json         ← Dépendances PHP</code></pre>

        <h2>Flux d'une requête</h2>
        <ol>
            <li><code>public/index.php</code> démarre la session et charge l'autoloader Composer</li>
            <li>Le <code>Router</code> matche l'URI avec une regex et instancie le contrôleur</li>
            <li>Le contrôleur interagit avec <code>OntologyModel</code> (via EasyRdf) ou <code>OntologySession</code></li>
            <li>La réponse est soit un rendu HTML (layout + vue) soit du JSON (API)</li>
            <li>Le frontend JavaScript consomme l'API et pilote D3.js</li>
        </ol>

        <h2>Formats OWL/RDF supportés</h2>
        <table>
            <tr><th>Extension</th><th>Format</th></tr>
            <tr><td>.owl, .rdf, .xml</td><td>RDF/XML (défaut)</td></tr>
            <tr><td>.ttl</td><td>Turtle</td></tr>
            <tr><td>.n3</td><td>Notation 3</td></tr>
            <tr><td>.nt</td><td>N-Triples</td></tr>
            <tr><td>.jsonld, .json</td><td>JSON-LD</td></tr>
        </table>

        <h2>Déploiement Railway</h2>
        <p>L'application se déploie depuis GitHub via un <code>Dockerfile</code> :</p>
        <pre><code>FROM php:8.2-apache
# Installation des extensions PHP + Composer
# Copie du projet + composer install --no-dev
# Config Apache avec mod_rewrite
# Port 80 exposé</code></pre>
        <p>Variables d'environnement Railway : aucune requise (les fichiers uploadés sont stockés en local dans <code>storage/uploads/</code>, durée de vie 1h).</p>

        <h2>Sécurité</h2>
        <ul>
            <li>Validation de l'extension et de la taille (max 50 MB) avant stockage</li>
            <li>Le fichier est re-parsé par EasyRdf pour valider le contenu RDF</li>
            <li>Les URI des concepts sont encodées/décodées (<code>urlencode/urldecode</code>) dans les routes</li>
            <li>Toutes les sorties HTML passent par <code>htmlspecialchars()</code></li>
            <li>Nettoyage automatique des fichiers de plus d'1h</li>
        </ul>

        <h2>Persistance d'état entre visualisations</h2>
        <p>L'état de navigation est géré côté client dans un objet JavaScript global <code>AppState</code> :</p>
        <pre><code>AppState = {
  currentViz: 'tree',    // Vue active
  concept: '...',        // URI du concept courant
  property: '...',       // URI de la propriété courante
  depth: 5,              // Profondeur
  expandedNodes: Set,    // Nœuds ouverts/fermés
  focusNode: null,       // Nœud focalisé
  transform: {...}       // Zoom/pan D3
}</code></pre>
        <p>Quand l'utilisateur change de visualisation, le concept, la propriété, la profondeur et les nœuds développés sont rejoués dans la nouvelle vue.</p>
    </div>
</div>
