<?php /** @var string $title */ ?>
<div class="wiki-layout">
    <nav class="wiki-nav">
        <div class="wiki-nav-title">Documentation</div>
        <a href="/wiki/technique" class="wiki-nav-link">📐 Manuel Technique</a>
        <a href="/wiki/realisation" class="wiki-nav-link">🔨 Manuel de Réalisation</a>
        <a href="/wiki/api" class="wiki-nav-link">⚡ Documentation API</a>
        <a href="/wiki/visualisations" class="wiki-nav-link active">🎨 Guide des Visualisations</a>
        <div class="wiki-nav-brand"><strong>Novyra Graphis</strong>par Novyra</div>
    </nav>
    <div class="wiki-content">
        <h1>Guide des Visualisations</h1>
        <p class="wiki-subtitle">Les 5 visualisations D3.js disponibles</p>

        <h2>🌳 1. Arbre (Tree)</h2>
        <p><strong>Usage :</strong> Visualiser l'héritage (sous-classes) d'un concept C de façon hiérarchique et lisible.</p>
        <p><strong>Données :</strong> <code>GET /api/hierarchy/{C}?depth=N</code></p>
        <p><strong>Interactions :</strong></p>
        <ul>
            <li>Clic sur un nœud → collapse/expand ses enfants</li>
            <li>Clic droit / double-clic → ouvrir le panneau d'info + passer à ce nœud dans une autre vue</li>
            <li>Zoom/pan avec la molette et le glisser</li>
            <li>Boutons "Tout ouvrir" / "Tout fermer" dans la barre d'outils</li>
        </ul>
        <p><strong>Représentation :</strong> Arbre vertical orienté de haut en bas. Les nœuds avec enfants cachés ont un cercle en pointillé. La couleur des nœuds indique la profondeur.</p>

        <h2>🕸️ 2. Radial (Radial Tree)</h2>
        <p><strong>Usage :</strong> Même données que l'arbre mais en layout radial — idéal pour voir d'un coup d'œil les ramifications d'une hiérarchie dense.</p>
        <p><strong>Données :</strong> <code>GET /api/hierarchy/{C}?depth=N</code></p>
        <p><strong>Interactions :</strong></p>
        <ul>
            <li>Clic sur un nœud → collapse/expand</li>
            <li>Rotation possible via zoom/pan</li>
            <li>Clic sur un nœud feuille → devient le nouveau centre si "Visualiser" est relancé</li>
        </ul>
        <p><strong>Représentation :</strong> Le concept racine est au centre. Les descendants rayonnent en cercles concentriques. Les liens sont des courbes de Bézier radiales.</p>

        <h2>🔵 3. Force (Force-directed Graph)</h2>
        <p><strong>Usage :</strong> Vue globale de l'ontologie entière — toutes les classes et toutes les propriétés.</p>
        <p><strong>Données :</strong> <code>GET /api/graph</code></p>
        <p><strong>Interactions :</strong></p>
        <ul>
            <li>Glisser un nœud pour le fixer dans l'espace</li>
            <li>Clic sur un nœud → panneau info + mise à jour du concept courant</li>
            <li>Double-clic → relance la simulation depuis ce nœud</li>
            <li>Molette → zoom</li>
        </ul>
        <p><strong>Représentation :</strong> Les nœuds se repoussent (charge négative), les liens les attirent. Les arêtes <code>subClassOf</code> sont bleues, les arêtes de propriété sont orange avec étiquette.</p>

        <h2>⚡ 4. Coupe (Neighborhood)</h2>
        <p><strong>Usage :</strong> Vue chirurgicale — zoom sur un concept et ses voisins directs (propriétés en domaine/codomaine, classes liées).</p>
        <p><strong>Données :</strong> <code>GET /api/class-properties/{C}</code></p>
        <p><strong>Interactions :</strong></p>
        <ul>
            <li>Clic sur un voisin → il devient le nouveau concept central</li>
            <li>Clic sur une arête → détail de la propriété</li>
            <li>Permet de "sauter" de classe en classe en suivant les propriétés</li>
        </ul>
        <p><strong>Représentation :</strong> Le concept C est au centre (grand cercle cyan). Ses voisins gravitent autour en simulation de force légère. Les arêtes portent le nom de la propriété.</p>

        <h2>🗺️ 5. Sunburst</h2>
        <p><strong>Usage :</strong> Vue hiérarchique en anneau concentrique — excellente pour les ontologies profondes avec beaucoup de branches.</p>
        <p><strong>Données :</strong> <code>GET /api/hierarchy/{C}?depth=N</code></p>
        <p><strong>Interactions :</strong></p>
        <ul>
            <li>Clic sur un arc → zoom in (le segment devient la nouvelle racine)</li>
            <li>Clic sur le centre → zoom out (revenir au parent)</li>
            <li>Survol → tooltip avec label et URI</li>
        </ul>
        <p><strong>Représentation :</strong> La racine est au centre. Chaque anneau est un niveau de profondeur. La largeur angulaire de chaque arc est proportionnelle au nombre de descendants.</p>

        <h2>🔗 6. Combiné (Combined View)</h2>
        <p><strong>Usage :</strong> Vue synthétique combinant : héritage de C + ses propriétés + chaîne d'une propriété P sur p liens.</p>
        <p><strong>Données :</strong> <code>GET /api/combined?concept=C&property=P&depth=p</code></p>
        <p><strong>Interactions :</strong></p>
        <ul>
            <li>Navigation entre les trois couches visuelles</li>
            <li>Clic sur un nœud d'héritage → focus sur ce concept</li>
            <li>Clic sur une propriété → affiche la chaîne depuis ce point</li>
        </ul>
        <p><strong>Représentation :</strong> Arbre d'héritage central, propriétés affichées comme nœuds orange satellites, chaîne de propriété en bas avec arêtes pointillées.</p>

        <h2>Persistance d'état inter-visualisations</h2>
        <p>Toutes les vues partagent le même <code>AppState</code>. En passant d'une vue à l'autre :</p>
        <ul>
            <li>Le concept courant est conservé et appliqué dans la nouvelle vue</li>
            <li>La profondeur est conservée</li>
            <li>Les nœuds ouverts/fermés (arbre, radial) sont mémorisés par URI</li>
            <li>Le zoom/pan est remis à zéro (centré sur le nouveau rendu)</li>
        </ul>
    </div>
</div>
