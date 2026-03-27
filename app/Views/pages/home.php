<?php
/** @var bool $hasFile */
/** @var array $classes */
/** @var array $properties */
/** @var string|null $fileName */
?>

<?php if (!$hasFile): ?>
<!-- LANDING PAGE -->
<div class="landing">
    <div class="landing-eyebrow">Novyra Graphis</div>
    <h1 class="landing-title">
        Explorez vos<br>
        <span class="hl">ontologies OWL</span><br>
        <span class="dim">en un coup d'œil</span>
    </h1>
    <p class="landing-sub">
        Chargez n'importe quel fichier OWL ou RDF et naviguez
        dans ses hiérarchies, classes et propriétés grâce à des
        visualisations interactives D3.js.
    </p>

    <div class="upload-card">
        <div class="upload-card-title">Charger une ontologie</div>
        <form method="POST" action="/upload" enctype="multipart/form-data" id="upload-form">
            <div class="upload-zone" id="drop-zone">
                <input type="file" name="owl_file" id="file-input" accept=".owl,.rdf,.xml,.ttl,.n3,.nt,.jsonld,.json">
                <span class="upload-icon">◈</span>
                <div class="upload-text">
                    <strong>Glisser-déposer ou cliquer</strong>
                    Sélectionnez votre fichier OWL/RDF
                </div>
                <div class="upload-exts">.owl · .rdf · .xml · .ttl · .n3 · .nt · .jsonld</div>
            </div>
            <div id="selected-file" style="display:none; margin-top:12px;" class="file-loaded">
                <span class="file-icon">◈</span>
                <span class="file-name" id="selected-name"></span>
            </div>
            <button type="submit" class="btn btn-primary btn-full" style="margin-top:14px;" id="upload-btn" disabled>
                ▶ Visualiser l'ontologie
            </button>
        </form>
    </div>

    <div class="feature-grid">
        <div class="feature-card">
            <div class="feature-icon">🌿</div>
            <div class="feature-name">Arbre</div>
            <div class="feature-desc">Hiérarchie verticale collapsible des sous-classes</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">◎</div>
            <div class="feature-name">Radial</div>
            <div class="feature-desc">Layout radial centré sur un concept pivot</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">⬡</div>
            <div class="feature-name">Force</div>
            <div class="feature-desc">Graphe de force dynamique — vue globale</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">⊙</div>
            <div class="feature-name">Coupe</div>
            <div class="feature-desc">Vue locale — concept et ses voisins directs</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">◉</div>
            <div class="feature-name">Sunburst</div>
            <div class="feature-desc">Partition hiérarchique en anneaux concentriques</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">⟁</div>
            <div class="feature-name">Combiné</div>
            <div class="feature-desc">Héritage + propriétés + chaîne de propriété</div>
        </div>
    </div>

    <div class="landing-footer">
        Une réalisation <strong>Novyra</strong> — Novyra Graphis v1.0
    </div>
</div>

<?php else: ?>
<!-- MAIN APP -->
<div class="app-layout">
    <!-- SIDEBAR -->
    <aside class="sidebar">

        <!-- File section -->
        <div class="sidebar-section">
            <div class="sidebar-title">Ontologie chargée</div>
            <div class="file-loaded">
                <span class="file-icon">◈</span>
                <span class="file-name" title="<?= htmlspecialchars($fileName ?? '') ?>"><?= htmlspecialchars($fileName ?? '') ?></span>
                <a href="/" title="Changer de fichier"><button class="file-clear" type="button">✕</button></a>
            </div>
            <div class="stats-bar">
                <div class="stat-item">Classes&nbsp;<span class="stat-val"><?= count($classes) ?></span></div>
                <div class="stat-item">Propriétés&nbsp;<span class="stat-val"><?= count($properties) ?></span></div>
            </div>
        </div>

        <!-- Visualization selector -->
        <div class="sidebar-section">
            <div class="sidebar-title">Visualisation</div>
            <div class="viz-tabs">
                <button class="viz-tab active" data-viz="tree">🌿 Arbre</button>
                <button class="viz-tab" data-viz="radial">◎ Radial</button>
                <button class="viz-tab" data-viz="force">⬡ Force</button>
                <button class="viz-tab" data-viz="neighborhood">⊙ Coupe</button>
                <button class="viz-tab" data-viz="sunburst">◉ Sunburst</button>
                <button class="viz-tab" data-viz="combined">⟁ Combiné</button>
            </div>
        </div>

        <!-- Mode & params -->
        <div class="sidebar-section">
            <div class="sidebar-title">Paramètres</div>
            <div class="control-group">
                <label class="control-label">Mode de données</label>
                <select class="select-styled" id="data-mode">
                    <option value="hierarchy">Héritage de classes</option>
                    <option value="properties">Hiérarchie de propriété</option>
                    <option value="graph">Graphe complet</option>
                </select>
            </div>

            <div class="control-group" id="concept-group">
                <label class="control-label">Concept racine (C)</label>
                <select class="select-styled" id="concept-select">
                    <option value="">— Sélectionner —</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= htmlspecialchars($c['uri']) ?>"><?= htmlspecialchars($c['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="control-group" id="property-group" style="display:none;">
                <label class="control-label">Propriété (P)</label>
                <select class="select-styled" id="property-select">
                    <option value="">— Sélectionner —</option>
                    <?php foreach ($properties as $p): ?>
                        <option value="<?= htmlspecialchars($p['uri']) ?>"><?= htmlspecialchars($p['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="control-group">
                <label class="control-label">Profondeur (p)</label>
                <div class="range-row">
                    <input type="range" id="depth-range" min="1" max="10" value="5">
                    <span class="range-val" id="depth-val">5</span>
                </div>
            </div>

            <button class="btn btn-primary btn-full" id="render-btn">▶ Visualiser</button>
        </div>

        <!-- Navigation history -->
        <div class="sidebar-section" style="flex:1; overflow-y:auto;">
            <div class="sidebar-title">Navigation</div>
            <div id="nav-history" style="font-size:11px; color:var(--text3); font-family:var(--font-head); line-height:1.6;">
                Cliquez sur un nœud pour naviguer.<br>
                L'état est conservé entre les vues.
            </div>
            <div id="breadcrumb" style="margin-top:10px;"></div>
        </div>

    </aside>

    <!-- VIZ AREA -->
    <section class="viz-area">
        <div class="viz-header">
            <div class="viz-header-title">
                <span id="viz-title-type">Arbre</span> —
                <span id="viz-title-concept" style="color:var(--text2);">sélectionner un concept</span>
            </div>
            <div class="viz-actions">
                <button class="btn btn-ghost btn-sm" id="btn-reset">↺ Reset</button>
                <button class="btn btn-ghost btn-sm" id="btn-center">⊕ Centrer</button>
                <button class="btn btn-ghost btn-sm" id="btn-expand">⊞ Ouvrir</button>
                <button class="btn btn-ghost btn-sm" id="btn-collapse">⊟ Fermer</button>
            </div>
        </div>

        <div class="viz-canvas" id="viz-canvas">
            <div class="empty-state" id="empty-state">
                <div class="empty-state-icon">⬡</div>
                <div class="empty-state-text">Aucune visualisation</div>
                <div class="empty-state-sub">Sélectionnez un concept et cliquez sur Visualiser</div>
            </div>
            <svg id="main-svg" style="display:none;"></svg>
            <div class="loading-overlay" id="loading" style="display:none;">
                <div class="spinner"></div>
                <div class="spinner-label">Chargement…</div>
            </div>
        </div>

        <div class="legend" id="legend" style="display:none;">
            <div class="legend-title">Légende</div>
            <div id="legend-items"></div>
        </div>

        <div class="info-panel" id="info-panel">
            <div class="info-panel-title">
                <span id="info-label">—</span>
                <button class="info-panel-close" id="info-close">✕</button>
            </div>
            <div id="info-content"></div>
        </div>
    </section>
</div>

<div class="viz-tooltip" id="tooltip">
    <div class="tooltip-label" id="tt-label"></div>
    <div class="tooltip-uri"   id="tt-uri"></div>
    <div class="tooltip-meta"  id="tt-meta"></div>
</div>
<?php endif; ?>
