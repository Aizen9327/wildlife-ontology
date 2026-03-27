<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Novyra Graphis — <?= htmlspecialchars($_SESSION['owl_name'] ?? 'Ontologie') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@300;400;500&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
<script src="https://d3js.org/d3.v7.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--black:#0d0d0d;--dark:#141414;--dark2:#1a1a1a;--dark3:#212121;--border:#2a2a2a;--bord2:#333;--bord3:#3d3d3d;--green:#22c55e;--white:#fff;--text:#e5e5e5;--text2:#a3a3a3;--text3:#666;}
html,body{height:100vh;font-family:'DM Sans',sans-serif;font-weight:300;background:var(--black);color:var(--text);overflow:hidden;}
header{height:48px;display:flex;align-items:stretch;background:var(--dark);border-bottom:1px solid var(--border);flex-shrink:0;}
.logo{display:flex;align-items:center;gap:10px;padding:0 18px;border-right:1px solid var(--border);text-decoration:none;cursor:pointer;}
.logo-dot{width:7px;height:7px;background:var(--green);border-radius:50%;}
.logo-name{font-family:'DM Mono',monospace;font-size:.7rem;font-weight:500;letter-spacing:.12em;text-transform:uppercase;color:var(--white);}
.logo-prod{font-family:'DM Mono',monospace;font-size:.6rem;font-weight:300;letter-spacing:.08em;color:var(--text3);padding-left:10px;border-left:1px solid var(--border);margin-left:2px;}
.tabs{display:flex;align-items:stretch;}
.tab-btn{font-family:'DM Mono',monospace;font-size:.6rem;letter-spacing:.07em;text-transform:uppercase;padding:0 14px;border:none;border-right:1px solid var(--border);background:transparent;color:var(--text3);cursor:pointer;transition:color .12s,background .12s;position:relative;}
.tab-btn:hover{color:var(--text2);background:var(--dark2);}
.tab-btn.active{color:var(--green);background:var(--dark2);}
.tab-btn.active::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;background:var(--green);}
.controls{display:flex;align-items:center;gap:9px;padding:0 14px;margin-left:auto;border-left:1px solid var(--border);}
.cl{font-family:'DM Mono',monospace;font-size:.54rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text3);}
select,input[type=number]{font-family:'DM Mono',monospace;font-size:.64rem;color:var(--text);background:var(--dark3);border:1px solid var(--bord2);padding:4px 7px;border-radius:3px;outline:none;}
select:focus,input:focus{border-color:var(--green);}select{cursor:pointer;max-width:150px;}input[type=number]{width:50px;}option{background:var(--dark3);}
.btn{font-family:'DM Mono',monospace;font-size:.56rem;letter-spacing:.07em;text-transform:uppercase;padding:5px 10px;border:1px solid var(--bord2);background:transparent;color:var(--text2);cursor:pointer;border-radius:3px;transition:all .12s;white-space:nowrap;}
.btn:hover{border-color:var(--green);color:var(--green);}
#fbadge{font-family:'DM Mono',monospace;font-size:.56rem;letter-spacing:.06em;color:var(--green);border:1px solid rgba(34,197,94,.3);background:rgba(34,197,94,.06);padding:3px 8px;border-radius:20px;white-space:nowrap;max-width:160px;overflow:hidden;text-overflow:ellipsis;}
.app-body{display:flex;height:calc(100vh - 48px);overflow:hidden;}
.sidebar{width:190px;background:var(--dark);border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden;flex-shrink:0;}
.sb-block{display:flex;flex-direction:column;overflow:hidden;border-bottom:1px solid var(--border);}
.sb-block:last-child{flex:1;border-bottom:none;}
.sb-head{font-family:'DM Mono',monospace;font-size:.52rem;letter-spacing:.16em;text-transform:uppercase;color:var(--text3);padding:7px 11px 5px;border-bottom:1px solid var(--border);flex-shrink:0;background:var(--dark2);}
#search-input{margin:5px 6px;width:calc(100% - 12px);font-family:'DM Mono',monospace;font-size:.64rem;color:var(--text);background:var(--dark3);border:1px solid var(--border);padding:4px 7px;border-radius:3px;outline:none;}
#search-input::placeholder{color:var(--text3);}#search-input:focus{border-color:var(--green);}
#concept-list,#property-list{list-style:none;overflow-y:auto;flex:1;padding:2px 0;}
#concept-list li,#property-list li{padding:5px 11px;cursor:pointer;font-size:.73rem;font-weight:300;color:var(--text2);transition:background .1s,color .1s;border-left:2px solid transparent;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.5;}
#concept-list li:hover,#property-list li:hover{background:var(--dark2);color:var(--text);}
#concept-list li.selected,#property-list li.selected{color:var(--green);border-left-color:var(--green);background:rgba(34,197,94,.06);font-weight:400;}
.badge{font-family:'DM Mono',monospace;font-size:.46rem;padding:1px 4px;border-radius:2px;margin-left:4px;vertical-align:middle;}
.badge.obj{background:rgba(34,197,94,.12);color:var(--green);}.badge.dat{background:rgba(163,163,163,.1);color:var(--text3);}
#canvas{flex:1;position:relative;overflow:hidden;background:var(--black);}
#canvas svg{width:100%;height:100%;}
#info-panel{width:195px;background:var(--dark);border-left:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden;flex-shrink:0;}
.ip-head{font-family:'DM Mono',monospace;font-size:.52rem;letter-spacing:.16em;text-transform:uppercase;color:var(--text3);padding:7px 11px 5px;border-bottom:1px solid var(--border);flex-shrink:0;background:var(--dark2);}
.ip-scroll{overflow-y:auto;flex:1;padding:10px 11px;display:flex;flex-direction:column;gap:6px;}
.ip-name{font-family:'DM Mono',monospace;font-size:.78rem;font-weight:500;color:var(--white);word-break:break-word;line-height:1.3;}
.ip-uri{font-family:'DM Mono',monospace;font-size:.52rem;color:var(--text3);word-break:break-all;line-height:1.4;}
.ip-comment{font-size:.72rem;color:var(--text2);line-height:1.5;font-style:italic;}
.ip-lbl{font-family:'DM Mono',monospace;font-size:.5rem;letter-spacing:.12em;text-transform:uppercase;color:var(--text3);margin-top:4px;}
.prop-row{padding:5px 7px;background:var(--dark2);border:1px solid var(--border);border-radius:3px;}
.pr-name{font-family:'DM Mono',monospace;font-size:.62rem;font-weight:500;color:var(--green);}
.pr-range{font-size:.6rem;color:var(--text3);margin-top:1px;}
.sub-row{padding:4px 7px;background:var(--dark2);border:1px solid var(--border);border-radius:3px;cursor:pointer;font-size:.68rem;color:var(--text2);}
.sub-row:hover{color:var(--green);border-color:var(--green);}
.dis-row{padding:4px 7px;background:rgba(239,68,68,.05);border:1px solid rgba(239,68,68,.2);border-radius:3px;font-size:.62rem;color:#ef4444;}
.rest-row{padding:4px 7px;background:var(--dark2);border:1px solid var(--border);border-radius:3px;font-size:.62rem;color:var(--text3);}
.view-row{display:flex;flex-direction:column;gap:2px;}
.view-pill{font-family:'DM Mono',monospace;font-size:.54rem;letter-spacing:.06em;text-transform:uppercase;padding:4px 7px;border:1px solid var(--border);background:transparent;color:var(--text3);cursor:pointer;border-radius:3px;transition:all .12s;text-align:left;}
.view-pill:hover{border-color:var(--green);color:var(--green);background:rgba(34,197,94,.05);}
.link{fill:none;stroke:var(--bord3);stroke-width:.8px;}
.node-label{font-family:'DM Sans',sans-serif;font-size:10px;font-weight:300;fill:var(--text2);pointer-events:none;text-anchor:middle;dominant-baseline:middle;}
#tooltip{position:absolute;background:var(--dark2);border:1px solid var(--bord2);border-radius:4px;padding:8px 11px;pointer-events:none;opacity:0;transition:opacity .1s;z-index:999;max-width:220px;box-shadow:0 4px 16px rgba(0,0,0,.4);}
#tooltip.on{opacity:1;}
#tooltip strong{display:block;font-family:'DM Mono',monospace;font-size:.7rem;font-weight:500;color:var(--white);margin-bottom:2px;}
#tooltip small{font-size:.64rem;color:var(--text3);font-style:italic;line-height:1.4;display:block;}
#loading{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(13,13,13,.9);z-index:500;flex-direction:column;gap:12px;}
#loading.hidden{display:none;}
.spinner{width:18px;height:18px;border:1.5px solid var(--bord2);border-top-color:var(--green);border-radius:50%;animation:spin .75s linear infinite;}
.loading-lbl{font-family:'DM Mono',monospace;font-size:.58rem;letter-spacing:.14em;text-transform:uppercase;color:var(--text3);}
@keyframes spin{to{transform:rotate(360deg);}}
#legend{position:absolute;bottom:14px;left:14px;background:rgba(20,20,20,.92);border:1px solid var(--border);border-radius:4px;padding:8px 12px;display:flex;flex-direction:column;gap:5px;}
.leg-item{display:flex;align-items:center;gap:7px;font-family:'DM Mono',monospace;font-size:.58rem;color:var(--text2);}
.leg-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0;}
::-webkit-scrollbar{width:3px;}::-webkit-scrollbar-track{background:transparent;}::-webkit-scrollbar-thumb{background:var(--bord2);border-radius:2px;}
</style>
</head>
<body>
<header>
  <a class="logo" href="index.php?route=landing">
    <div class="logo-dot"></div>
    <span class="logo-name">Novyra</span>
    <span class="logo-prod">Graphis</span>
  </a>
  <div class="tabs">
    <button class="tab-btn active" data-view="radial">Radiale</button>
    <button class="tab-btn" data-view="collapsible">Arbre</button>
    <button class="tab-btn" data-view="packing">Cercles</button>
    <button class="tab-btn" data-view="sunburst">Sunburst</button>
    <button class="tab-btn" data-view="combined">Combinée</button>
  </div>
  <div class="controls">
    <span id="fbadge"><?= htmlspecialchars($_SESSION['owl_name'] ?? '') ?></span>
    <span class="cl">Profondeur</span>
    <input type="number" id="depth-input" value="6" min="1" max="15"/>
    <span class="cl">Propriété</span>
    <select id="prop-select"><option value="">— sélectionner —</option></select>
    <button class="btn" onclick="resetApp()">Nouvelle ontologie</button>
  </div>
</header>

<div class="app-body">
  <div class="sidebar">
    <div class="sb-block" style="flex:1;overflow:hidden;display:flex;flex-direction:column;">
      <div class="sb-head">Concepts</div>
      <input type="text" id="search-input" placeholder="Filtrer..."/>
      <ul id="concept-list"></ul>
    </div>
    <div class="sb-block" style="max-height:36%;display:flex;flex-direction:column;">
      <div class="sb-head">Propriétés</div>
      <ul id="property-list"></ul>
    </div>
  </div>
  <div id="canvas">
    <div id="loading"><div class="spinner"></div><span class="loading-lbl">Chargement</span></div>
    <svg id="main-svg"></svg>
    <div id="tooltip"></div>
    <div id="legend"></div>
  </div>
  <div id="info-panel">
    <div class="ip-head">Sélection</div>
    <div class="ip-scroll"><div style="color:var(--text3);font-size:.72rem;font-style:italic;">Cliquez sur un concept</div></div>
  </div>
</div>

<script>
const S={view:'radial',concept:null,property:null,depth:6,classes:[],properties:[],relations:[],tree:null,prevState:null};
const COLS=['#22c55e','#4ade80','#86efac','#a3a3a3','#737373','#16a34a','#6ee7b7','#d4d4d4','#bbf7d0','#525252'];
const col=d=>COLS[d%COLS.length];

async function api(action,params={}){
  const qs=Object.entries(params).map(([k,v])=>`${k}=${encodeURIComponent(v)}`).join('&');
  const r=await fetch('index.php?route=api&action='+action+(qs?'&'+qs:''));
  if(!r.ok)throw new Error('API '+r.status);
  return r.json();
}

async function init(){
  loading(true);
  try{
    const d=await api('fullData');
    if(d.error==='no_file'){window.location.href='index.php?route=landing';return;}
    S.classes=d.classes||[];S.properties=d.properties||[];S.relations=d.relations||[];S.tree=d.tree||null;
    buildSidebar();buildPropSelect();
    if(S.classes[0])await selectConcept(S.classes[0].id,false);
  }catch(e){console.error(e);}
  loading(false);
}

function buildSidebar(f=''){
  const ul=document.getElementById('concept-list');ul.innerHTML='';
  S.classes.filter(c=>c.label.toLowerCase().includes(f.toLowerCase())).forEach(c=>{
    const li=document.createElement('li');li.textContent=c.label;li.title=c.comment||c.id;
    if(c.id===S.concept)li.classList.add('selected');li.onclick=()=>selectConcept(c.id);ul.appendChild(li);
  });
  const ul2=document.getElementById('property-list');ul2.innerHTML='';
  S.properties.forEach(p=>{
    const li=document.createElement('li');li.textContent=p.label;
    const b=document.createElement('span');b.className='badge '+(p.type==='object'?'obj':'dat');b.textContent=p.type==='object'?'obj':'dat';
    li.appendChild(b);li.title=p.id;if(p.id===S.property)li.classList.add('selected');li.onclick=()=>selectProp(p.id);ul2.appendChild(li);
  });
}

function buildPropSelect(){
  const sel=document.getElementById('prop-select');sel.innerHTML='<option value="">— sélectionner —</option>';
  S.properties.filter(p=>p.type==='object').forEach(p=>{const o=document.createElement('option');o.value=p.id;o.textContent=p.label;sel.appendChild(o);});
}

async function selectConcept(uri,save=true){
  if(save)S.prevState={view:S.view,concept:S.concept};
  S.concept=uri;buildSidebar(document.getElementById('search-input').value);
  showInfo(S.classes.find(c=>c.id===uri));await loadRender();
}

function selectProp(uri){S.property=uri;document.getElementById('prop-select').value=uri;buildSidebar();if(S.view==='combined')loadRender();}

async function loadRender(){
  loading(true);
  try{
    if(S.view==='combined'){
      const d=await api('combined',{concept:S.concept||'',depth:S.depth,property:S.property||''});
      S.tree=d.hierarchy;renderCombined(d);
    }else{
      const t=S.concept?await api('hierarchy',{concept:S.concept,depth:S.depth}):await api('hierarchy',{depth:S.depth});
      S.tree=t;render(t);
    }
  }catch(e){console.error(e);}
  loading(false);
}

function render(t){switch(S.view){case'radial':renderRadial(t);break;case'collapsible':renderCollapsible(t);break;case'packing':renderPacking(t);break;case'sunburst':renderSunburst(t);break;default:renderRadial(t);}}
function switchView(v){S.prevState={view:S.view,concept:S.concept};S.view=v;document.querySelectorAll('.tab-btn').forEach(b=>b.classList.toggle('active',b.dataset.view===v));loadRender();}
async function resetApp(){await api('reset');window.location.href='index.php?route=landing';}

const tip=document.getElementById('tooltip');
function showTip(ev,h){tip.innerHTML=h;tip.classList.add('on');mvTip(ev);}
function mvTip(ev){const r=document.getElementById('canvas').getBoundingClientRect();let x=ev.clientX-r.left+14,y=ev.clientY-r.top+14;if(x+225>r.width)x-=235;if(y+90>r.height)y-=80;tip.style.left=x+'px';tip.style.top=y+'px';}
function hideTip(){tip.classList.remove('on');}

function showInfo(cls){
  const sc=document.querySelector('.ip-scroll');
  if(!cls){sc.innerHTML='<div style="color:var(--text3);font-style:italic;font-size:.72rem">Aucune sélection</div>';return;}
  const sub=S.relations.filter(r=>r.parent===cls.id);
  const props=S.properties.filter(p=>p.domain===cls.id);
  const ln=u=>{if(!u)return'';const h=u.lastIndexOf('#');return h>=0?u.slice(h+1):u;};
  sc.innerHTML=`
    <div class="ip-name">${cls.label}</div>
    ${cls.comment?`<div class="ip-comment">${cls.comment}</div>`:''}
    <div class="ip-uri">${cls.id}</div>
    ${sub.length>0?`<div class="ip-lbl">Sous-classes (${sub.length})</div>${sub.map(r=>`<div class="sub-row" onclick="selectConcept('${r.child}')">↳ ${ln(r.child)}</div>`).join('')}`:''}
    ${props.length>0?`<div class="ip-lbl">Propriétés (${props.length})</div>${props.map(p=>`<div class="prop-row"><div class="pr-name">${p.label} <span class="badge ${p.type==='object'?'obj':'dat'}">${p.type}</span></div>${p.range?`<div class="pr-range">→ ${ln(p.range)}</div>`:''}</div>`).join('')}`:''}
    <div class="ip-lbl" style="margin-top:6px">Changer de vue</div>
    <div class="view-row">${['radial','collapsible','packing','sunburst','combined'].map(v=>`<button class="view-pill" onclick="switchView('${v}')">${v}</button>`).join('')}</div>`;

  api('conceptDetails',{concept:cls.id}).then(info=>{
    if(!info)return;
    let extra='';
    if(info.restrictions?.length>0){extra+=`<div class="ip-lbl">Restrictions</div>`;info.restrictions.forEach(r=>{extra+=`<div class="rest-row">${r.some?'∃':'∀'} ${r.property} ${r.some||r.all||''}</div>`;});}
    if(info.disjoint?.length>0){extra+=`<div class="ip-lbl">Disjoint avec</div>`;info.disjoint.forEach(d=>{extra+=`<div class="dis-row">⊥ ${d.label}</div>`;});}
    if(extra){const vr=sc.querySelector('.view-row');if(vr)vr.insertAdjacentHTML('beforebegin',extra);}
  }).catch(()=>{});
}

function legend(items){document.getElementById('legend').innerHTML=items.map(i=>`<div class="leg-item"><div class="leg-dot" style="background:${i.c}"></div>${i.l}</div>`).join('');}
function loading(b){document.getElementById('loading').classList.toggle('hidden',!b);}
const ln=u=>{if(!u)return'';const h=u.lastIndexOf('#');return h>=0?u.slice(h+1):u;};

function renderRadial(data){
  const svg=d3.select('#main-svg');svg.selectAll('*').remove();
  const W=svg.node().clientWidth||800,H=svg.node().clientHeight||600;
  const g=svg.append('g').attr('transform',`translate(${W/2},${H/2})`);
  svg.call(d3.zoom().scaleExtent([0.1,10]).on('zoom',ev=>g.attr('transform',ev.transform.translate(W/2,H/2))));
  const root=d3.hierarchy(data),radius=Math.min(W,H)/2*.82;
  d3.tree().size([2*Math.PI,radius]).separation((a,b)=>(a.parent===b.parent?1:2)/a.depth)(root);
  g.selectAll('.link').data(root.links()).join('path').attr('class','link').attr('d',d3.linkRadial().angle(d=>d.x).radius(d=>d.y)).attr('stroke',d=>col(d.target.depth)).attr('stroke-opacity',.25);
  const node=g.selectAll('.node').data(root.descendants()).join('g').attr('class','node').attr('transform',d=>`rotate(${d.x*180/Math.PI-90}) translate(${d.y},0)`).style('cursor','pointer')
    .on('mouseover',(ev,d)=>showTip(ev,`<strong>${d.data.name}</strong><small>${d.data.comment||''}</small>`)).on('mousemove',mvTip).on('mouseout',hideTip).on('click',(ev,d)=>{ev.stopPropagation();selectConcept(d.data.id);});
  node.append('circle').attr('r',d=>d.depth===0?10:Math.max(3,7-d.depth*.8)).attr('fill',d=>d.depth===0?col(0):'transparent').attr('stroke',d=>col(d.depth)).attr('stroke-width',1);
  node.append('text').attr('class','node-label').attr('dy','0.31em').attr('x',d=>d.x<Math.PI===!d.children?12:-12).attr('text-anchor',d=>d.x<Math.PI===!d.children?'start':'end').attr('transform',d=>d.x>=Math.PI?'rotate(180)':null).text(d=>d.data.name).attr('fill',d=>col(d.depth)).attr('font-weight',d=>d.depth===0?'500':'300');
  legend([{c:col(0),l:'Racine'},{c:col(1),l:'Niveau 1'},{c:col(2),l:'Niveau 2'},{c:col(3),l:'Niveau 3+'}]);
}

function renderCollapsible(data){
  const svg=d3.select('#main-svg');svg.selectAll('*').remove();
  const W=svg.node().clientWidth||800,H=svg.node().clientHeight||600;
  const m={top:20,right:160,bottom:20,left:60},iW=W-m.left-m.right,iH=H-m.top-m.bottom;
  const g=svg.append('g').attr('transform',`translate(${m.left},${m.top})`);
  svg.call(d3.zoom().scaleExtent([0.1,10]).on('zoom',ev=>g.attr('transform',ev.transform.translate(m.left,m.top))));
  const root=d3.hierarchy(data);root.each(d=>{if(d.depth>1&&d.children){d._children=d.children;d.children=null;}});
  function update(src){
    const layout=d3.tree().nodeSize([17,iW/(root.height+1)]);layout(root);
    const nodes=root.descendants(),links=root.links();
    const node=g.selectAll('.node').data(nodes,d=>d.data.id);
    const ne=node.enter().append('g').attr('class','node').attr('transform',`translate(${src.y0??0},${src.x0??iH/2})`).style('cursor','pointer')
      .on('click',(ev,d)=>{ev.stopPropagation();if(d.children){d._children=d.children;d.children=null;}else{d.children=d._children;d._children=null;}update(d);if(!d.children&&!d._children)selectConcept(d.data.id);})
      .on('mouseover',(ev,d)=>showTip(ev,`<strong>${d.data.name}</strong><small>${d.data.comment||''}</small>`)).on('mousemove',mvTip).on('mouseout',hideTip);
    ne.append('circle').attr('r',0).attr('fill',d=>d._children?col(d.depth):'transparent').attr('stroke',d=>col(d.depth)).attr('stroke-width',1);
    ne.append('text').attr('class','node-label').attr('dy','0.31em').attr('x',d=>d.children||d._children?-10:10).attr('text-anchor',d=>d.children||d._children?'end':'start').text(d=>d.data.name).attr('fill',d=>col(d.depth)).attr('font-weight','300');
    ne.merge(node).transition().duration(240).attr('transform',d=>`translate(${d.y},${d.x})`);
    ne.merge(node).select('circle').transition().duration(240).attr('r',d=>d.depth===0?7:4);
    node.exit().transition().duration(240).attr('transform',`translate(${src.y},${src.x})`).remove();
    const link=g.selectAll('.link').data(links,d=>d.target.data.id);
    const le=link.enter().insert('path','.node').attr('class','link').attr('d',()=>{const o={x:src.x0??iH/2,y:src.y0??0};return d3.linkHorizontal().x(d=>d.y).y(d=>d.x)({source:o,target:o});});
    le.merge(link).transition().duration(240).attr('d',d3.linkHorizontal().x(d=>d.y).y(d=>d.x)).attr('stroke',d=>col(d.target.depth)).attr('stroke-opacity',.25);
    link.exit().transition().duration(240).attr('d',()=>{const o={x:src.x,y:src.y};return d3.linkHorizontal().x(d=>d.y).y(d=>d.x)({source:o,target:o});}).remove();
    nodes.forEach(d=>{d.x0=d.x;d.y0=d.y;});
  }
  root.x0=iH/2;root.y0=0;update(root);
  legend([{c:col(0),l:'Racine'},{c:col(1),l:'Fils'},{c:col(2),l:'Petits-fils'}]);
}

function renderPacking(data){
  const svg=d3.select('#main-svg');svg.selectAll('*').remove();
  const W=svg.node().clientWidth||800,H=svg.node().clientHeight||600;
  const pack=d3.pack().size([W,H]).padding(4);
  const root=pack(d3.hierarchy(data).sum(()=>1).sort((a,b)=>b.value-a.value));
  let focus=root;const g=svg.append('g');
  g.selectAll('circle').data(root.descendants()).join('circle').attr('cx',d=>d.x).attr('cy',d=>d.y).attr('r',d=>d.r).attr('fill',d=>!d.parent?'transparent':d.children?col(d.depth):'transparent').attr('fill-opacity',.08).attr('stroke',d=>col(d.depth)).attr('stroke-width',d=>d.children?.7:.5).attr('stroke-opacity',.5).style('cursor','pointer')
    .on('mouseover',(ev,d)=>showTip(ev,`<strong>${d.data.name}</strong><small>${d.descendants().length-1} sous-concept(s)</small>`)).on('mousemove',mvTip).on('mouseout',hideTip).on('click',(ev,d)=>{ev.stopPropagation();focus!==d?zoom(d):selectConcept(d.data.id);});
  const label=g.selectAll('text').data(root.descendants().filter(d=>d.r>14)).join('text').attr('class','node-label').attr('x',d=>d.x).attr('y',d=>d.y).attr('fill',d=>col(d.depth)).text(d=>d.data.name).attr('font-size',d=>Math.min(10,d.r/2.8)+'px').attr('opacity',d=>d.parent===root?1:0);
  function zoom(d){focus=d;const k=Math.min(W,H)/(d.r*2+12);g.transition().duration(500).attr('transform',`translate(${W/2-d.x*k},${H/2-d.y*k}) scale(${k})`);label.transition().duration(500).attr('opacity',n=>n.parent===d?1:n===d?1:0);}
  svg.on('click',()=>{focus=root;zoom(root);});
  legend([{c:col(0),l:'Top'},{c:col(1),l:'Sous-concept'},{c:col(2),l:'Feuille'}]);
}

function renderSunburst(data){
  const svg=d3.select('#main-svg');svg.selectAll('*').remove();
  const W=svg.node().clientWidth||800,H=svg.node().clientHeight||600;
  const r=Math.min(W,H)/2-10,g=svg.append('g').attr('transform',`translate(${W/2},${H/2})`);
  const part=d3.partition().size([2*Math.PI,r]);
  const root=part(d3.hierarchy(data).sum(()=>1).sort((a,b)=>b.value-a.value));
  const arc=d3.arc().startAngle(d=>d.x0).endAngle(d=>d.x1).innerRadius(d=>d.y0).outerRadius(d=>d.y1-1);
  root.each(d=>d.current=d);
  const path=g.selectAll('path').data(root.descendants().filter(d=>d.depth)).join('path').attr('d',d=>arc(d.current)).attr('fill',d=>{let c=d;while(c.depth>1)c=c.parent;return col(root.children?.indexOf(c)??0);}).attr('fill-opacity',d=>Math.max(.08,.7-d.depth*.1)).attr('stroke','var(--black)').attr('stroke-width',.5).style('cursor','pointer')
    .on('mouseover',(ev,d)=>showTip(ev,`<strong>${d.data.name}</strong><small>${d.data.comment||''}</small>`)).on('mousemove',mvTip).on('mouseout',hideTip).on('click',(ev,d)=>{ev.stopPropagation();zs(d);selectConcept(d.data.id);});
  g.append('text').attr('text-anchor','middle').attr('dy','0.35em').attr('fill','var(--text)').attr('font-size','10px').attr('font-family',"'DM Mono',monospace").attr('font-weight','500').text(root.data.name);
  function zs(p){root.each(d=>d.target={x0:Math.max(0,Math.min(1,(d.x0-p.x0)/(p.x1-p.x0)))*2*Math.PI,x1:Math.max(0,Math.min(1,(d.x1-p.x0)/(p.x1-p.x0)))*2*Math.PI,y0:Math.max(0,d.y0-p.y0),y1:Math.max(0,d.y1-p.y0)});const t=g.transition().duration(600);path.transition(t).tween('data',d=>{const i=d3.interpolate(d.current,d.target);return t=>d.current=i(t);}).attrTween('d',d=>()=>arc(d.current));root.each(d=>d.current=d.target);}
  legend(root.children?.slice(0,5).map((c,i)=>({c:col(i),l:c.data.name}))||[]);
}

function renderCombined(data){
  const svg=d3.select('#main-svg');svg.selectAll('*').remove();
  const W=svg.node().clientWidth||800,H=svg.node().clientHeight||600,split=W*.62;
  svg.append('line').attr('x1',split).attr('y1',0).attr('x2',split).attr('y2',H).attr('stroke','var(--border)').attr('stroke-width',1);
  svg.append('text').text('Propriétés').attr('x',split+13).attr('y',20).attr('fill','var(--text3)').attr('font-family',"'DM Mono',monospace").attr('font-size',8.5).attr('letter-spacing','0.1em');
  const props=data.properties||[];
  if(!props.length)svg.append('text').text('Aucune propriété directe').attr('x',split+13).attr('y',44).attr('fill','var(--text3)').attr('font-size',11).attr('font-style','italic').attr('font-family',"'DM Sans',sans-serif");
  props.forEach((p,i)=>{
    const y=30+i*48,rw=W-split-20;
    const pg=svg.append('g').attr('transform',`translate(${split+10},${y})`).style('cursor','pointer').on('click',()=>selectProp(p.id));
    pg.append('rect').attr('width',rw).attr('height',40).attr('rx',3).attr('fill','var(--dark2)').attr('stroke','var(--border)').attr('stroke-width',.5);
    pg.append('text').text(ln(p.domain||'')).attr('x',7).attr('y',14).attr('fill',col(0)).attr('font-size',9.5).attr('font-family',"'DM Mono',monospace").attr('font-weight','500');
    pg.append('text').text(p.label).attr('x',rw/2).attr('y',14).attr('text-anchor','middle').attr('fill','var(--text3)').attr('font-size',8.5).attr('font-family',"'DM Mono',monospace");
    pg.append('text').text(ln(p.range||'')).attr('x',rw-7).attr('y',14).attr('text-anchor','end').attr('fill',col(2)).attr('font-size',9.5).attr('font-family',"'DM Mono',monospace").attr('font-weight','500');
    pg.append('line').attr('x1',Math.min(50,rw/4)).attr('y1',9).attr('x2',rw-Math.min(50,rw/4)).attr('y2',9).attr('stroke','var(--bord2)').attr('stroke-width',.5);
    pg.append('text').text(p.type).attr('x',7).attr('y',31).attr('fill','var(--text3)').attr('font-size',7.5).attr('font-family',"'DM Mono',monospace");
  });
  if(data.propertyChain){svg.append('text').text('Chaîne : '+data.propertyChain.name).attr('x',split+13).attr('y',34+props.length*48+6).attr('fill','var(--green)').attr('font-size',9.5).attr('font-family',"'DM Mono',monospace");}
  const tree=data.hierarchy;if(!tree)return;
  const root=d3.hierarchy(tree),radius=Math.min(split,H)/2*.82;
  d3.tree().size([2*Math.PI,radius]).separation((a,b)=>(a.parent===b.parent?1:2)/a.depth)(root);
  const g=svg.append('g').attr('transform',`translate(${split/2},${H/2})`);
  g.selectAll('.link').data(root.links()).join('path').attr('class','link').attr('d',d3.linkRadial().angle(d=>d.x).radius(d=>d.y)).attr('stroke',d=>col(d.target.depth)).attr('stroke-opacity',.25);
  const node=g.selectAll('.node').data(root.descendants()).join('g').attr('class','node').attr('transform',d=>`rotate(${d.x*180/Math.PI-90}) translate(${d.y},0)`).style('cursor','pointer')
    .on('mouseover',(ev,d)=>showTip(ev,`<strong>${d.data.name}</strong>`)).on('mousemove',mvTip).on('mouseout',hideTip).on('click',(ev,d)=>{ev.stopPropagation();selectConcept(d.data.id);});
  node.append('circle').attr('r',d=>d.depth===0?8:3.5).attr('fill',d=>d.depth===0?col(0):'transparent').attr('stroke',d=>col(d.depth)).attr('stroke-width',1);
  node.append('text').attr('class','node-label').attr('dy','0.31em').attr('x',d=>d.x<Math.PI===!d.children?10:-10).attr('text-anchor',d=>d.x<Math.PI===!d.children?'start':'end').attr('transform',d=>d.x>=Math.PI?'rotate(180)':null).text(d=>d.data.name).attr('fill',d=>col(d.depth)).attr('font-weight','300');
  legend([{c:col(0),l:'Héritage'},{c:'var(--bord2)',l:'Propriétés'}]);
}

document.querySelectorAll('.tab-btn').forEach(b=>b.addEventListener('click',()=>switchView(b.dataset.view)));
document.getElementById('search-input').addEventListener('input',e=>buildSidebar(e.target.value));
document.getElementById('depth-input').addEventListener('change',e=>{S.depth=parseInt(e.target.value)||6;loadRender();});
document.getElementById('prop-select').addEventListener('change',e=>selectProp(e.target.value));
document.addEventListener('keydown',e=>{
  if(e.key==='Backspace'&&e.target.tagName!=='INPUT'&&S.prevState?.concept){
    e.preventDefault();const p=S.prevState;S.prevState=null;S.view=p.view;S.concept=p.concept;
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.toggle('active',b.dataset.view===S.view));
    buildSidebar();showInfo(S.classes.find(c=>c.id===S.concept));loadRender();
  }
});

window.addEventListener('DOMContentLoaded',()=>init());
</script>
</body>
</html>
