<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Novyra Graphis — Ontology Visualization</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@300;400;500&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--black:#0d0d0d;--dark:#141414;--dark2:#1a1a1a;--border:#2a2a2a;--bord2:#333;--green:#22c55e;--white:#fff;--muted:#737373;--muted2:#525252;}
html,body{min-height:100%;font-family:'DM Sans',sans-serif;font-weight:300;background:var(--black);color:var(--white);}
nav{position:fixed;top:0;left:0;right:0;height:56px;display:flex;align-items:center;justify-content:space-between;padding:0 40px;border-bottom:1px solid var(--border);background:rgba(13,13,13,.96);z-index:100;}
.nav-logo{font-family:'DM Mono',monospace;font-weight:500;font-size:.88rem;letter-spacing:.12em;text-transform:uppercase;display:flex;align-items:center;gap:10px;}
.nav-dot{width:8px;height:8px;background:var(--green);border-radius:50%;}
.nav-tag{font-family:'DM Mono',monospace;font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);border:1px solid var(--bord2);padding:3px 10px;border-radius:20px;}
.hero{min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:80px 40px 60px;text-align:center;position:relative;}
.glow{position:absolute;top:15%;left:50%;transform:translateX(-50%);width:500px;height:500px;background:radial-gradient(circle,rgba(34,197,94,.07) 0%,transparent 70%);pointer-events:none;}
.eyebrow{font-family:'DM Mono',monospace;font-size:.65rem;letter-spacing:.2em;text-transform:uppercase;color:var(--green);margin-bottom:28px;display:flex;align-items:center;gap:10px;}
.eyebrow::before,.eyebrow::after{content:'';width:28px;height:1px;background:var(--green);opacity:.5;}
h1{font-size:clamp(2.6rem,5.5vw,4.8rem);font-weight:500;line-height:1.08;letter-spacing:-.025em;margin-bottom:12px;}
h1 span{color:var(--green);}
.subtitle{font-family:'DM Mono',monospace;font-size:.78rem;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);margin-bottom:32px;}
.desc{font-size:1rem;color:var(--muted);max-width:460px;line-height:1.75;margin-bottom:52px;}
.upload-box{width:100%;max-width:520px;border:1px solid var(--bord2);border-radius:8px;background:var(--dark);padding:36px;display:flex;flex-direction:column;align-items:center;gap:20px;transition:border-color .2s;}
.upload-box.over{border-color:var(--green);background:rgba(34,197,94,.03);}
.icon-wrap{width:48px;height:48px;border:1px solid var(--bord2);border-radius:8px;display:flex;align-items:center;justify-content:center;}
.icon-wrap svg{width:22px;height:22px;stroke:var(--muted);fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round;}
.upload-main{font-size:.9rem;font-weight:400;color:var(--white);}
.upload-hint{font-family:'DM Mono',monospace;font-size:.6rem;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-top:4px;}
.pick-btn{font-family:'DM Mono',monospace;font-size:.66rem;letter-spacing:.08em;text-transform:uppercase;padding:10px 22px;border:1px solid var(--bord2);border-radius:4px;background:transparent;color:var(--white);cursor:pointer;transition:all .15s;position:relative;display:inline-flex;align-items:center;}
.pick-btn:hover{border-color:var(--green);color:var(--green);}
.pick-btn input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;}
.formats{font-family:'DM Mono',monospace;font-size:.56rem;letter-spacing:.06em;text-transform:uppercase;color:var(--muted2);}
#prog{width:100%;max-width:520px;display:none;margin-top:6px;}
.prog-bar{height:1px;background:var(--border);margin-bottom:8px;}
.prog-fill{height:100%;background:var(--green);width:0%;transition:width .35s;}
.prog-txt{font-family:'DM Mono',monospace;font-size:.62rem;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);text-align:center;}
.prog-err{color:#ef4444;}
.features{padding:80px 40px;max-width:960px;margin:0 auto;}
.feat-label{font-family:'DM Mono',monospace;font-size:.58rem;letter-spacing:.2em;text-transform:uppercase;color:var(--muted);text-align:center;margin-bottom:40px;}
.feat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--border);border:1px solid var(--border);border-radius:6px;overflow:hidden;}
.feat-card{background:var(--dark);padding:28px 24px;}
.feat-num{font-family:'DM Mono',monospace;font-size:.56rem;letter-spacing:.1em;color:var(--green);margin-bottom:14px;}
.feat-name{font-size:.92rem;font-weight:400;margin-bottom:8px;}
.feat-desc{font-size:.78rem;color:var(--muted);line-height:1.6;}
footer{border-top:1px solid var(--border);padding:24px 40px;display:flex;align-items:center;justify-content:space-between;}
.foot-logo{font-family:'DM Mono',monospace;font-size:.66rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted2);display:flex;align-items:center;gap:7px;}
.foot-dot{width:6px;height:6px;background:var(--green);border-radius:50%;}
.foot-r{font-family:'DM Mono',monospace;font-size:.56rem;letter-spacing:.08em;text-transform:uppercase;color:var(--muted2);}
@media(max-width:650px){.feat-grid{grid-template-columns:1fr;}nav,footer{padding-left:20px;padding-right:20px;}.hero{padding:80px 20px 40px;}}
</style>
</head>
<body>
<nav>
  <div class="nav-logo"><div class="nav-dot"></div>Novyra</div>
  <div class="nav-tag">Graphis v1.0</div>
</nav>

<section class="hero">
  <div class="glow"></div>
  <div class="eyebrow">Novyra Graphis</div>
  <h1>Make your<br/>ontology <span>visible</span></h1>
  <p class="subtitle">Ontology Visualization Tool</p>
  <p class="desc">Chargez n'importe quel fichier OWL et explorez sa structure en temps réel — hiérarchies, propriétés, relations.</p>

  <div class="upload-box" id="drop">
    <div class="icon-wrap">
      <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
    </div>
    <div>
      <div class="upload-main">Déposez votre fichier OWL ici</div>
      <div class="upload-hint">ou cliquez pour sélectionner</div>
    </div>
    <label class="pick-btn">
      Sélectionner un fichier
      <input type="file" id="file-in" accept=".owl,.rdf,.xml,.ttl,.nt,.json"/>
    </label>
    <div class="formats">.owl &nbsp;·&nbsp; .rdf &nbsp;·&nbsp; .xml &nbsp;·&nbsp; .ttl &nbsp;·&nbsp; .nt &nbsp;·&nbsp; .json-ld</div>
  </div>

  <div id="prog">
    <div class="prog-bar"><div class="prog-fill" id="pfill"></div></div>
    <div class="prog-txt" id="ptxt">Chargement...</div>
  </div>
</section>

<section class="features">
  <div class="feat-label">Ce que Novyra Graphis offre</div>
  <div class="feat-grid">
    <div class="feat-card">
      <div class="feat-num">01</div>
      <div class="feat-name">5 modes de visualisation</div>
      <div class="feat-desc">Radiale, arbre collapsible, circle packing, sunburst, vue combinée. Passez de l'un à l'autre sans perdre votre contexte.</div>
    </div>
    <div class="feat-card">
      <div class="feat-num">02</div>
      <div class="feat-name">Compatible tout fichier OWL</div>
      <div class="feat-desc">RDF/XML, OWL, Turtle, N-Triples, JSON-LD. Détection automatique du format — aucune configuration requise.</div>
    </div>
    <div class="feat-card">
      <div class="feat-num">03</div>
      <div class="feat-name">Navigation interactive</div>
      <div class="feat-desc">Cliquez sur un concept, zoomez, explorez les propriétés, restrictions et relations disjointes en temps réel.</div>
    </div>
  </div>
</section>

<footer>
  <div class="foot-logo"><div class="foot-dot"></div>Novyra</div>
  <div class="foot-r">Graphis v1.0 &nbsp;·&nbsp; <?= date('Y') ?></div>
</footer>

<script>
const drop=document.getElementById('drop'),fin=document.getElementById('file-in');
const prog=document.getElementById('prog'),pfill=document.getElementById('pfill'),ptxt=document.getElementById('ptxt');

function go(file){
  if(!file)return;
  const ext=file.name.split('.').pop().toLowerCase();
  if(!['owl','rdf','xml','ttl','nt','json'].includes(ext)){err('Format non supporté');return;}
  prog.style.display='block';pfill.style.width='20%';ptxt.textContent='Envoi en cours...';ptxt.className='prog-txt';
  const fd=new FormData();fd.append('owl_file',file);
  pfill.style.width='65%';
  fetch('index.php?route=upload',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(j=>{
      if(j.success){
        pfill.style.width='100%';
        ptxt.textContent=j.file+' — chargé avec succès';
        setTimeout(()=>{window.location.href='index.php?route=app';},400);
      }else err(j.error||'Erreur');
    }).catch(()=>err('Erreur réseau'));
}
function err(m){pfill.style.width='0%';ptxt.textContent=m;ptxt.className='prog-txt prog-err';}
fin.addEventListener('change',e=>go(e.target.files[0]));
drop.addEventListener('dragover',e=>{e.preventDefault();drop.classList.add('over');});
drop.addEventListener('dragleave',()=>drop.classList.remove('over'));
drop.addEventListener('drop',e=>{e.preventDefault();drop.classList.remove('over');go(e.dataTransfer.files[0]);});
</script>
</body>
</html>
