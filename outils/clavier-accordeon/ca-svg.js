/* ╔══════════════════════════════════════════════════════════════╗
   ║  ca-svg.js — Shared SVG keyboard rendering library          ║
   ║  Single source of truth for ALL keyboard SVG output.        ║
   ║  Used by: admin tool, stagiaires viewer, inscription forms  ║
   ║  Exports (globals): buildSVG_Plan, buildSVG_Droite,         ║
   ║    buildSVG_Gauche, buildSVG_Combined, presetToSVGData      ║
   ╚══════════════════════════════════════════════════════════════╝ */
/* ── i18n shim ─────────────────────────────────────────────────────────────
   ca-svg.js uses t(key) for labels (provided by the admin tool's LANGS
   object).  When loaded standalone (inscription form, WP Optimize bundle)
   the global t() does not exist, causing ReferenceError inside buildSVG_*
   and an empty SVG preview.  This shim sets window.t to French defaults
   when no t() is available, without touching the admin tool's own t().
   Using window.t avoids var-hoisting ambiguity inside IIFE bundles.
   ────────────────────────────────────────────────────────────────────────── */
if (typeof window.t !== 'function') {
  window.t = function(k) {
    var _L = {
      basses:'Basses', accords:'Accords', terzetto:'Terzetto',
      svgClient:'Client', svgLuthier:'Luthier',
      svgPush:'\u2193 Pouss\u00e9', svgPull:'\u2191 Tir\u00e9',
      svgHigh:'AIGU \u25b6', svgLow:'\u25c4 GRAVE',
      svgRH:'Main Droite', svgLH:'Main Gauche',
      svgPlan:'Plan de clavier', svgBellows:'SOUFFLET',
      svgSommierRH:'SOMMIER \u2014 Main Droite',
      svgSommierLH:'SOMMIER \u2014 Main Gauche',
      svgMidi:'MIDI', svgGeneratedBy:'G\u00e9n\u00e9r\u00e9 le',
      svgBellowsPush:'POUSS\u00c9', svgBellowsPull:'TIR\u00c9'
    };
    return _L[k] !== undefined ? _L[k] : k;
  };
}
/* ╔══════════════════════════════════════════════════════╗
   ║  SVG HELPERS                                         ║
   ╚══════════════════════════════════════════════════════╝ */
let _svgId=0;
function svg_open(w,h,bg){const id='vp'+(++_svgId);return`<svg width="${w}" height="${h}" viewBox="0 0 ${w} ${h}" xmlns="http://www.w3.org/2000/svg" font-family="'Nunito','Segoe UI','Helvetica Neue',Arial,sans-serif" overflow="hidden"><defs><clipPath id="${id}"><rect width="${w}" height="${h}"/></clipPath></defs><rect width="${w}" height="${h}" fill="${bg||'white'}"/><g clip-path="url(#${id})">`;}
function svg_close(){return'</g></svg>';}
function tx(x,y,text,size,fill,weight,anchor){return`<text x="${x}" y="${y}" font-size="${size}" fill="${fill}" font-weight="${weight||'normal'}" text-anchor="${anchor||'start'}">${text}</text>`;}
function esc(s){if(!s)return'';return String(s).replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function escSVG(s){if(!s)return'';return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function fmtDate(d){if(!d)return new Date().toLocaleDateString('fr-FR');try{return new Date(d).toLocaleDateString('fr-FR');}catch(e){return d;}}
/* Note display — uses displayNote() if defined (admin), else passes note as-is */
function _dispNote(note){if(typeof displayNote==='function')return displayNote(note);return note||'';}
function dn(note){return escSVG(_dispNote(note));}  // display note in current lang
/* Render a button value (single note OR "N1/N2/N3") as SVG text centred at (cx,baseY).
   For multi-note values the notes are stacked vertically (tspan lines). */
function svgBtnVal(v,cx,baseY,color){
  if(!v)return'';
  const disp=_dispNote(v);
  if(!disp.includes('/')){
    const fs=disp.length>8?5.5:disp.length>5?6.8:8;
    return tx(cx,baseY,escSVG(disp),fs,color,'bold','middle');
  }
  const parts=disp.split('/').map(p=>p.trim()).filter(Boolean);
  const lineH=6.5, fs=5.5;
  const startY=baseY-((parts.length-1)*lineH)/2;
  let out=`<text font-size="${fs}" fill="${color}" font-weight="bold" text-anchor="middle">`;
  parts.forEach((p,i)=>out+=`<tspan x="${cx}" y="${startY+i*lineH}">${escSVG(p)}</tspan>`);
  return out+'</text>';
}

/* ─── Bellows SVG ─── */
function bellowsSVG(x,y,w,h,folds,dark){
  const gold=dark?'#c9a227':'#8b6914',bg=dark?'#0a0800':'#fff8e6';
  let s=`<rect x="${x}" y="${y}" width="${w}" height="${h}" fill="${bg}" rx="2"/>`;
  const fh=h/folds;
  for(let i=0;i<folds;i++){
    const fy1=y+i*fh,fy2=fy1+fh,mid=fy1+fh/2;
    s+=`<line x1="${x}" y1="${fy1}" x2="${x+w}" y2="${fy1}" stroke="${gold}" stroke-width=".6" opacity=".7"/>`;
    s+=`<line x1="${x}" y1="${fy1}" x2="${x+w/2}" y2="${mid}" stroke="${gold}" stroke-width=".9"/>`;
    s+=`<line x1="${x+w/2}" y1="${mid}" x2="${x+w}" y2="${fy1}" stroke="${gold}" stroke-width=".9"/>`;
    if(i<folds-1){
      s+=`<line x1="${x}" y1="${fy2}" x2="${x+w/2}" y2="${mid}" stroke="${gold}" stroke-width=".6" opacity=".5"/>`;
      s+=`<line x1="${x+w/2}" y1="${mid}" x2="${x+w}" y2="${fy2}" stroke="${gold}" stroke-width=".6" opacity=".5"/>`;
    }
  }
  s+=`<rect x="${x}" y="${y}" width="${w}" height="${h}" fill="none" stroke="${gold}" stroke-width="1.5" rx="2"/>`;
  return s;
}


/* ╔══════════════════════════════════════════════════════╗
   ║  SVG KEYBOARD — CLIENT (light preview + dark PDF)    ║
   ╚══════════════════════════════════════════════════════╝ */
function buildSVG_Droite(data,dark){
  const R=24,HGAP=60,VGAP=72,LPAD=186,MARG=14,HEAD=80,FOOT=34;
  const gold=dark?'#c9a227':'#4338CA';
  const bg=dark?'#111111':'#ffffff';
  const pushBg=dark?'#0d1f0d':'#EEF2FF',pullBg=dark?'#1f1000':'#FFFBEB';
  const pushTx=dark?'#6fcf5a':'#3730A3',pullTx=dark?'#e8b840':'#92400E';
  const subtl=dark?'#888866':'#94A3B8',stroke=dark?'#c9a227':'#6366F1';
  const band1=dark?'#1a1a1a':'#FAFAFA',band2=dark?'#161616':'#F5F3FF';
  const numTx=dark?'rgba(200,162,39,.55)':'rgba(99,102,241,.55)';

  const maxBtns=Math.max(...data.droite.map(r=>r.boutons.length),1);
  const allOffs=data.droite.map(r=>r.offset||0);
  const maxOff=Math.max(...allOffs,0);
  const minOff=Math.min(...allOffs,0);
  const leftShift=-minOff*(HGAP/2);
  const BASE=LPAD+leftShift;
  const W=BASE+maxBtns*HGAP+maxOff*(HGAP/2)+(data.droite.length>1?HGAP/2:0)+MARG;
  const H=MARG+HEAD+data.droite.length*VGAP+FOOT;

  let s=svg_open(W,H,bg);

  // Orientation arrows — horizontal across the top
  const arrowY=MARG+HEAD-10;
  const ax1=BASE,ax2=W-MARG-2;
  const arrowMid=(ax1+ax2)/2;
  s+=`<line x1="${ax1+2}" y1="${arrowY}" x2="${ax2-2}" y2="${arrowY}" stroke="${gold}" stroke-width="1" opacity=".5"/>`;
  s+=`<polygon points="${ax1+2},${arrowY} ${ax1+10},${arrowY-4} ${ax1+10},${arrowY+4}" fill="${gold}" opacity=".7"/>`;
  s+=`<polygon points="${ax2-2},${arrowY} ${ax2-10},${arrowY-4} ${ax2-10},${arrowY+4}" fill="${gold}" opacity=".7"/>`;
  s+=tx(ax1+14,arrowY+4,t('svgLow'),8,gold,'bold','start');
  s+=tx(ax2-14,arrowY+4,t('svgHigh'),8,gold,'bold','end');

  // Title
  s+=tx(BASE+(maxBtns*HGAP)/2,MARG+16,escSVG(data.nomInstrument),16,gold,'bold','middle');
  if(data.nomJoueur)s+=tx(BASE+(maxBtns*HGAP)/2,MARG+32,`${t('svgClient')} : ${escSVG(data.nomJoueur)}`,10,subtl,'normal','middle');
  s+=tx(BASE+(maxBtns*HGAP)/2,MARG+47,`${t('svgLuthier')} : ${escSVG(data.nomLuthier)}  ·  ${fmtDate(data.date)}`,9,subtl,'normal','middle');

  // Legend – split circle (push=top half, pull=bottom half)
  const lx=BASE,ly=MARG+62;
  const lr=10,lcx=lx+lr,lcy=ly-4;
  s+=`<circle cx="${lcx}" cy="${lcy}" r="${lr}" fill="${bg}" stroke="${stroke}" stroke-width="1.2"/>`;
  s+=`<path d="M${lcx-lr},${lcy} A${lr},${lr} 0 0,1 ${lcx+lr},${lcy}" fill="${pushBg}"/>`;
  s+=`<path d="M${lcx-lr},${lcy} A${lr},${lr} 0 0,0 ${lcx+lr},${lcy}" fill="${pullBg}"/>`;
  s+=`<circle cx="${lcx}" cy="${lcy}" r="${lr}" fill="none" stroke="${stroke}" stroke-width="1.4"/>`;
  s+=`<line x1="${lcx-lr}" y1="${lcy}" x2="${lcx+lr}" y2="${lcy}" stroke="${stroke}" stroke-width=".5" opacity=".4"/>`;
  s+=tx(lcx+lr+5,lcy-1,t('svgPush'),8,dark?'#ccc':'#333','normal','start');
  s+=tx(lcx+lr+5,lcy+9,t('svgPull'),8,dark?'#ccc':'#333','normal','start');

  // Rows
  data.droite.forEach((row,ri)=>{
    const cy=MARG+HEAD+ri*VGAP+VGAP/2;
    const stagger=(ri%2===1)?HGAP/2:0;
    const alignOff=(row.offset||0)*(HGAP/2);
    s+=`<rect x="${BASE+alignOff-4}" y="${cy-R-6}" width="${row.boutons.length*HGAP+stagger+8}" height="${(R+6)*2}" rx="4" fill="${ri%2===0?band1:band2}" opacity=".7"/>`;
    s+=tx(BASE+alignOff-8,cy+4,escSVG(row.nom),9.5,gold,'bold','end');
    row.boutons.forEach((btn,bi)=>{
      const cx=BASE+alignOff+stagger+bi*HGAP;
      s+=`<circle cx="${cx}" cy="${cy}" r="${R}" fill="${bg}" stroke="${bg==='#ffffff'?'#bbb':'#333'}" stroke-width="1.2"/>`;
      s+=`<path d="M${cx-R},${cy} A${R},${R} 0 0,1 ${cx+R},${cy}" fill="${pushBg}"/>`;
      s+=`<path d="M${cx-R},${cy} A${R},${R} 0 0,0 ${cx+R},${cy}" fill="${pullBg}"/>`;
      s+=`<circle cx="${cx}" cy="${cy}" r="${R}" fill="none" stroke="${stroke}" stroke-width="1.4"/>`;
      s+=`<line x1="${cx-R}" y1="${cy}" x2="${cx+R}" y2="${cy}" stroke="${stroke}" stroke-width=".5" opacity=".4"/>`;
      s+=tx(cx,cy+3.5,row.boutons.length-bi,7.5,numTx,'normal','middle');
      if(btn.p){const fs=btn.p.length>6?7:8.5;s+=tx(cx,cy-9,dn(btn.p),fs,pushTx,'bold','middle');}
      if(btn.t){const fs=btn.t.length>6?7:8.5;s+=tx(cx,cy+19,dn(btn.t),fs,pullTx,'bold','middle');}
      if(data.showMidi){
        const mp=noteToMidi(btn.p),mt=noteToMidi(btn.t);
        if(mp!==null)s+=tx(cx+R-2,cy-R+6,mp,5.5,'rgba(100,140,220,.8)','normal','end');
        if(mt!==null)s+=tx(cx+R-2,cy+R-2,mt,5.5,'rgba(100,140,220,.8)','normal','end');
      }
    });
  });

  const fy=MARG+HEAD+data.droite.length*VGAP+14;
  s+=`<line x1="${BASE}" y1="${fy}" x2="${W-MARG-44}" y2="${fy}" stroke="${dark?'#333':'#eee'}" stroke-width="1"/>`;
  s+=tx(BASE,fy+14,`${escSVG(data.nomLuthier)} — ewendaviau.com`,7.5,dark?'#444':'#bbb','normal','start');
  s+=svg_close();return s;
}

function buildSVG_Gauche(data,dark){
  const R=24,HGAP=62,VGAP=70,LPAD=140,MARG=12,HEAD=32,FOOT=20;
  const gold=dark?'#c9a227':'#4338CA';
  const bg=dark?'#111':'#fff';
  const pushBg=dark?'#0d1f0d':'#EEF2FF',pullBg=dark?'#1f1000':'#FFFBEB';
  const pushTx=dark?'#6fcf5a':'#3730A3',pullTx=dark?'#e8b840':'#92400E';
  const stroke=dark?'#c9a227':'#6366F1';
  const band1=dark?'#1a1a1a':'#FAFAFA',band2=dark?'#161616':'#F5F3FF';
  const numTx=dark?'rgba(200,162,39,.55)':'rgba(99,102,241,.55)';
  const pushTx2=dark?'rgba(111,207,90,.55)':'rgba(55,48,163,.5)';
  const pullTx2=dark?'rgba(232,184,64,.55)':'rgba(146,64,14,.5)';
  const bassLbl=dark?'#5aa0d0':'#1D4ED8';
  const accLbl=dark?'#d09040':'#B45309';

  // Paired mode: B1 A1 B2 A2 / B3 A3 B4 A4 / … (PPROW pairs per display row)
  if(data.lhDisplayMode==='paired'){
    const PPROW=Math.max(1,data.lhPairsPerRow||2); // pairs per display row
    const nPairs=Math.max(data.basses.length,data.accords.length);
    const nDispRows=Math.ceil(nPairs/PPROW);
    const nCols=PPROW*2;
    const gridCol=dark?'#3a3a3a':'#DDD6FE';
    const W=LPAD+nCols*HGAP+MARG;
    const H=MARG+HEAD+nDispRows*VGAP+FOOT;
    let s=svg_open(W,H,bg);
    s+=tx(W/2,MARG+16,`${t('svgLH')} — ${escSVG(data.nomInstrument)}`,12,gold,'bold','middle');
    for(let r=0;r<nDispRows;r++){
      const cy=MARG+HEAD+r*VGAP+VGAP/2;
      s+=`<rect x="${LPAD-4}" y="${cy-R-6}" width="${nCols*HGAP+8}" height="${(R+6)*2}" rx="4" fill="${r%2===0?band1:band2}" opacity=".7"/>`;
      if(r>0){const yh=MARG+HEAD+r*VGAP;s+=`<line x1="${LPAD-4}" y1="${yh}" x2="${W-MARG}" y2="${yh}" stroke="${gridCol}" stroke-width="1"/>`;}
      for(let q=0;q<PPROW;q++){
        const pi=r*PPROW+q;
        if(pi>=nPairs)break;
        const bCol=data.lhBassFirst?q*2:q*2+1,aCol=data.lhBassFirst?q*2+1:q*2;
        [[data.basses[pi],'B',bCol,bassLbl,data.nbVoixBasses||2,data.basses2],[data.accords[pi],'A',aCol,accLbl,data.nbVoixAccords||2,data.accords2]].forEach(([btn,lbl,col,lblCol,voix,arr2])=>{
          if(!btn)return;
          const cx=LPAD+col*HGAP;
          const btn2=(voix>1&&arr2&&arr2.length>pi)?arr2[pi]:null;
          s+=`<circle cx="${cx}" cy="${cy}" r="${R}" fill="${bg}" stroke="${bg==='#fff'?'#bbb':'#333'}" stroke-width="1.2"/>`;
          s+=`<path d="M${cx-R},${cy} A${R},${R} 0 0,1 ${cx+R},${cy}" fill="${pushBg}"/>`;
          s+=`<path d="M${cx-R},${cy} A${R},${R} 0 0,0 ${cx+R},${cy}" fill="${pullBg}"/>`;
          s+=`<circle cx="${cx}" cy="${cy}" r="${R}" fill="none" stroke="${stroke}" stroke-width="1.4"/>`;
          s+=`<line x1="${cx-R}" y1="${cy}" x2="${cx+R}" y2="${cy}" stroke="${stroke}" stroke-width=".5" opacity=".4"/>`;
          s+=tx(cx-R+5,cy+R-4,lbl+(pi+1),6.5,lblCol,'bold','start');
          const btn2HasNotes=btn2&&(btn2.p||btn2.t);
          if(btn2HasNotes){
            if(btn.p)s+=svgBtnVal(btn.p,cx,cy-17,pushTx);
            if(btn2.p)s+=svgBtnVal(btn2.p,cx,cy-7,pushTx2);
            if(btn.t)s+=svgBtnVal(btn.t,cx,cy+9,pullTx);
            if(btn2.t)s+=svgBtnVal(btn2.t,cx,cy+19,pullTx2);
          }else{
            if(btn.p)s+=svgBtnVal(btn.p,cx,cy-9,pushTx);
            if(btn.t)s+=svgBtnVal(btn.t,cx,cy+19,pullTx);
          }
          if(data.showMidi){
            const mp=noteToMidi(btn.p),mt=noteToMidi(btn.t);
            if(mp!==null)s+=tx(cx+R-2,cy-R+6,mp,5.5,'rgba(100,140,220,.8)','normal','end');
            if(mt!==null)s+=tx(cx+R-2,cy+R-2,mt,5.5,'rgba(100,140,220,.8)','normal','end');
          }
        });
      }
    }
    s+=svg_close();return s;
  }

  // Separated mode: multi-row basses/accords via lhRows
  const lhRows=(data.lhRows&&data.lhRows.length)?data.lhRows:[{nb:data.basses.length||1,offset:0}];
  const secRows=[];
  let bassIdx=0,accordIdx=0;
  const vA=data.nbVoixAccords||2,vB=data.nbVoixBasses||2;
  const bassFirst=data.lhBassFirst||false;
  // Build accords sections
  const accordSecs=[];
  lhRows.forEach((row,ri)=>{
    accordSecs.push({nom:lhRows.length===1?t('accords'):(ri===0?t('accords'):`  ↳ +${accordIdx}`),items:data.accords.slice(accordIdx,accordIdx+row.nb),items2:vA>1?(data.accords2||[]).slice(accordIdx,accordIdx+row.nb):null,offset:row.offset||0,type:'A',voix:vA});
    accordIdx+=row.nb;
  });
  // Build basses sections
  const basseSecs=[];
  lhRows.forEach((row,ri)=>{
    basseSecs.push({nom:lhRows.length===1?t('basses'):(ri===0?t('basses'):`  ↳ +${bassIdx}`),items:data.basses.slice(bassIdx,bassIdx+row.nb),items2:vB>1?(data.basses2||[]).slice(bassIdx,bassIdx+row.nb):null,offset:row.offset||0,type:'B',voix:vB});
    bassIdx+=row.nb;
  });
  // Order: bassFirst → basses then accords, else accords then basses
  if(bassFirst){basseSecs.forEach(s=>secRows.push(s));accordSecs.forEach(s=>secRows.push(s));}
  else{accordSecs.forEach(s=>secRows.push(s));basseSecs.forEach(s=>secRows.push(s));}
  if(data.terzetto&&data.terzetto.length)secRows.push({nom:t('terzetto'),items:data.terzetto,items2:null,offset:0,type:'T',voix:null});

  const maxItems=Math.max(...secRows.map(sc=>sc.items.length),1);
  const maxSecOff=Math.max(...secRows.map(sc=>sc.offset||0),0);
  const W=LPAD+maxItems*HGAP+maxSecOff*(HGAP/2)+MARG;
  const H=MARG+HEAD+secRows.length*VGAP+FOOT;

  let s=svg_open(W,H,bg);
  s+=tx(W/2,MARG+16,`${t('svgLH')} — ${escSVG(data.nomInstrument)}`,12,gold,'bold','middle');
  // Pass 1: alternating row bands + row labels
  secRows.forEach((sec,si)=>{
    const off=(sec.offset||0)*(HGAP/2);
    const cy=MARG+HEAD+si*VGAP+VGAP/2;
    if(sec.items.length)
      s+=`<rect x="${LPAD-4+off}" y="${cy-R-6}" width="${sec.items.length*HGAP+8}" height="${(R+6)*2}" rx="4" fill="${si%2===0?band1:band2}" opacity=".7"/>`;
    s+=tx(LPAD-R-10,cy,escSVG(sec.nom),10,gold,'bold','end');
    if(sec.voix!==null&&sec.voix!==undefined)s+=tx(LPAD-R-10,cy+11,`${sec.voix}v`,7.5,dark?'rgba(200,162,39,.6)':'rgba(45,90,39,.55)','normal','end');
  });
  // Pass 2: table grid (column + row separator lines, outer border, column numbers)
  if(secRows.length>0&&maxItems>0){
    const gTop=MARG+HEAD,gBot=MARG+HEAD+secRows.length*VGAP;
    const gLeft=LPAD-HGAP/2,gRight=LPAD+maxItems*HGAP-HGAP/2;
    const gridCol=dark?'#3a3a3a':'#DDD6FE';
    s+=`<rect x="${gLeft}" y="${gTop}" width="${gRight-gLeft}" height="${gBot-gTop}" fill="none" stroke="${dark?'#555':'#C7D2FE'}" stroke-width="1.2" rx="2"/>`;
    for(let ci=1;ci<maxItems;ci++){const xv=LPAD+ci*HGAP-HGAP/2;s+=`<line x1="${xv}" y1="${gTop}" x2="${xv}" y2="${gBot}" stroke="${gridCol}" stroke-width="1"/>`;}
    for(let ri=1;ri<secRows.length;ri++){const yh=gTop+ri*VGAP;s+=`<line x1="${gLeft}" y1="${yh}" x2="${gRight}" y2="${yh}" stroke="${gridCol}" stroke-width="1"/>`;}
    for(let ci=0;ci<maxItems;ci++){s+=tx(LPAD+ci*HGAP,gTop-5,ci+1,7,dark?'rgba(200,162,39,.5)':'rgba(99,102,241,.4)','normal','middle');}
  }
  // Pass 3: buttons on top of grid
  secRows.forEach((sec,si)=>{
    const off=(sec.offset||0)*(HGAP/2);
    const cy=MARG+HEAD+si*VGAP+VGAP/2;
    const has2=sec.items2&&sec.items2.length>0;
    sec.items.forEach((btn,bi)=>{
      const cx=LPAD+bi*HGAP+off;
      const btn2=has2?(sec.items2[bi]||null):null;
      s+=`<circle cx="${cx}" cy="${cy}" r="${R}" fill="${bg}" stroke="${bg==='#fff'?'#bbb':'#333'}" stroke-width="1.2"/>`;
      s+=`<path d="M${cx-R},${cy} A${R},${R} 0 0,1 ${cx+R},${cy}" fill="${pushBg}"/>`;
      s+=`<path d="M${cx-R},${cy} A${R},${R} 0 0,0 ${cx+R},${cy}" fill="${pullBg}"/>`;
      s+=`<circle cx="${cx}" cy="${cy}" r="${R}" fill="none" stroke="${stroke}" stroke-width="1.4"/>`;
      s+=`<line x1="${cx-R}" y1="${cy}" x2="${cx+R}" y2="${cy}" stroke="${stroke}" stroke-width=".5" opacity=".4"/>`;
      const btnLblCol=sec.type==='B'?bassLbl:sec.type==='A'?accLbl:gold;
      s+=tx(cx-R+5,cy+R-4,sec.type+(bi+1),6.5,btnLblCol,'bold','start');
      const btn2HasNotes=has2&&btn2&&(btn2.p||btn2.t);
      if(btn2HasNotes){
        // 2-voice layout: V1 outer, V2 inner (closer to center divider)
        if(btn.p)s+=svgBtnVal(btn.p,cx,cy-17,pushTx);
        if(btn2.p)s+=svgBtnVal(btn2.p,cx,cy-7,pushTx2);
        if(btn.t)s+=svgBtnVal(btn.t,cx,cy+9,pullTx);
        if(btn2.t)s+=svgBtnVal(btn2.t,cx,cy+19,pullTx2);
      } else {
        if(btn.p)s+=svgBtnVal(btn.p,cx,cy-9,pushTx);
        if(btn.t)s+=svgBtnVal(btn.t,cx,cy+19,pullTx);
      }
      if(data.showMidi){
        const mp=noteToMidi(btn.p),mt=noteToMidi(btn.t);
        if(mp!==null)s+=tx(cx+R-2,cy-R+6,mp,5.5,'rgba(100,140,220,.8)','normal','end');
        if(mt!==null)s+=tx(cx+R-2,cy+R-2,mt,5.5,'rgba(100,140,220,.8)','normal','end');
      }
    });
  });
  s+=svg_close();return s;
}

/* ── Combined landscape SVG: Right Hand + Left Hand side-by-side ── */
function buildSVG_Combined(data,dark){
  const svgD=buildSVG_Droite(data,dark);
  const svgG=buildSVG_Gauche(data,dark);
  const wD=+(svgD.match(/\bwidth="(\d+(?:\.\d+)?)"/)||[,800])[1];
  const hD=+(svgD.match(/\bheight="(\d+(?:\.\d+)?)"/)||[,600])[1];
  const wG=+(svgG.match(/\bwidth="(\d+(?:\.\d+)?)"/)||[,800])[1];
  const hG=+(svgG.match(/\bheight="(\d+(?:\.\d+)?)"/)||[,600])[1];
  const GAP=28;
  const W=wD+GAP+wG;
  const H=Math.max(hD,hG);
  const bg=dark?'#111111':'#ffffff';
  const gold=dark?'#c9a227':'#2d5a27';
  // Re-root each SVG into the outer canvas by adding x/y to the outer <svg> tag
  // (nested SVG keeps its own viewBox and clipPath — fully supported per SVG spec)
  const yD=Math.round((H-hD)/2);
  const yG=Math.round((H-hG)/2);
  const innerD=svgD.replace(/^<svg\b/,`<svg x="0" y="${yD}"`);
  const innerG=svgG.replace(/^<svg\b/,`<svg x="${wD+GAP}" y="${yG}"`);
  return [
    `<svg width="${W}" height="${H}" viewBox="0 0 ${W} ${H}" xmlns="http://www.w3.org/2000/svg" font-family="Arial,Helvetica,sans-serif" overflow="hidden">`,
    `<rect width="${W}" height="${H}" fill="${bg}"/>`,
    innerD,
    `<line x1="${wD+GAP/2}" y1="10" x2="${wD+GAP/2}" y2="${H-10}" stroke="${gold}" stroke-width="1.2" opacity=".25" stroke-dasharray="5,4"/>`,
    innerG,
    `</svg>`
  ].join('');
}

/* ╔══════════════════════════════════════════════════════╗
   ║  SVG PLAN DE CLAVIER — TikZ-style vertical layout     ║
   ║  Faithfully reproduces the LaTeX/TikZ template:       ║
   ║  · \drawtitle  table (rotated "Plan de clavier" +     ║
   ║    vertical rule + info rows)                         ║
   ║  · LH columns anchored west at 60 % vertical ratio   ║
   ║  · RH columns anchored east at 60 % vertical ratio   ║
   ║  · "Haut (graves)" in italics near top-left           ║
   ║  · Buttons in vertical columns: top=graves→bot=aigus  ║
   ║  · Horizontal divider in each circle (top=poussé,     ║
   ║    bottom=tiré)                                       ║
   ╚══════════════════════════════════════════════════════╝ */
function buildSVG_Plan(data,dark){
  /* ── constants ────────────────────────────────────── */
  const R=18;                 // button radius
  const BGAP=46;              // vertical gap between buttons (center-to-center)
  const CGAP=52;              // horizontal gap between columns
  const MH=20,MT=14,MB=30;   // margins: horiz, top, bottom
  const TITLE_H=76;           // height reserved for the title table
  const BTWN=50;              // gap between LH section and RH section (bellows)

  /* ── colours ──────────────────────────────────────── */
  const gold=dark?'#c9a227':'#4338CA';
  const bg=dark?'#111':'#fff';
  const pushBg=dark?'#0d1f0d':'#EEF2FF',pullBg=dark?'#1f1000':'#FFFBEB';
  const pushTx=dark?'#6fcf5a':'#3730A3',pullTx=dark?'#e8b840':'#92400E';
  const pushTx2=dark?'rgba(111,207,90,.55)':'rgba(55,48,163,.5)';
  const pullTx2=dark?'rgba(232,184,64,.55)':'rgba(146,64,14,.5)';
  const subtl=dark?'#888866':'#94A3B8';
  const stroke=dark?'#c9a227':'#6366F1';
  const numTx=dark?'rgba(200,162,39,.65)':'rgba(99,102,241,.55)';
  const band1=dark?'#1a1a1a':'#FAFAFA',band2=dark?'#161616':'#F5F3FF';
  const circleBorder=dark?'#333':'#C7D2FE';
  const textColor=dark?'#ccc':'#1E293B';
  const ruleColor=dark?'#444':'#E2E8F0';

  /* ── draw one button circle ───────────────────────── */
  function drawBtn(cx,cy,btn,btn2){
    let o='';
    o+=`<circle cx="${cx}" cy="${cy}" r="${R}" fill="${bg}" stroke="${circleBorder}" stroke-width="1"/>`;
    o+=`<path d="M${cx-R},${cy} A${R},${R} 0 0,1 ${cx+R},${cy}" fill="${pushBg}"/>`;
    o+=`<path d="M${cx-R},${cy} A${R},${R} 0 0,0 ${cx+R},${cy}" fill="${pullBg}"/>`;
    o+=`<circle cx="${cx}" cy="${cy}" r="${R}" fill="none" stroke="${stroke}" stroke-width="1.2"/>`;
    o+=`<line x1="${cx-R}" y1="${cy}" x2="${cx+R}" y2="${cy}" stroke="${stroke}" stroke-width=".5" opacity=".4"/>`;
    const b2ok=btn2&&(btn2.p||btn2.t);
    if(b2ok){
      if(btn.p) o+=svgBtnVal(btn.p,cx,cy-11,pushTx);
      if(btn2.p)o+=svgBtnVal(btn2.p,cx,cy-4,pushTx2);
      if(btn.t) o+=svgBtnVal(btn.t,cx,cy+8,pullTx);
      if(btn2.t)o+=svgBtnVal(btn2.t,cx,cy+15,pullTx2);
    }else{
      if(btn.p)o+=svgBtnVal(btn.p,cx,cy-7,pushTx);
      if(btn.t)o+=svgBtnVal(btn.t,cx,cy+15,pullTx);
    }
    return o;
  }

  /* ── RH data ──────────────────────────────────────── */
  const rhRows=data.droite;
  const nRH=rhRows.length;
  const maxRHBtns=Math.max(...rhRows.map(r=>r.boutons.length),1);

  /* ── LH data (sections: accords, basses, terzetto) ─ */
  const isPaired=data.lhDisplayMode==='paired';
  const lhSecRows=[];

  if(isPaired){
    /* Alternated mode: split nPairs into groups of lhPairsPerRow per column.
       Vertical  → each group = 1 interleaved column (B-A-B-A stacked).
       Horizontal → each group = 2 adjacent columns (B column | A column). */
    const nPairs=Math.max(data.basses.length,data.accords.length);
    const ppRow=Math.max(1,data.lhPairsPerRow||nPairs);
    const nGroups=Math.ceil(nPairs/ppRow);
    const vA=data.nbVoixAccords||2,vB=data.nbVoixBasses||2;
    if(data.lhPairedDir==='horizontal'){
      /* Horizontal: ppRow = number of columns side by side.
         Column c holds: for each group g → B_{g*ppRow+c}, A_{g*ppRow+c} stacked vertically.
         Visual result (ppRow=3, total=6):
           Col0   Col1   Col2
           B1     B2     B3
           A1     A2     A3
           B4     B5     B6
           A4     A5     A6  */
      const nGroups=Math.ceil(nPairs/ppRow);
      for(let c=0;c<ppRow;c++){
        const intItems=[],intItems2=[],intTypes=[],intIndices=[];
        for(let g=0;g<nGroups;g++){
          const i=g*ppRow+c;
          if(i>=nPairs)break;
          const seq=data.lhBassFirst
            ?[['B',data.basses,data.basses2,vB],['A',data.accords,data.accords2,vA]]
            :[['A',data.accords,data.accords2,vA],['B',data.basses,data.basses2,vB]];
          seq.forEach(([typ,src,src2,nv])=>{
            if(src[i]){intItems.push(src[i]);intItems2.push(nv>1&&src2?src2[i]||null:null);intTypes.push(typ);intIndices.push(i+1);}
          });
        }
        if(intItems.length){
          const lbl=ppRow===1?`${t('basses')} / ${t('accords')}`:`${t('basses')}/${t('accords')} ${c+1}`;
          lhSecRows.push({nom:lbl,items:intItems,items2:intItems2,offset:0,interleavedTypes:intTypes,interleavedIndices:intIndices});
        }
      }
    }else{
      /* Vertical (default): each group → 1 interleaved column (B-A-B-A) */
      for(let g=0;g<nGroups;g++){
        const s0=g*ppRow,s1=Math.min(s0+ppRow,nPairs);
        const intItems=[],intItems2=[],intTypes=[],intIndices=[];
        for(let i=s0;i<s1;i++){
          const seq=data.lhBassFirst
            ?[['B',data.basses,data.basses2,vB],['A',data.accords,data.accords2,vA]]
            :[['A',data.accords,data.accords2,vA],['B',data.basses,data.basses2,vB]];
          seq.forEach(([typ,src,src2,nv])=>{
            if(src[i]){intItems.push(src[i]);intItems2.push(nv>1&&src2?src2[i]||null:null);intTypes.push(typ);intIndices.push(i+1);}
          });
        }
        const lbl=nGroups===1?`${t('basses')} / ${t('accords')}`:`${t('basses')}/${t('accords')} ${g+1}`;
        lhSecRows.push({nom:lbl,items:intItems,items2:intItems2,offset:0,interleavedTypes:intTypes,interleavedIndices:intIndices});
      }
    }
    if(data.terzetto&&data.terzetto.length)lhSecRows.push({nom:t('terzetto'),items:data.terzetto,items2:null,offset:0});
  }else{
    /* Separated mode: distinct column per section, respects lhRows multi-row config */
    const lhRowCfg=(data.lhRows&&data.lhRows.length)?data.lhRows:[{nb:data.basses.length||1,offset:0}];
    const accSecs=[],basSecs=[];
    let bIdx=0,aIdx=0;
    const vA=data.nbVoixAccords||2,vB=data.nbVoixBasses||2;
    lhRowCfg.forEach((row,ri)=>{
      accSecs.push({nom:lhRowCfg.length===1?t('accords'):(ri===0?t('accords'):`↳+${aIdx}`),items:data.accords.slice(aIdx,aIdx+row.nb),items2:vA>1?(data.accords2||[]).slice(aIdx,aIdx+row.nb):null,offset:row.offset||0,type:'A'});
      aIdx+=row.nb;
      basSecs.push({nom:lhRowCfg.length===1?t('basses'):(ri===0?t('basses'):`↳+${bIdx}`),items:data.basses.slice(bIdx,bIdx+row.nb),items2:vB>1?(data.basses2||[]).slice(bIdx,bIdx+row.nb):null,offset:row.offset||0,type:'B'});
      bIdx+=row.nb;
    });
    if(data.lhBassFirst){ basSecs.forEach(s=>lhSecRows.push(s)); accSecs.forEach(s=>lhSecRows.push(s)); }
    else                { accSecs.forEach(s=>lhSecRows.push(s)); basSecs.forEach(s=>lhSecRows.push(s)); }
    if(data.terzetto&&data.terzetto.length)lhSecRows.push({nom:t('terzetto'),items:data.terzetto,items2:null,offset:0,type:'T'});
  }

  const nLH=lhSecRows.length;
  const maxLHBtns=nLH>0?Math.max(...lhSecRows.map(sc=>sc.items.length),0):0;
  const maxBtns=Math.max(maxRHBtns,maxLHBtns,1);

  /* ── account for row offsets in height ──────────────── */
  const rhOffsets=rhRows.map(r=>r.offset||0);
  const rhMaxOff=Math.max(...rhOffsets,0);
  const rhMinOff=Math.min(...rhOffsets,0);
  const rhTopShift=-rhMinOff*(BGAP/2);   // extra space at top for negative offsets
  const rhBottomExtra=rhMaxOff*(BGAP/2); // extra space at bottom for positive offsets
  const lhOffsets=lhSecRows.map(s=>s.offset||0);
  const lhMaxOff=lhOffsets.length?Math.max(...lhOffsets,0):0;
  const lhMinOff=lhOffsets.length?Math.min(...lhOffsets,0):0;
  const lhTopShift=-lhMinOff*(BGAP/2);
  const lhBottomExtra=lhMaxOff*(BGAP/2);
  const HEADER_GAP=120;  // space above grid for vertical column labels

  /* ── layout geometry ──────────────────────────────── */
  const lhW=nLH>0?nLH*CGAP:0;
  const rhW=nRH*CGAP;
  const contentW=lhW+(nLH>0?BTWN:0)+rhW;
  const W=Math.max(contentW+2*MH,460);
  const extraOff=Math.max(rhTopShift+rhBottomExtra,lhTopShift+lhBottomExtra);
  const bodyH=maxBtns*BGAP+extraOff+HEADER_GAP+20;
  const H=MT+TITLE_H+bodyH+MB;

  /* positions — matching TikZ template exactly:
     · \input{left}  (anchor=west)  = RH (main droite) on LEFT side
     · \input{right} (anchor=east)  = LH (main gauche) on RIGHT side
     · both centred at 60 % vertical depth (TikZ .6 ratio)             */
  const contentTop=MT+TITLE_H;
  const contentBot=H-MB;
  const gridCenterY=contentTop+(contentBot-contentTop)*0.55;
  const gridTop=Math.round(gridCenterY-bodyH/2)+HEADER_GAP;
  /* gridTop now leaves HEADER_GAP above for rotated labels */

  /* RH on LEFT: outermost row (Ré/ext.) far left, innermost (Sol/int.) closest to bellows */
  const rhX0=MH+CGAP/2;
  /* LH on RIGHT: innermost (accords) closest to bellows, outermost (basses) far right */
  const lhX0=nLH>0? rhX0+rhW+BTWN : rhX0+rhW;
  /* bellows separator halfway between RH right edge and LH left edge */
  const sepX=nLH>0?(rhX0+(nRH-1)*CGAP+R+(lhX0-R))/2 : rhX0+rhW+BTWN/2;

  let s=svg_open(W,H,bg);

  /* ── Thin accent bar at top ──────────────────────── */
  s+=`<rect x="0" y="0" width="${W}" height="3" fill="${gold}"/>`;

  /* ═══════════════════════════════════════════════════
     TITLE TABLE — reproduces TikZ \drawtitle
     ═══════════════════════════════════════════════════ */
  const tblX=MH, tblY=MT;
  const rotW=22;                   // width of rotated-title column
  const tblInfoX=tblX+rotW+6;     // left edge of info rows
  const rowH=16;                   // row height
  const tblRows=4;
  const tblH=tblRows*rowH;

  // accent background behind the "Plan de clavier" rotated title column
  s+=`<rect x="${tblX}" y="${tblY}" width="${rotW}" height="${tblH}" fill="${gold}" opacity=".08" rx="3"/>`;
  // outer border of the title table
  s+=`<rect x="${tblX}" y="${tblY}" width="${rotW+244}" height="${tblH}" fill="none" stroke="${ruleColor}" stroke-width="1" rx="3"/>`;
  // vertical rule between rotated title and info columns
  s+=`<line x1="${tblX+rotW}" y1="${tblY}" x2="${tblX+rotW}" y2="${tblY+tblH}" stroke="${ruleColor}" stroke-width="1"/>`;
  // horizontal rules between info rows
  for(let i=1;i<tblRows;i++)
    s+=`<line x1="${tblX+rotW}" y1="${tblY+i*rowH}" x2="${tblX+rotW+244}" y2="${tblY+i*rowH}" stroke="${ruleColor}" stroke-width=".5"/>`;

  // rotated "Plan de clavier" (reads bottom-to-top, matching \begin{turn}{90})
  const rotCx=tblX+rotW/2, rotCy=tblY+tblH/2;
  s+=`<text x="${rotCx}" y="${rotCy}" text-anchor="middle" font-size="13" fill="${gold}" font-weight="bold" transform="rotate(-90,${rotCx},${rotCy})">${escSVG(t('svgPlan'))}</text>`;

  // Row 1 — system / instrument name
  s+=`<text x="${tblInfoX}" y="${tblY+rowH/2+4}" font-size="12" fill="${gold}" font-weight="bold">${escSVG(data.nomInstrument||'Accordéon Diatonique')}</text>`;
  // Row 2 — client
  const r2=data.nomJoueur?`${t('svgClient')} : ${data.nomJoueur}`:'';
  s+=`<text x="${tblInfoX}" y="${tblY+rowH+rowH/2+3}" font-size="8.5" fill="${textColor}">${escSVG(r2)}</text>`;
  // Row 3 — model description (show actual basses + accords count)
  const nBtnsRH=data.droite.reduce((a,r)=>a+r.boutons.length,0);
  const nBasses=data.basses?data.basses.length:0;
  const nAccords=data.accords?data.accords.length:0;
  const r3=`${nRH} ${nRH>1?'rangées':'rangée'} (${nBtnsRH} boutons) · ${nBasses} basses · ${nAccords} accords`;
  s+=`<text x="${tblInfoX}" y="${tblY+2*rowH+rowH/2+3}" font-size="8" fill="${textColor}">${escSVG(r3)}</text>`;
  // Row 4 — luthier + date
  s+=`<text x="${tblInfoX}" y="${tblY+3*rowH+rowH/2+3}" font-size="7.5" fill="${subtl}">${escSVG(data.nomLuthier)}  ·  ${escSVG(fmtDate(data.date))}</text>`;

  /* ── "Haut (graves)" in italics near top-left ─────── */
  s+=`<text x="${rhX0-R-6}" y="${gridTop-HEADER_GAP-8}" font-size="8" fill="${subtl}" font-style="italic">Haut (graves)</text>`;

  /* ── push/pull legend (small circle, top-right) ───── */
  const legR=7,legX=W-MH-legR-2,legY=tblY+tblH/2;
  s+=`<circle cx="${legX}" cy="${legY}" r="${legR}" fill="${bg}" stroke="${stroke}" stroke-width="1"/>`;
  s+=`<path d="M${legX-legR},${legY} A${legR},${legR} 0 0,1 ${legX+legR},${legY}" fill="${pushBg}"/>`;
  s+=`<path d="M${legX-legR},${legY} A${legR},${legR} 0 0,0 ${legX+legR},${legY}" fill="${pullBg}"/>`;
  s+=`<circle cx="${legX}" cy="${legY}" r="${legR}" fill="none" stroke="${stroke}" stroke-width="1"/>`;
  s+=`<line x1="${legX-legR}" y1="${legY}" x2="${legX+legR}" y2="${legY}" stroke="${stroke}" stroke-width=".4" opacity=".4"/>`;
  s+=tx(legX-legR-3,legY-1,t('svgPush').replace(/[↓↑]/g,'').trim(),6,textColor,'normal','end');
  s+=tx(legX-legR-3,legY+7,t('svgPull').replace(/[↓↑]/g,'').trim(),6,textColor,'normal','end');

  /* ═══════════════════════════════════════════════════
     RH COLUMNS  — anchored west (LEFT side of page)
     Matches TikZ: \node[anchor=west]{\input{left}}
     Row 0 (R1/Sol/int.) on far left, last row on right
     ═══════════════════════════════════════════════════ */
  s+=tx(rhX0+((nRH-1)*CGAP)/2,gridTop-HEADER_GAP+10,t('svgRH'),8.5,gold,'bold','middle');
  rhRows.forEach((row,ri)=>{
    const cx=rhX0+ri*CGAP;  // ri=0=R1 → leftmost
    const off=rhTopShift+(row.offset||0)*(BGAP/2);  // normalized: topShift ensures negative offsets stay visible
    // column label — fully vertical (-90°) above the column, text goes upward from lblY
    const lblY=gridTop-8;
    s+=`<text x="${cx}" y="${lblY}" text-anchor="start" font-size="6.5" fill="${gold}" font-weight="bold" transform="rotate(-90,${cx},${lblY})">${escSVG(row.nom)}</text>`;
    // alternating column band
    if(row.boutons.length){
      const cy0=gridTop+off+BGAP/2;
      const bandY=cy0-R-3;
      const bandH=(row.boutons.length-1)*BGAP+2*(R+3);
      s+=`<rect x="${cx-R-3}" y="${bandY}" width="${(R+3)*2}" height="${bandH}" rx="3" fill="${ri%2===0?band1:band2}" opacity=".6"/>`;
    }
    // buttons + per-column button number (bottom-right corner, with row prime suffix)
    const suffix=ri===0?'':ri===1?"'":"''";
    row.boutons.forEach((btn,bi)=>{
      const cy=gridTop+off+bi*BGAP+BGAP/2;
      s+=drawBtn(cx,cy,btn,null);
      s+=tx(cx-R-1,cy+R+2,(bi+1)+suffix,5.5,numTx,'bold','start');
    });
  });

  /* ═══════════════════════════════════════════════════
     BELLOWS SEPARATOR — vertical dashed line
     ═══════════════════════════════════════════════════ */
  if(nLH>0){
    const sepY1=gridTop-2,sepY2=gridTop+maxBtns*BGAP+extraOff+2;
    s+=`<line x1="${sepX}" y1="${sepY1}" x2="${sepX}" y2="${sepY2}" stroke="${dark?'#444':'#C7D2FE'}" stroke-width="1.5" stroke-dasharray="6,4"/>`;
    const sepMidY=(sepY1+sepY2)/2;
    s+=`<text x="${sepX}" y="${sepMidY}" text-anchor="middle" font-size="6.5" fill="${subtl}" font-weight="bold" transform="rotate(-90,${sepX},${sepMidY})">${escSVG('— '+t('svgBellows')+' —')}</text>`;
  }

  /* ═══════════════════════════════════════════════════
     LH COLUMNS  — anchored east (RIGHT side of page)
     Matches TikZ: \node[anchor=east]{\input{right}}
     Innermost sections (accords) closest to bellows = leftmost
     Outermost sections (basses) = rightmost (far right)
     LH vertically centred relative to RH block
     ═══════════════════════════════════════════════════ */
  if(nLH>0&&maxLHBtns>0){
    // Vertical offset to centre LH relative to RH
    const rhBlockH=maxRHBtns*BGAP+rhTopShift+rhBottomExtra;
    const lhBlockH=maxLHBtns*BGAP+lhTopShift+lhBottomExtra;
    const lhGridTop=gridTop+Math.round((rhBlockH-lhBlockH)/2);

    s+=tx(lhX0+((nLH-1)*CGAP)/2,lhGridTop-HEADER_GAP+10,t('svgLH'),8.5,gold,'bold','middle');

    /* All LH sections: unified render loop.
       isPaired mode pushes a single interleaved column into lhSecRows (B-A-B-A).
       Separated mode pushes one column per section. */
    const bassLbl=dark?'#5aa0d0':'#1D4ED8';
    const accLbl=dark?'#d09040':'#B45309';
    lhSecRows.forEach((sec,si)=>{
      const cx=lhX0+si*CGAP;
      const off=lhTopShift+(sec.offset||0)*(BGAP/2);
      const has2=sec.items2&&sec.items2.some(x=>x!=null);
      // rotated column label above the column (like RH columns)
      const lblY=lhGridTop-8;
      s+=`<text x="${cx}" y="${lblY}" text-anchor="start" font-size="6.5" fill="${gold}" font-weight="bold" transform="rotate(-90,${cx},${lblY})">${escSVG(sec.nom)}</text>`;
      // alternating column band
      if(sec.items.length){
        const cy0=lhGridTop+off+BGAP/2;
        const bandY=cy0-R-3;
        const bandH=(sec.items.length-1)*BGAP+2*(R+3);
        s+=`<rect x="${cx-R-3}" y="${bandY}" width="${(R+3)*2}" height="${bandH}" rx="3" fill="${si%2===0?band1:band2}" opacity=".6"/>`;
      }
      // buttons + B/A labels in bottom-left corner
      sec.items.forEach((btn,bi)=>{
        const cy=lhGridTop+off+bi*BGAP+BGAP/2;
        s+=drawBtn(cx,cy,btn,has2?(sec.items2[bi]||null):null);
        if(sec.interleavedTypes){
          const typ=sec.interleavedTypes[bi];
          const idx=sec.interleavedIndices?sec.interleavedIndices[bi]:'';
          s+=tx(cx-R+3,cy+R-3,typ+idx,5.5,typ==='B'?bassLbl:accLbl,'bold','start');
        }else if(sec.type){
          const lblC=sec.type==='B'?bassLbl:sec.type==='A'?accLbl:gold;
          s+=tx(cx-R+3,cy+R-3,sec.type+(bi+1),5.5,lblC,'bold','start');
        }
      });
    });
    // button numbers on the far right
    const lhLastCx=lhX0+(nLH-1)*CGAP;
    for(let i=0;i<maxLHBtns;i++)
      s+=tx(lhLastCx+R+5,lhGridTop+i*BGAP+BGAP/2+3,i+1,6,numTx,'normal','start');
  }

  /* ── "↑ Aigus" centred at bottom ───────────────── */
  s+=`<text x="${W/2}" y="${H-MB+12}" font-size="8.5" fill="${textColor}" font-style="italic" text-anchor="middle">↑ Aigus</text>`;

  /* ── footer ────────────────────────────────────── */
  s+=`<line x1="${MH}" y1="${H-MB+20}" x2="${W-MH}" y2="${H-MB+20}" stroke="${ruleColor}" stroke-width="1"/>`;
  s+=tx(MH,H-MB+28,`${escSVG(data.nomLuthier)} — ewendaviau.com`,7,subtl,'normal','start');

  s+=svg_close();
  return s;
}

/* ──────────────────────────────────────────────────────────
   presetToSVGData — Convert raw preset JSON (from admin DB
   or server .json files) to the data format for buildSVG_Plan.
   Handles ALL fields: offset, multi-voice, lhPairedDir, etc.
   ────────────────────────────────────────────────────────── */
function presetToSVGData(rec){
  const n=rec.notes||{};
  const droiteNotes=n.droite||[];
  const droite=(rec.rangees||[]).map((row,ri)=>({
    nom:row.nom||('Rang\u00e9e '+(ri+1)),
    register:row.register||'N',
    offset:row.offset||0,
    boutons:droiteNotes[ri]||[]
  }));
  const lhNb=Math.max((n.basses||[]).length,(n.accords||[]).length,1);
  return{
    nomInstrument:rec.nom||'',
    nomJoueur:rec.joueur||'',
    nomLuthier:rec.luthier||"Ewen d'Aviau",
    date:rec.dateCreation||'',
    nbVoix:rec.nbVoix||2,
    voixPerRow:rec.voixPerRow||[],
    nbVoixAccords:rec.nbVoixAccords||2,
    nbVoixBasses:rec.nbVoixBasses||2,
    lhDisplayMode:rec.lhDisplayMode||'separated',
    lhPairedDir:rec.lhPairedDir||'vertical',
    lhRows:rec.lhRows||[{nb:lhNb,offset:0}],
    lhPairsPerRow:rec.lhPairsPerRow||2,
    lhBassFirst:rec.lhBassFirst||false,
    showMidi:false,
    droite:droite,
    basses:n.basses||[],
    basses2:n.basses2||[],
    basses3:n.basses3||[],
    accords:n.accords||[],
    accords2:n.accords2||[],
    accords3:n.accords3||[],
    terzetto:n.terzetto||[],
    orderSpecs:rec.orderSpecs||{}
  };
}

/* ── Explicit window exports ──────────────────────────────────────────────
   Required because WP Optimize (and other minifiers/bundlers) may wrap
   this file in an IIFE, turning function declarations into local variables.
   Assigning to window guarantees global availability regardless of context.
   ──────────────────────────────────────────────────────────────────────── */
window.buildSVG_Droite  = buildSVG_Droite;
window.buildSVG_Gauche  = buildSVG_Gauche;
window.buildSVG_Combined = buildSVG_Combined;
window.buildSVG_Plan    = buildSVG_Plan;
window.presetToSVGData  = presetToSVGData;
