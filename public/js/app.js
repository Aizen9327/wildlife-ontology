/* ============================================================
   OWLviz — app.js
   5 visualisations D3.js avec gestion d'état partagé
   ============================================================ */

/* ── Upload page ── */
(function initUpload() {
  const dropZone = document.getElementById('drop-zone');
  const fileInput = document.getElementById('file-input');
  const uploadBtn = document.getElementById('upload-btn');
  const selectedFile = document.getElementById('selected-file');
  const selectedName = document.getElementById('selected-name');
  if (!dropZone) return;

  fileInput?.addEventListener('change', () => {
    const f = fileInput.files[0];
    if (f) {
      selectedName.textContent = f.name;
      selectedFile.style.display = 'flex';
      uploadBtn.disabled = false;
    }
  });

  dropZone?.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
  dropZone?.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
  dropZone?.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    const f = e.dataTransfer.files[0];
    if (f) {
      const dt = new DataTransfer(); dt.items.add(f);
      fileInput.files = dt.files;
      selectedName.textContent = f.name;
      selectedFile.style.display = 'flex';
      uploadBtn.disabled = false;
    }
  });
})();

/* ── Main App ── */
if (document.getElementById('main-svg')) {
  initApp();
}

function initApp() {
  /* ── State ── */
  const AppState = {
    currentViz: 'tree',
    concept: null,
    property: null,
    depth: 5,
    mode: 'hierarchy',
    expandedNodes: new Set(),
    focusNode: null,
    transform: null,
    lastData: null,
  };

  /* ── DOM refs ── */
  const svg         = d3.select('#main-svg');
  const canvas      = document.getElementById('viz-canvas');
  const emptyState  = document.getElementById('empty-state');
  const loading     = document.getElementById('loading');
  const tooltip     = document.getElementById('tooltip');
  const ttLabel     = document.getElementById('tt-label');
  const ttUri       = document.getElementById('tt-uri');
  const ttMeta      = document.getElementById('tt-meta');
  const infoPanel   = document.getElementById('info-panel');
  const infoLabel   = document.getElementById('info-label');
  const infoContent = document.getElementById('info-content');
  const breadcrumb  = document.getElementById('breadcrumb');
  const vizTitleType    = document.getElementById('viz-title-type');
  const vizTitleConcept = document.getElementById('viz-title-concept');
  const legend      = document.getElementById('legend');
  const legendItems = document.getElementById('legend-items');
  const navHistory  = document.getElementById('nav-history');

  /* ── Controls ── */
  const vizTabs       = document.querySelectorAll('.viz-tab');
  const dataMode      = document.getElementById('data-mode');
  const conceptSelect = document.getElementById('concept-select');
  const propertySelect= document.getElementById('property-select');
  const depthRange    = document.getElementById('depth-range');
  const depthVal      = document.getElementById('depth-val');
  const renderBtn     = document.getElementById('render-btn');
  const btnReset      = document.getElementById('btn-reset');
  const btnCenter     = document.getElementById('btn-center');
  const btnExpand     = document.getElementById('btn-expand');
  const btnCollapse   = document.getElementById('btn-collapse');

  /* ── Event bindings ── */
  vizTabs.forEach(tab => tab.addEventListener('click', () => {
    vizTabs.forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    AppState.currentViz = tab.dataset.viz;
    updateHeader();
    if (AppState.lastData) rerender();
  }));

  dataMode.addEventListener('change', () => {
    AppState.mode = dataMode.value;
    const showConcept  = AppState.mode !== 'graph';
    const showProperty = AppState.mode === 'hierarchy';
    document.getElementById('concept-group').style.display  = showConcept  ? '' : 'none';
    document.getElementById('property-group').style.display = showProperty ? '' : 'none';
  });

  depthRange.addEventListener('input', () => {
    AppState.depth = +depthRange.value;
    depthVal.textContent = AppState.depth;
  });

  conceptSelect.addEventListener('change', () => { AppState.concept = conceptSelect.value || null; });
  propertySelect.addEventListener('change', () => { AppState.property = propertySelect.value || null; });

  renderBtn.addEventListener('click', () => {
    AppState.expandedNodes.clear();
    fetchAndRender();
  });

  btnReset.addEventListener('click', () => {
    AppState.expandedNodes.clear();
    if (AppState.lastData) rerender();
  });
  btnCenter.addEventListener('click', centerView);
  btnExpand.addEventListener('click', () => { expandAll(true);  rerender(); });
  btnCollapse.addEventListener('click', () => { expandAll(false); rerender(); });

  document.getElementById('info-close')?.addEventListener('click', () => infoPanel.classList.remove('visible'));

  /* ── Helpers ── */
  function showLoading(v) { loading.style.display = v ? 'flex' : 'none'; }
  function showEmpty(v) { emptyState.style.display = v ? 'flex' : 'none'; }
  function showSvg(v) { svg.attr('display', v ? null : 'none'); }

  function updateHeader() {
    const labels = { tree:'Arbre', radial:'Radial', force:'Force', neighborhood:'Coupe', sunburst:'Sunburst', combined:'Combiné' };
    vizTitleType.textContent = labels[AppState.currentViz] || AppState.currentViz;
    vizTitleConcept.textContent = AppState.conceptLabel || 'sélectionner un concept';
  }

  function localName(uri) {
    if (!uri) return '?';
    if (uri.includes('#')) return uri.split('#').pop();
    return uri.split('/').pop();
  }

  function showTooltip(event, d) {
    const label = d.data?.label || d.label || localName(d.data?.uri || d.uri);
    const uri   = d.data?.uri || d.uri || '';
    ttLabel.textContent = label;
    ttUri.textContent   = uri;
    ttMeta.textContent  = d.data?.type || d.type || '';
    tooltip.classList.add('visible');
    moveTooltip(event);
  }
  function moveTooltip(event) {
    const x = event.clientX + 12, y = event.clientY - 28;
    tooltip.style.left = Math.min(x, window.innerWidth - 300) + 'px';
    tooltip.style.top  = Math.max(y, 8) + 'px';
  }
  function hideTooltip() { tooltip.classList.remove('visible'); }

  function showInfo(d) {
    const data = d.data || d;
    infoLabel.textContent = data.label || localName(data.uri);
    let html = `<div class="info-row"><span class="info-key">URI</span><span class="info-val" style="font-size:10px">${data.uri || '—'}</span></div>`;
    if (data.type)   html += `<div class="info-row"><span class="info-key">Type</span><span class="info-val">${data.type}</span></div>`;
    if (data.domain) html += `<div class="info-row"><span class="info-key">Domaine</span><span class="info-val">${data.domain.label || localName(data.domain.uri)}</span></div>`;
    if (data.range)  html += `<div class="info-row"><span class="info-key">Codomaine</span><span class="info-val">${data.range.label || localName(data.range.uri)}</span></div>`;

    if (data.children?.length || data._children?.length) {
      const kids = data.children || data._children;
      html += `<div class="info-row"><span class="info-key">Sous-classes</span><span class="info-val">${kids.length}</span></div>`;
    }

    // Action: navigate to node
    if (data.uri) {
      html += `<div style="margin-top:10px">
        <button class="btn btn-ghost btn-sm btn-full" onclick="setConceptFromInfo('${data.uri}')">⊙ Pivoter sur ce concept</button>
      </div>`;
    }

    infoContent.innerHTML = html;
    infoPanel.classList.add('visible');
  }

  window.setConceptFromInfo = function(uri) {
    AppState.concept = uri;
    AppState.expandedNodes.clear();
    // Update select if exists
    const opt = conceptSelect.querySelector(`option[value="${CSS.escape(uri)}"]`);
    if (opt) { conceptSelect.value = uri; AppState.conceptLabel = opt.textContent; }
    else AppState.conceptLabel = localName(uri);
    infoPanel.classList.remove('visible');
    fetchAndRender();
  };

  function addBreadcrumb(label, uri) {
    const item = document.createElement('div');
    item.style.cssText = 'font-size:11px;padding:3px 8px;background:var(--bg3);border-radius:3px;margin-bottom:3px;cursor:pointer;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
    item.textContent = '↳ ' + label;
    item.title = uri;
    item.addEventListener('click', () => window.setConceptFromInfo(uri));
    breadcrumb.insertBefore(item, breadcrumb.firstChild);
    if (breadcrumb.children.length > 8) breadcrumb.removeChild(breadcrumb.lastChild);
  }

  function setLegend(items) {
    if (!items.length) { legend.style.display = 'none'; return; }
    legend.style.display = 'block';
    legendItems.innerHTML = items.map(it => {
      const shape = it.type === 'line'
        ? `<div class="legend-line" style="background:${it.color}"></div>`
        : `<div class="legend-dot" style="background:${it.color}"></div>`;
      return `<div class="legend-item">${shape}<span>${it.label}</span></div>`;
    }).join('');
  }

  /* ── Fetch & Render ── */
  async function fetchAndRender() {
    const label = conceptSelect.options[conceptSelect.selectedIndex]?.text;
    if (AppState.concept && label) { AppState.conceptLabel = label; addBreadcrumb(label, AppState.concept); }
    showLoading(true);
    showEmpty(false);
    showSvg(false);

    try {
      let data;
      const mode = AppState.mode;
      const viz  = AppState.currentViz;
      const enc  = encodeURIComponent;

      if (mode === 'graph' || viz === 'force') {
        data = await fetch('/api/graph').then(r => r.json());
      } else if (mode === 'properties' && AppState.property) {
        data = await fetch(`/api/property-hierarchy/${enc(AppState.property)}?depth=${AppState.depth}`).then(r => r.json());
      } else if (viz === 'neighborhood' || viz === 'combined') {
        let url = `/api/combined?concept=${enc(AppState.concept||'')}&depth=${AppState.depth}`;
        if (AppState.property) url += `&property=${enc(AppState.property)}`;
        data = await fetch(url).then(r => r.json());
      } else if (AppState.concept) {
        data = await fetch(`/api/hierarchy/${enc(AppState.concept)}?depth=${AppState.depth}`).then(r => r.json());
      } else {
        showLoading(false); showEmpty(true); return;
      }

      AppState.lastData = data;
      rerender();
    } catch (e) {
      console.error(e);
      showLoading(false);
      showEmpty(true);
    }
  }

  function rerender() {
    showLoading(false);
    showSvg(true);
    showEmpty(false);
    updateHeader();

    svg.selectAll('*').remove();
    const viz = AppState.currentViz;

    try {
      if      (viz === 'tree')         drawTree(AppState.lastData);
      else if (viz === 'radial')       drawRadial(AppState.lastData);
      else if (viz === 'force')        drawForce(AppState.lastData);
      else if (viz === 'neighborhood') drawNeighborhood(AppState.lastData);
      else if (viz === 'sunburst')     drawSunburst(AppState.lastData);
      else if (viz === 'combined')     drawCombined(AppState.lastData);
    } catch(e) {
      console.error('Render error:', e);
    }
  }

  /* ── Colors ── */
  // Novyra Green Palette
  const COLORS = {
    class:    '#22c55e',
    property: '#a3e635',
    instance: '#4ade80',
    subClass: '#16a34a',
    depths: ['#22c55e','#4ade80','#86efac','#a3e635','#16a34a','#bbf7d0','#65a30d','#6ee7b7'],
  };

  function depthColor(d) { return COLORS.depths[d.depth % COLORS.depths.length]; }

  /* ──────────────────────────────────────────
     VIZ 1 — TREE (Vertical collapsible)
  ────────────────────────────────────────── */
  function drawTree(rawData) {
    if (!rawData || rawData.error) { showEmpty(true); showSvg(false); return; }

    setLegend([
      { color: COLORS.depths[0], label: 'Racine' },
      { color: COLORS.depths[1], label: 'Niveau 1' },
      { color: COLORS.depths[2], label: 'Niveau 2+' },
      { color: '#555', label: 'Nœud collapsé', type:'line' },
    ]);

    const W = canvas.clientWidth, H = canvas.clientHeight;
    const margin = { top: 40, right: 20, bottom: 20, left: 20 };
    const nodeH = 40, nodeW = 160;

    // Build hierarchy, restore expand state
    const root = d3.hierarchy(rawData);
    root.descendants().forEach(d => {
      d._id = d.data.uri;
      if (d.children && !AppState.expandedNodes.has(d._id) && d.depth > 0) {
        d._children = d.children;
        d.children = null;
      } else if (d.children) {
        AppState.expandedNodes.add(d._id);
      }
    });

    const zoom = d3.zoom().scaleExtent([.05, 3]).on('zoom', e => { g.attr('transform', e.transform); AppState.transform = e.transform; });
    svg.call(zoom);
    if (AppState.transform) svg.call(zoom.transform, AppState.transform);

    const g = svg.append('g');
    if (!AppState.transform) g.attr('transform', `translate(${W/2},${margin.top})`);

    // Defs: arrowhead
    svg.append('defs').append('marker')
      .attr('id','arrow-tree').attr('viewBox','0 -5 10 10').attr('refX',18).attr('refY',0)
      .attr('markerWidth',6).attr('markerHeight',6).attr('orient','auto')
      .append('path').attr('d','M0,-5L10,0L0,5').attr('fill','#4a5568');

    function update(source) {
      const treeLayout = d3.tree().nodeSize([nodeW, nodeH]);
      treeLayout(root);

      const nodes = root.descendants();
      const links = root.links();

      // Links
      const link = g.selectAll('.link').data(links, d => d.target._id);
      link.enter().append('path').attr('class','link')
        .attr('stroke', '#14532d').attr('stroke-dasharray', d => d.target._children ? '4,3' : null)
        .attr('marker-end','url(#arrow-tree)')
        .attr('d', d => `M${d.source.x},${d.source.y} C${d.source.x},${(d.source.y+d.target.y)/2} ${d.target.x},${(d.source.y+d.target.y)/2} ${d.target.x},${d.target.y}`);
      link.exit().remove();
      g.selectAll('.link').attr('d', d => `M${d.source.x},${d.source.y} C${d.source.x},${(d.source.y+d.target.y)/2} ${d.target.x},${(d.source.y+d.target.y)/2} ${d.target.x},${d.target.y}`)
        .attr('stroke-dasharray', d => d.target._children ? '4,3' : null);

      // Nodes
      const node = g.selectAll('.node').data(nodes, d => d._id);
      const nodeEnter = node.enter().append('g').attr('class','node')
        .attr('transform', d => `translate(${d.x},${d.y})`)
        .style('cursor','pointer')
        .on('click', (e, d) => { toggleNode(d); update(d); })
        .on('dblclick', (e, d) => { e.stopPropagation(); showInfo(d); })
        .on('mouseover', (e, d) => showTooltip(e, d))
        .on('mousemove', moveTooltip)
        .on('mouseout', hideTooltip);

      nodeEnter.append('circle').attr('r', d => d.depth === 0 ? 16 : 10)
        .attr('fill', depthColor)
        .attr('stroke', d => d._children ? '#fff' : depthColor)
        .attr('stroke-width', d => d._children ? 2.5 : 1.5)
        .attr('stroke-dasharray', d => d._children ? '4,2' : null);

      nodeEnter.append('text')
        .attr('dy', d => d.children || d._children ? -18 : 18)
        .attr('text-anchor','middle')
        .attr('fill','#e2e8f0').attr('font-size','10px')
        .text(d => {
          const lbl = d.data.label || localName(d.data.uri);
          return lbl.length > 18 ? lbl.slice(0,16)+'…' : lbl;
        });

      node.exit().remove();
      g.selectAll('.node').attr('transform', d => `translate(${d.x},${d.y})`);
      g.selectAll('.node circle')
        .attr('stroke-dasharray', d => d._children ? '4,2' : null)
        .attr('stroke', d => d._children ? '#fff' : depthColor);
    }

    function toggleNode(d) {
      if (d.children) {
        d._children = d.children; d.children = null;
        AppState.expandedNodes.delete(d._id);
      } else if (d._children) {
        d.children = d._children; d._children = null;
        AppState.expandedNodes.add(d._id);
      }
    }

    update(root);
    window._treeRoot = root;
    window._treeUpdate = update;

    btnCenter.onclick = centerView;
    btnExpand.onclick = () => { expandAllNodes(root, true); update(root); };
    btnCollapse.onclick = () => { expandAllNodes(root, false); update(root); };
  }

  function expandAllNodes(root, open) {
    root.descendants().forEach(d => {
      if (open) {
        if (d._children) { d.children = d._children; d._children = null; }
        AppState.expandedNodes.add(d._id);
      } else {
        if (d.children && d.depth > 0) { d._children = d.children; d.children = null; }
        if (d.depth > 0) AppState.expandedNodes.delete(d._id);
      }
    });
  }

  /* ──────────────────────────────────────────
     VIZ 2 — RADIAL
  ────────────────────────────────────────── */
  function drawRadial(rawData) {
    if (!rawData || rawData.error) { showEmpty(true); showSvg(false); return; }

    setLegend([
      { color: COLORS.depths[0], label: 'Racine' },
      { color: COLORS.depths[1], label: 'Profondeur 1' },
      { color: COLORS.depths[2], label: 'Profondeur 2' },
    ]);

    const W = canvas.clientWidth, H = canvas.clientHeight;
    const radius = Math.min(W, H) / 2 - 80;

    const zoom = d3.zoom().scaleExtent([.1, 4]).on('zoom', e => { g.attr('transform', e.transform); AppState.transform = e.transform; });
    svg.call(zoom);

    const g = svg.append('g');
    if (AppState.transform) svg.call(zoom.transform, AppState.transform);
    else g.attr('transform', `translate(${W/2},${H/2})`);

    const root = d3.hierarchy(rawData);
    root.descendants().forEach(d => {
      d._id = d.data.uri;
      if (d.children && !AppState.expandedNodes.has(d._id) && d.depth > 0) {
        d._children = d.children; d.children = null;
      } else if (d.children) AppState.expandedNodes.add(d._id);
    });

    const radialLayout = d3.tree().size([2 * Math.PI, radius]).separation((a, b) => (a.parent === b.parent ? 1 : 2) / a.depth);

    function radialPoint(x, y) { return [(y = +y) * Math.cos(x -= Math.PI / 2), y * Math.sin(x)]; }

    svg.append('defs').append('marker')
      .attr('id','arrow-radial').attr('viewBox','0 -5 10 10').attr('refX',15).attr('refY',0)
      .attr('markerWidth',5).attr('markerHeight',5).attr('orient','auto')
      .append('path').attr('d','M0,-5L10,0L0,5').attr('fill','#4a5568');

    function update() {
      radialLayout(root);
      g.selectAll('*').remove();

      // Links
      g.selectAll('.link').data(root.links()).enter().append('path').attr('class','link')
        .attr('stroke','#14532d').attr('fill','none').attr('stroke-width',1.5)
        .attr('marker-end','url(#arrow-radial)')
        .attr('d', d3.linkRadial().angle(d => d.x).radius(d => d.y));

      // Nodes
      const node = g.selectAll('.node').data(root.descendants()).enter().append('g').attr('class','node')
        .attr('transform', d => `translate(${radialPoint(d.x, d.y)})`)
        .style('cursor','pointer')
        .on('click', (e, d) => { toggleRadial(d); update(); })
        .on('dblclick', (e, d) => { e.stopPropagation(); showInfo(d); })
        .on('mouseover', (e, d) => showTooltip(e, d))
        .on('mousemove', moveTooltip)
        .on('mouseout', hideTooltip);

      node.append('circle').attr('r', d => d.depth === 0 ? 14 : 8)
        .attr('fill', depthColor)
        .attr('stroke', d => d._children ? '#fff' : 'transparent')
        .attr('stroke-width', 2)
        .attr('stroke-dasharray', d => d._children ? '3,2' : null);

      node.append('text')
        .attr('dy','0.31em')
        .attr('x', d => d.x < Math.PI === !d.children ? 12 : -12)
        .attr('text-anchor', d => d.x < Math.PI === !d.children ? 'start' : 'end')
        .attr('transform', d => d.x >= Math.PI ? 'rotate(180)' : null)
        .attr('fill','#e2e8f0').attr('font-size','9px')
        .text(d => { const l = d.data.label || localName(d.data.uri); return l.length > 15 ? l.slice(0,13)+'…' : l; });
    }

    function toggleRadial(d) {
      if (d.children) { d._children = d.children; d.children = null; AppState.expandedNodes.delete(d._id); }
      else if (d._children) { d.children = d._children; d._children = null; AppState.expandedNodes.add(d._id); }
    }

    update();

    btnExpand.onclick = () => { expandAllNodes(root, true); update(); };
    btnCollapse.onclick = () => { expandAllNodes(root, false); update(); };
  }

  /* ──────────────────────────────────────────
     VIZ 3 — FORCE DIRECTED GRAPH
  ────────────────────────────────────────── */
  function drawForce(rawData) {
    if (!rawData || rawData.error || !rawData.nodes) { showEmpty(true); showSvg(false); return; }

    setLegend([
      { color: COLORS.class,    label: 'Classe OWL' },
      { color: COLORS.subClass, label: 'Héritage (subClassOf)', type:'line' },
      { color: COLORS.property, label: 'Propriété', type:'line' },
    ]);

    const W = canvas.clientWidth, H = canvas.clientHeight;

    // Deduplicate nodes
    const nodesMap = {};
    rawData.nodes.forEach(n => { nodesMap[n.id] = { ...n }; });
    const nodes = Object.values(nodesMap);
    const links = rawData.links.filter(l => nodesMap[l.source] && nodesMap[l.target]).map(l => ({...l}));

    const zoom = d3.zoom().scaleExtent([.05, 5]).on('zoom', e => { g.attr('transform', e.transform); AppState.transform = e.transform; });
    svg.call(zoom);
    const g = svg.append('g');
    if (AppState.transform) svg.call(zoom.transform, AppState.transform);

    svg.append('defs').append('marker')
      .attr('id','arrow-force').attr('viewBox','0 -5 10 10').attr('refX',20).attr('refY',0)
      .attr('markerWidth',6).attr('markerHeight',6).attr('orient','auto')
      .append('path').attr('d','M0,-5L10,0L0,5').attr('fill','#4a5568');

    const sim = d3.forceSimulation(nodes)
      .force('link', d3.forceLink(links).id(d => d.id).distance(90).strength(0.5))
      .force('charge', d3.forceManyBody().strength(-300))
      .force('center', d3.forceCenter(W/2, H/2))
      .force('collision', d3.forceCollide(22));

    // Link labels
    const linkLabel = g.selectAll('.link-label').data(links.filter(l => l.label)).enter()
      .append('text').attr('class','link-label').attr('font-size','8px').attr('fill','#64748b')
      .text(d => d.label || '');

    // Links
    const link = g.selectAll('.link').data(links).enter().append('line').attr('class','link')
      .attr('stroke', d => d.type === 'subClassOf' ? '#14532d' : '#365314')
      .attr('stroke-width', d => d.type === 'subClassOf' ? 1.5 : 1)
      .attr('stroke-opacity', .7)
      .attr('marker-end','url(#arrow-force)');

    // Nodes
    const node = g.selectAll('.node').data(nodes).enter().append('g').attr('class','node')
      .style('cursor','pointer')
      .on('mouseover', (e, d) => showTooltip(e, d))
      .on('mousemove', moveTooltip)
      .on('mouseout', hideTooltip)
      .on('click', (e, d) => {
        AppState.concept = d.id;
        AppState.conceptLabel = d.label;
        updateHeader();
        showInfo(d);
        // highlight
        g.selectAll('.node circle').attr('stroke-width', n => n.id === d.id ? 3 : 1.5);
      })
      .call(d3.drag()
        .on('start', (e, d) => { if (!e.active) sim.alphaTarget(.3).restart(); d.fx = d.x; d.fy = d.y; })
        .on('drag',  (e, d) => { d.fx = e.x; d.fy = e.y; })
        .on('end',   (e, d) => { if (!e.active) sim.alphaTarget(0); })
      );

    node.append('circle').attr('r', 10)
      .attr('fill', d => d.type === 'class' ? '#052e16' : '#0f2e1a')
      .attr('stroke', d => d.type === 'class' ? COLORS.class : COLORS.property)
      .attr('stroke-width', 1.5);

    node.append('text').attr('dy','0.31em').attr('text-anchor','middle')
      .attr('fill','#cbd5e1').attr('font-size','8px').attr('pointer-events','none')
      .text(d => { const l = d.label || localName(d.id); return l.length > 10 ? l.slice(0,8)+'…' : l; });

    sim.on('tick', () => {
      link.attr('x1', d => d.source.x).attr('y1', d => d.source.y)
          .attr('x2', d => d.target.x).attr('y2', d => d.target.y);
      linkLabel.attr('x', d => (d.source.x + d.target.x)/2).attr('y', d => (d.source.y + d.target.y)/2);
      node.attr('transform', d => `translate(${d.x},${d.y})`);
    });

    btnCenter.onclick = () => {
      sim.alpha(.3).restart();
      svg.transition().duration(600).call(zoom.transform, d3.zoomIdentity.translate(W/2, H/2));
    };
  }

  /* ──────────────────────────────────────────
     VIZ 4 — NEIGHBORHOOD (Coupe)
  ────────────────────────────────────────── */
  function drawNeighborhood(rawData) {
    if (!rawData || rawData.error) { showEmpty(true); showSvg(false); return; }

    setLegend([
      { color: COLORS.class,    label: 'Concept central' },
      { color: COLORS.instance, label: 'Domaine' },
      { color: COLORS.property, label: 'Propriété' },
      { color: '#7c3aed',       label: 'Codomaine' },
    ]);

    const W = canvas.clientWidth, H = canvas.clientHeight;

    const props  = rawData.properties || { domain: [], range: [] };
    const center = { id: rawData.concept, label: rawData.label, type: 'center' };

    // Build nodes + links
    const nodes = [center];
    const links = [];
    const seen  = new Set([rawData.concept]);

    props.domain.forEach(p => {
      const propNode = { id: p.uri, label: p.label, type: 'property' };
      if (!seen.has(p.uri)) { nodes.push(propNode); seen.add(p.uri); }
      links.push({ source: rawData.concept, target: p.uri, label: 'domain', type: 'domain' });
      if (p.range?.uri && !seen.has(p.range.uri)) {
        nodes.push({ id: p.range.uri, label: p.range.label || localName(p.range.uri), type: 'range' });
        seen.add(p.range.uri);
        links.push({ source: p.uri, target: p.range.uri, label: 'range', type: 'range' });
      }
    });

    props.range.forEach(p => {
      if (!seen.has(p.uri)) { nodes.push({ id: p.uri, label: p.label, type: 'property' }); seen.add(p.uri); }
      links.push({ source: p.uri, target: rawData.concept, label: 'range', type: 'inRange' });
      if (p.domain?.uri && !seen.has(p.domain.uri)) {
        nodes.push({ id: p.domain.uri, label: p.domain.label || localName(p.domain.uri), type: 'domain' });
        seen.add(p.domain.uri);
        links.push({ source: p.domain.uri, target: p.uri, label: 'domain', type: 'domain' });
      }
    });

    const zoom = d3.zoom().scaleExtent([.1, 5]).on('zoom', e => { g.attr('transform', e.transform); AppState.transform = e.transform; });
    svg.call(zoom);
    const g = svg.append('g');
    if (AppState.transform) svg.call(zoom.transform, AppState.transform);

    svg.append('defs').append('marker')
      .attr('id','arrow-nbr').attr('viewBox','0 -5 10 10').attr('refX',22).attr('refY',0)
      .attr('markerWidth',6).attr('markerHeight',6).attr('orient','auto')
      .append('path').attr('d','M0,-5L10,0L0,5').attr('fill','#4a5568');

    const sim = d3.forceSimulation(nodes)
      .force('link', d3.forceLink(links).id(d => d.id).distance(120).strength(.8))
      .force('charge', d3.forceManyBody().strength(-400))
      .force('center', d3.forceCenter(W/2, H/2))
      .force('collision', d3.forceCollide(40));

    const colorOf = t => ({ center: COLORS.class, property: COLORS.property, range: '#7c3aed', domain: COLORS.instance })[t] || '#888';

    const link = g.selectAll('.link').data(links).enter().append('line').attr('class','link')
      .attr('stroke', d => colorOf(d.type)).attr('stroke-width', 1.5).attr('stroke-opacity',.6)
      .attr('marker-end','url(#arrow-nbr)');

    const lLabel = g.selectAll('.ll').data(links).enter().append('text')
      .attr('font-size','8px').attr('fill','#64748b').text(d => d.label);

    const node = g.selectAll('.node').data(nodes).enter().append('g').attr('class','node')
      .style('cursor','pointer')
      .on('mouseover', (e, d) => showTooltip(e, d))
      .on('mousemove', moveTooltip)
      .on('mouseout', hideTooltip)
      .on('click', (e, d) => {
        if (d.type !== 'property') {
          window.setConceptFromInfo(d.id);
        } else {
          showInfo(d);
        }
      })
      .call(d3.drag()
        .on('start', (e, d) => { if (!e.active) sim.alphaTarget(.3).restart(); d.fx = d.x; d.fy = d.y; })
        .on('drag',  (e, d) => { d.fx = e.x; d.fy = e.y; })
        .on('end',   (e, d) => { if (!e.active) sim.alphaTarget(0); })
      );

    node.append('circle').attr('r', d => d.type === 'center' ? 22 : d.type === 'property' ? 14 : 16)
      .attr('fill', d => colorOf(d.type) + '22')
      .attr('stroke', d => colorOf(d.type)).attr('stroke-width', 2);

    node.append('text').attr('dy','0.31em').attr('text-anchor','middle')
      .attr('fill','#e2e8f0').attr('font-size', d => d.type === 'center' ? '10px' : '8px')
      .attr('pointer-events','none')
      .text(d => { const l = d.label || localName(d.id); return l.length > 12 ? l.slice(0,10)+'…' : l; });

    sim.on('tick', () => {
      link.attr('x1', d => d.source.x).attr('y1', d => d.source.y)
          .attr('x2', d => d.target.x).attr('y2', d => d.target.y);
      lLabel.attr('x', d => (d.source.x + d.target.x)/2).attr('y', d => (d.source.y + d.target.y)/2);
      node.attr('transform', d => `translate(${d.x},${d.y})`);
    });
  }

  /* ──────────────────────────────────────────
     VIZ 5 — SUNBURST
  ────────────────────────────────────────── */
  function drawSunburst(rawData) {
    if (!rawData || rawData.error) { showEmpty(true); showSvg(false); return; }

    setLegend([
      { color: COLORS.depths[0], label: 'Racine' },
      { color: COLORS.depths[1], label: 'Niveau 1' },
      { color: COLORS.depths[2], label: 'Niveau 2' },
      { color: COLORS.depths[3], label: 'Niveau 3+' },
    ]);

    const W = canvas.clientWidth, H = canvas.clientHeight;
    const radius = Math.min(W, H) / 2;

    const color = d3.scaleOrdinal(COLORS.depths);

    const arc = d3.arc()
      .startAngle(d => d.x0).endAngle(d => d.x1)
      .padAngle(d => Math.min((d.x1 - d.x0) / 2, .005))
      .padRadius(radius / 2).innerRadius(d => d.y0).outerRadius(d => d.y1 - 2);

    const partition = d3.partition().size([2 * Math.PI, radius]);

    const root = d3.hierarchy(rawData).sum(d => d.children?.length ? 0 : 1).sort((a, b) => b.value - a.value);
    partition(root);

    const zoom = d3.zoom().scaleExtent([.3, 4]).on('zoom', e => { g.attr('transform', e.transform); AppState.transform = e.transform; });
    svg.call(zoom);
    const g = svg.append('g');
    if (AppState.transform) svg.call(zoom.transform, AppState.transform);
    else g.attr('transform', `translate(${W/2},${H/2})`);

    // Current focus
    let current = root;

    function labelVisible(d) { return d.y1 <= radius && d.y0 >= 0 && (d.y1 - d.y0) * (d.x1 - d.x0) > .03; }
    function labelTransform(d) {
      const x = (d.x0 + d.x1) / 2 * 180 / Math.PI;
      const y = (d.y0 + d.y1) / 2;
      return `rotate(${x - 90}) translate(${y},0) rotate(${x < 180 ? 0 : 180})`;
    }

    const path = g.append('g').selectAll('path').data(root.descendants().filter(d => d.depth)).enter()
      .append('path').attr('d', arc)
      .attr('fill', d => { let n = d; while (n.depth > 1) n = n.parent; return color(n.data.label || n.data.uri); })
      .attr('fill-opacity', d => (d.ancestors().includes(current) || d === current) ? .9 : .3)
      .attr('stroke', '#0a0b0e').attr('stroke-width', .5)
      .style('cursor','pointer')
      .on('click', clicked)
      .on('mouseover', (e, d) => showTooltip(e, d))
      .on('mousemove', moveTooltip)
      .on('mouseout', hideTooltip);

    const label = g.append('g').attr('pointer-events','none').attr('text-anchor','middle')
      .selectAll('text').data(root.descendants().filter(d => d.depth && labelVisible(d))).enter()
      .append('text').attr('dy','0.35em').attr('transform', labelTransform)
      .attr('fill','#e2e8f0').attr('font-size','8px')
      .text(d => { const l = d.data.label || localName(d.data.uri); return l.length > 10 ? l.slice(0,8)+'…' : l; });

    // Center circle (back)
    const parent = g.append('circle').attr('r', root.y0 || 40)
      .attr('fill','transparent').attr('pointer-events','all').style('cursor','pointer')
      .on('click', () => clicked(null, root));

    // Center label
    g.append('text').attr('text-anchor','middle').attr('dy','0.35em')
      .attr('fill','#64748b').attr('font-size','9px').text('← retour');

    function clicked(e, p) {
      current = p;
      parent.datum(p.parent || root);
      root.each(d => d.target = {
        x0: Math.max(0, Math.min(1, (d.x0 - p.x0) / (p.x1 - p.x0))) * 2 * Math.PI,
        x1: Math.max(0, Math.min(1, (d.x1 - p.x0) / (p.x1 - p.x0))) * 2 * Math.PI,
        y0: Math.max(0, d.y0 - p.depth),
        y1: Math.max(0, d.y1 - p.depth),
      });

      const t = g.transition().duration(600);
      path.transition(t)
        .tween('data', d => { const i = d3.interpolate(d.current, d.target); return t => d.current = i(t); })
        .attr('fill-opacity', d => (d.ancestors().includes(current) || d === current) ? .9 : .3)
        .attrTween('d', d => () => arc(d.current));

      label.transition(t)
        .attr('fill-opacity', d => +labelVisible(d.target))
        .attrTween('transform', d => () => labelTransform(d.current));

      // Update AppState on click-in
      if (p.data.uri) { AppState.concept = p.data.uri; AppState.conceptLabel = p.data.label; updateHeader(); }
    }

    root.each(d => d.current = d);
  }

  /* ──────────────────────────────────────────
     VIZ 6 — COMBINED
  ────────────────────────────────────────── */
  function drawCombined(rawData) {
    if (!rawData || rawData.error) { showEmpty(true); showSvg(false); return; }

    setLegend([
      { color: COLORS.class,    label: 'Classe (héritage)' },
      { color: COLORS.property, label: 'Propriété domaine' },
      { color: '#7c3aed',       label: 'Propriété codomaine' },
      { color: '#10b981',       label: 'Chaîne de propriété', type:'line' },
    ]);

    const W = canvas.clientWidth, H = canvas.clientHeight;

    const zoom = d3.zoom().scaleExtent([.05, 4]).on('zoom', e => { g.attr('transform', e.transform); AppState.transform = e.transform; });
    svg.call(zoom);
    const g = svg.append('g');
    if (AppState.transform) svg.call(zoom.transform, AppState.transform);
    else g.attr('transform', `translate(${W/2 - 100},30)`);

    // ── Section A: Hierarchy tree (left-center)
    if (rawData.hierarchy) {
      const root = d3.hierarchy(rawData.hierarchy);
      const treeLayout = d3.tree().nodeSize([120, 60]);
      treeLayout(root);

      g.selectAll('.link-hier').data(root.links()).enter().append('path').attr('class','link')
        .attr('stroke','#14532d').attr('fill','none').attr('stroke-width',1.5)
        .attr('d', d => `M${d.source.x},${d.source.y} C${d.source.x},${(d.source.y+d.target.y)/2} ${d.target.x},${(d.source.y+d.target.y)/2} ${d.target.x},${d.target.y}`);

      g.selectAll('.node-hier').data(root.descendants()).enter().append('g')
        .attr('transform', d => `translate(${d.x},${d.y})`)
        .style('cursor','pointer')
        .on('click', (e, d) => { AppState.concept = d.data.uri; AppState.conceptLabel = d.data.label; updateHeader(); showInfo(d); })
        .on('mouseover', (e, d) => showTooltip(e, d))
        .on('mousemove', moveTooltip).on('mouseout', hideTooltip)
        .call(sel => {
          sel.append('circle').attr('r', d => d.depth===0?14:9).attr('fill', depthColor)
            .attr('stroke','#1a2535').attr('stroke-width',1.5);
          sel.append('text').attr('dy',-16).attr('text-anchor','middle').attr('fill','#e2e8f0').attr('font-size','9px')
            .text(d => { const l = d.data.label||localName(d.data.uri); return l.length>16?l.slice(0,14)+'…':l; });
        });
    }

    // ── Section B: Properties (right side, orbiting root)
    const props = rawData.properties || { domain: [], range: [] };
    const rootX = 0, rootY = 0;
    const propOffset = 220;

    props.domain.slice(0, 8).forEach((p, i) => {
      const angle = (-Math.PI/4) + (i * Math.PI/6);
      const px = rootX + propOffset * Math.cos(angle);
      const py = rootY + propOffset * Math.sin(angle);

      g.append('line').attr('x1',rootX).attr('y1',rootY).attr('x2',px).attr('y2',py)
        .attr('stroke',COLORS.property).attr('stroke-width',1).attr('stroke-dasharray','4,3').attr('stroke-opacity',.6);
      g.append('text').attr('x',(rootX+px)/2+4).attr('y',(rootY+py)/2)
        .attr('fill',COLORS.property).attr('font-size','8px').text(p.label||localName(p.uri));

      g.append('ellipse').attr('cx',px).attr('cy',py).attr('rx',36).attr('ry',14)
        .attr('fill',COLORS.property+'22').attr('stroke',COLORS.property).attr('stroke-width',1.5)
        .style('cursor','pointer')
        .on('mouseover', e => showTooltip(e, p)).on('mousemove', moveTooltip).on('mouseout', hideTooltip);
      g.append('text').attr('x',px).attr('y',py).attr('dy','0.35em').attr('text-anchor','middle')
        .attr('fill','#e2e8f0').attr('font-size','8px').attr('pointer-events','none')
        .text(() => { const l=p.label||localName(p.uri); return l.length>12?l.slice(0,10)+'…':l; });
    });

    props.range.slice(0, 6).forEach((p, i) => {
      const angle = (Math.PI*3/4) + (i * Math.PI/6);
      const px = rootX + propOffset * Math.cos(angle);
      const py = rootY + propOffset * Math.sin(angle);
      g.append('line').attr('x1',rootX).attr('y1',rootY).attr('x2',px).attr('y2',py)
        .attr('stroke','#7c3aed').attr('stroke-width',1).attr('stroke-dasharray','4,3').attr('stroke-opacity',.6);
      g.append('ellipse').attr('cx',px).attr('cy',py).attr('rx',36).attr('ry',14)
        .attr('fill','#7c3aed22').attr('stroke','#7c3aed').attr('stroke-width',1.5);
      g.append('text').attr('x',px).attr('y',py).attr('dy','0.35em').attr('text-anchor','middle')
        .attr('fill','#e2e8f0').attr('font-size','8px').attr('pointer-events','none')
        .text(() => { const l=p.label||localName(p.uri); return l.length>12?l.slice(0,10)+'…':l; });
    });

    // ── Section C: Property chain (bottom)
    if (rawData.chain) {
      const chainG = g.append('g').attr('transform','translate(0, 300)');
      chainG.append('text').attr('x',0).attr('y',-20).attr('fill',COLORS.instance).attr('font-size','10px').attr('font-weight','bold')
        .text('↓ Chaîne de propriété');

      function drawChain(node, cx, cy, depth) {
        chainG.append('circle').attr('cx',cx).attr('cy',cy).attr('r',12)
          .attr('fill',COLORS.instance+'22').attr('stroke',COLORS.instance).attr('stroke-width',1.5);
        chainG.append('text').attr('x',cx).attr('y',cy).attr('dy','0.35em').attr('text-anchor','middle')
          .attr('fill','#e2e8f0').attr('font-size','8px')
          .text(() => { const l=node.label||localName(node.uri); return l.length>10?l.slice(0,8)+'…':l; });

        if (node.children?.length) {
          const step = 120;
          node.children.forEach((child, i) => {
            const nx = cx + (i - (node.children.length-1)/2) * step;
            const ny = cy + 70;
            chainG.append('line').attr('x1',cx).attr('y1',cy+12).attr('x2',nx).attr('y2',ny-12)
              .attr('stroke',COLORS.instance).attr('stroke-width',1).attr('stroke-dasharray','4,2');
            drawChain(child, nx, ny, depth+1);
          });
        }
      }
      drawChain(rawData.chain, 0, 0, 0);
    }
  }

  /* ── Utilities ── */
  function centerView() {
    const W = canvas.clientWidth, H = canvas.clientHeight;
    svg.transition().duration(500).call(
      d3.zoom().transform,
      d3.zoomIdentity.translate(W/2, H/2).scale(1)
    );
    AppState.transform = null;
  }

  function expandAll(open) {
    AppState.expandedNodes.clear();
    if (open && AppState.lastData) {
      function markAll(n) {
        if (n.uri) AppState.expandedNodes.add(n.uri);
        (n.children||[]).forEach(markAll);
      }
      markAll(AppState.lastData);
    }
  }

  updateHeader();
}
