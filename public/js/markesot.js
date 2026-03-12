/* ═══════════════════════════════════════
   SHARED DATA
═══════════════════════════════════════ */
const MENUS = window.APP_MENUS || [];
const CRITERIA=[
  {id:'harga',  name:'Harga Terjangkau',  icon:'💰',desc:'Sesuai kantong'},
  {id:'rasa',   name:'Rasa Enak',         icon:'😋',desc:'Lezat & memuaskan'},
  {id:'sehat',  name:'Sehat & Bergizi',   icon:'🥗',desc:'Nilai gizi baik'},
  {id:'kenyang',name:'Bikin Kenyang',     icon:'💪',desc:'Porsi memuaskan'},
];
const PAIRS=[[0,1],[0,2],[0,3],[1,2],[1,3],[2,3]];
const fmt=n=>'Rp '+n.toLocaleString('id-ID');

/* ═══════════════════════════════════════
   ORDER SYSTEM
═══════════════════════════════════════ */
const DP_PCT = window.DP_PCT || 50;
let qty={},oStep=1,payMethod=null,uploaded=null;
MENUS.forEach(m=>qty[m.id]=0);
const oTotal=()=>MENUS.reduce((s,m)=>s+m.price*(qty[m.id]||0),0);
const oDp=()=>Math.round(oTotal()*DP_PCT/100);

function openOrder(){
  oStep=1;payMethod=null;uploaded=null;
  MENUS.forEach(m=>qty[m.id]=0);
  document.getElementById('orderOverlay').classList.add('open');
  document.body.style.overflow='hidden';
  renderOrder();
}
function closeOrder(){
  document.getElementById('orderOverlay').classList.remove('open');
  document.body.style.overflow='';
}

function renderOrder(){
  updateOrderSteps();
  const b=document.getElementById('orderBody');
  if(oStep===1)b.innerHTML=oS1();
  else if(oStep===2)b.innerHTML=oS2();
  else b.innerHTML=oS3();
  setTimeout(animW,80);
}

function updateOrderSteps(){
  const labels=['Menu','Bayar','Selesai'];
  let h='';
  labels.forEach((l,i)=>{
    const n=i+1,cls=n<oStep?'done':n===oStep?'active':'';
    h+=`<div class="step-pill ${cls}"><div class="step-dot">${n<oStep?'✓':n}</div><span>${l}</span></div>`;
    if(i<2)h+=`<div class="step-line ${n<oStep?'done':''}"></div>`;
  });
  document.getElementById('orderStepsRow').innerHTML=h;
  const titles={1:'Pilih Menu',2:'Pembayaran',3:'Pesanan Diterima!'};
  document.getElementById('orderTitle').textContent=titles[oStep];
}

function oS1(){
  let h=`<div class="menu-cat-label">🍽 Makanan</div>`;
  MENUS.filter(m=>m.cat==='food').forEach(m=>h+=mRow(m));
  h+=`<div class="menu-cat-label">🥤 Minuman</div>`;
  MENUS.filter(m=>m.cat==='drink').forEach(m=>h+=mRow(m));
  const t=oTotal(),has=t>0;
  if(has){
    h+=`<div class="order-box">`;
    MENUS.filter(m=>qty[m.id]>0).forEach(m=>{h+=`<div class="orow"><span>${m.emoji} ${m.name} ×${qty[m.id]}</span><span>${fmt(m.price*qty[m.id])}</span></div>`;});
    h+=`<div class="orow orow-total"><span>Total</span><span>${fmt(t)}</span></div></div>`;
  }else{h+=`<div class="empty-note"><span>🛒</span>Belum ada menu yang dipilih</div>`;}
  h+=`<button class="btn-primary" onclick="oGoStep(2)" ${!has?'disabled':''}>Lanjut ke Pembayaran →</button>`;
  return h;
}

function mRow(m){
  const q=qty[m.id]||0;
  return`<div class="menu-row"><div class="menu-emoji">${m.emoji}</div><div class="menu-info"><div class="menu-row-name">${m.name}</div><div class="menu-row-price">${fmt(m.price)}</div></div><div class="qty-wrap"><button class="qty-btn" onclick="chgQty(${m.id},-1)" ${q===0?'disabled':''}>−</button><div class="qty-val">${q}</div><button class="qty-btn" onclick="chgQty(${m.id},1)">+</button></div></div>`;
}

function chgQty(id,d){qty[id]=Math.max(0,(qty[id]||0)+d);renderOrder();}
function oGoStep(n){oStep=n;if(n===2){payMethod=null;uploaded=null;}renderOrder();}

let cName = '', cPhone = '', lastOrderNumber = '';

function oS2(){
  const t=oTotal(),d=oDp();
  let h=`<div class="dp-banner"><div class="dp-ico">💡</div><div class="dp-info"><h4>Kebijakan DP ${DP_PCT}%</h4><p>DP ditetapkan admin. Pelunasan saat pengambilan.</p></div><div class="dp-right"><div class="dp-num">${fmt(d)}</div><div class="dp-lbl">DP yang dibayar</div></div></div>
  
  <div style="margin-bottom:1.4rem;">
    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;color:var(--text-light);margin-bottom:.5rem;">Data Diri</div>
    <input type="text" id="custName" placeholder="Nama Lengkap (min 3 kar)" value="${cName}" oninput="cName=this.value;checkData()" style="width:100%;padding:.8rem;border:1px solid var(--border);border-radius:10px;margin-bottom:.5rem;font-family:inherit;font-size:.9rem;background:white;">
    <input type="tel" id="custPhone" placeholder="No WhatsApp (cth: 0812...)" value="${cPhone}" oninput="cPhone=this.value;checkData()" style="width:100%;padding:.8rem;border:1px solid var(--border);border-radius:10px;font-family:inherit;font-size:.9rem;background:white;">
  </div>

  <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:var(--text-light);margin-bottom:.8rem;">Metode Pembayaran</div>
  <div class="pay-opts">
    <div class="pay-opt ${payMethod==='qris'?'sel':''}" onclick="selPay('qris')"><div class="pay-opt-icon">📱</div><div class="pay-opt-name">QRIS</div><div class="pay-opt-hint">Scan & bayar cepat</div></div>
    <div class="pay-opt ${payMethod==='bank'?'sel':''}" onclick="selPay('bank')"><div class="pay-opt-icon">🏦</div><div class="pay-opt-name">Transfer Bank</div><div class="pay-opt-hint">BRI / BNI / Mandiri</div></div>
  </div>`;
  
  if(payMethod==='qris'){
    h+=`<div class="pay-detail"><h4>🔲 Scan QRIS Berikut</h4>
    <div class="qris-box" id="qrisContainer">
      <span class="dss-spinner" style="width:30px;height:30px;margin:1rem auto;border-width:2px;display:block;"></span>
      <div style="text-align:center;line-height:1.5">Generating QRIS...</div>
    </div>
    <p style="text-align:center;font-size:.8rem;color:var(--text-light);margin-top:.8rem;">Nominal DP: <strong style="color:var(--maroon)">${fmt(d)}</strong></p></div>`;
  }
  if(payMethod==='bank'){
    h+=`<div class="pay-detail"><h4>🏦 Detail Rekening</h4><div class="bank-line"><span class="bl-label">Bank</span><span class="bl-val" id="bankNameTxt">Loading...</span></div><div class="bank-line"><span class="bl-label">No. Rekening</span><span class="bl-val"><span id="bankAccTxt">Loading...</span> <button class="copy-btn" onclick="cp(document.getElementById('bankAccTxt').innerText)">Copy</button></span></div><div class="bank-line"><span class="bl-label">Atas Nama</span><span class="bl-val" id="bankHolderTxt">Loading...</span></div><div class="bank-line"><span class="bl-label">Nominal DP</span><span class="bl-val" style="color:var(--maroon)">${fmt(d)}</span></div></div>`;
  }
  if(payMethod){
    h+=`<span class="upload-label">📎 Upload Bukti Pembayaran</span><div class="upload-zone"><input type="file" accept="image/*" id="paymentFile" onchange="handleFile(event)"/><div class="upload-ico">📂</div><div class="upload-txt" id="uploadText">Klik atau seret foto bukti pembayaran</div><div class="upload-hint">JPG, PNG — maks. 5MB</div><img class="preview-img ${uploaded?'show':''}" id="prevImg" ${uploaded?`src="${uploaded.previewExt}"`:''}/></div>`;
  }
  h+=`<div class="order-box" style="margin-top:1.4rem;"><div class="orow"><span>Total Pesanan</span><span>${fmt(t)}</span></div><div class="orow"><span>DP (${DP_PCT}%)</span><span style="color:var(--gold-light)">${fmt(d)}</span></div><div class="orow orow-total"><span>Sisa Pelunasan</span><span>${fmt(t-d)}</span></div></div>
  <button class="btn-primary" id="pesanBtn" onclick="submitOrder()" disabled>🛍 Kirim Pesanan <span id="pesanLoad" style="display:none">⏳</span></button>
  <button class="btn-ghost" onclick="oGoStep(1)">← Ubah Menu</button>`;
  return h;
}

function selPay(m){
  payMethod=m; renderOrder(); checkData();
  
  if(m === 'qris') {
    fetch('/qris/' + oDp())
      .then(r=>r.json())
      .then(res=>{
        const qc = document.getElementById('qrisContainer');
        if(!qc) return;
        if(res.error) {
           qc.innerHTML = `<div style="color:var(--maroon);text-align:center;padding:1rem;">${res.error}</div>`;
        } else {
           qc.innerHTML = `<img src="${res.qr_image}" style="width:100%;height:100%;object-fit:cover;border-radius:8px">`;
        }
      })
      .catch(err => {
        const qc = document.getElementById('qrisContainer');
        if(qc) qc.innerHTML = `<div style="color:red;padding:1rem;">Gagal memuat QRIS</div>`;
      });
  }
  if(m === 'bank') {
    fetch('/bank-info')
      .then(r => r.json())
      .then(res => {
        const bn = document.getElementById('bankNameTxt'),
              bnc = document.getElementById('bankAccTxt'),
              bhl = document.getElementById('bankHolderTxt');
        if(bn) bn.innerText = res.bank_name || '-';
        if(bnc) bnc.innerText = res.account_number || '-';
        if(bhl) bhl.innerText = res.account_name || '-';
      });
  }
}

function handleFile(e){
  const f=e.target.files[0];
  if(!f)return;
  if(f.size > 5 * 1024 * 1024) { alert("Maksimal 5MB!"); e.target.value=''; return; }
  uploaded=f;
  const r=new FileReader();
  r.onload=ev=>{
    uploaded.previewExt = ev.target.result;
    const img=document.getElementById('prevImg');
    if(img){img.src=ev.target.result;img.classList.add('show');}
    const txt=document.getElementById('uploadText');
    if(txt){txt.innerText=f.name;}
    checkData();
  };
  r.readAsDataURL(f);
}

function checkData(){
  const btn = document.getElementById('pesanBtn');
  if(!btn) return;
  const validName = cName.trim().length >= 3;
  const validPhone = cPhone.trim().length >= 10;
  if(validName && validPhone && payMethod && uploaded) {
    btn.disabled = false;
  } else {
    btn.disabled = true;
  }
}

function cp(txt){navigator.clipboard.writeText(txt).then(()=>{document.querySelectorAll('.copy-btn').forEach(b=>{if(b.textContent==='Copy'){b.textContent='✓';setTimeout(()=>b.textContent='Copy',1300);}});});}

function submitOrder(){
  const btn = document.getElementById('pesanBtn');
  btn.disabled = true;
  document.getElementById('pesanLoad').style.display = 'inline';
  
  const fd = new FormData();
  fd.append('_token', window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '');
  fd.append('customer_name', cName);
  fd.append('customer_phone', cPhone);
  fd.append('payment_method', payMethod);
  fd.append('payment_proof', uploaded);
  
  let itemIdx = 0;
  MENUS.filter(m=>qty[m.id]>0).forEach(m => {
     fd.append(`items[${itemIdx}][menu_item_id]`, m.id);
     fd.append(`items[${itemIdx}][qty]`, qty[m.id]);
     itemIdx++;
  });

  fetch('/order', {
    method: 'POST',
    body: fd
  })
  .then(r => r.json())
  .then(res => {
     if(res.order_number) {
       lastOrderNumber = res.order_number;
       oStep = 3;
       renderOrder();
     } else if(res.errors || res.error) {
       alert("Error: " + JSON.stringify(res.errors || res.error));
       btn.disabled = false;
       document.getElementById('pesanLoad').style.display = 'none';
     }
  })
  .catch(e => {
     alert("Terjadi kesalahan jaringan.");
     btn.disabled = false;
     document.getElementById('pesanLoad').style.display = 'none';
  });
}

function oS3(){
  const t=oTotal(),d=oDp();
  let rows=MENUS.filter(m=>qty[m.id]>0).map(m=>`<div class="orow"><span>${m.emoji} ${m.name} ×${qty[m.id]}</span><span>${fmt(m.price*qty[m.id])}</span></div>`).join('');
  return`<div class="success-wrap"><div class="success-ico">🎉</div><div class="success-ttl">Pesanan Terkirim!</div><div class="success-sub">Order ID: <strong>${lastOrderNumber}</strong><br>Terima kasih! Tim Markesot akan segera memproses.</div>
  <div class="order-box" style="margin-bottom:1.2rem;">${rows}<div class="orow orow-total"><span>Total</span><span>${fmt(t)}</span></div><div class="orow" style="color:rgba(255,255,255,.72)"><span>DP dibayar</span><span>${fmt(d)}</span></div><div class="orow" style="color:rgba(255,255,255,.72)"><span>Sisa lunas</span><span>${fmt(t-d)}</span></div></div>
  <a href="/pay/${lastOrderNumber}" style="display:block;margin-bottom:1.2rem;background:#fdf4ec;border-radius:13px;padding:1rem;font-size:.84rem;color:var(--text-light);line-height:1.65;text-decoration:none;">🔗 Klik di sini untuk ke <strong>Halaman Status Pembayaran / Struk</strong></a>
  <button class="btn-primary" onclick="window.location.reload()">Selesai & Tutup</button></div>`;
}

function resetOrder(){MENUS.forEach(m=>qty[m.id]=0);payMethod=null;uploaded=null;oStep=1;cName='';cPhone='';closeOrder();}

/* ═══════════════════════════════════════
   DSS / AHP SYSTEM
═══════════════════════════════════════ */
let pairAns=Array(6).fill(null);
let prefAns={harga:null,rasa:null,sehat:null,kenyang:null};
let dssScreen=0;

const TOTAL_Q=10;

function openDSS(){
  dssScreen=0;
  document.getElementById('dssOverlay').classList.add('open');
  document.body.style.overflow='hidden';
  renderDSS();
}
function closeDSS(){
  document.getElementById('dssOverlay').classList.remove('open');
  document.body.style.overflow='';
}

function renderDSS(){
  updateDSSProgress();
  const b=document.getElementById('dssBody');
  if(dssScreen===0)b.innerHTML=dssIntro();
  else if(dssScreen<=6)b.innerHTML=dssPair(dssScreen-1);
  else if(dssScreen<=10)b.innerHTML=dssPref(dssScreen-7);
  else if(dssScreen===11){b.innerHTML=dssLoading();runDSSLoading();}
  else b.innerHTML=dssResult();
  b.scrollTop=0;
  setTimeout(animW,80);
}

function updateDSSProgress(){
  const answered=pairAns.filter(Boolean).length+Object.values(prefAns).filter(Boolean).length;
  const pct=Math.round((answered/TOTAL_Q)*100);
  document.getElementById('dssPFill').style.width=pct+'%';
  document.getElementById('dssPStep').textContent=answered+' dari '+TOTAL_Q;
  const labels=['Yuk Mulai!','Bandingkan Kriteria','Bandingkan Kriteria','Bandingkan Kriteria','Bandingkan Kriteria','Bandingkan Kriteria','Bandingkan Kriteria','Preferensimu','Preferensimu','Preferensimu','Preferensimu','Menganalisis...','Hasilnya ada!'];
  document.getElementById('dssPLabel').textContent=labels[dssScreen]||'';
  let dots='';
  for(let i=0;i<TOTAL_Q;i++){const done=i<answered,active=i===answered;dots+=`<div class="dss-dot ${done?'done':active?'active':''}"></div>`;}
  document.getElementById('dssPDots').innerHTML=dots;
}

// AHP engine
function buildMatrix(){
  const n=4,M=Array.from({length:n},()=>Array(n).fill(1));
  PAIRS.forEach(([i,j],k)=>{
    const a=pairAns[k];if(!a)return;
    const scale=[1,2,4,6,8];
    if(a.winner==='equal'){M[i][j]=1;M[j][i]=1;}
    else if(a.winner===0){M[i][j]=scale[a.intensity||1];M[j][i]=1/M[i][j];}
    else{M[j][i]=scale[a.intensity||1];M[i][j]=1/M[j][i];}
  });
  return M;
}
function ahpW(M){
  const n=M.length,cs=Array(n).fill(0);
  M.forEach(r=>r.forEach((v,j)=>cs[j]+=v));
  const nm=M.map(r=>r.map((v,j)=>v/cs[j]));
  return nm.map(r=>r.reduce((s,v)=>s+v,0)/n);
}
function calcScores(w){
  return MENUS.filter(m=>m.cat==='food').map(m=>{
    let sc=0;
    CRITERIA.forEach((c,i)=>{const pv=prefAns[c.id]||3;sc+=m[c.id]*w[i]*(pv/5);});
    return{...m,score:sc};
  }).sort((a,b)=>b.score-a.score);
}

// DSS Screens
function dssIntro(){
  return`<div class="chat-bubble"><div class="chat-avatar">👨‍🍳</div><div class="chat-text">Halo! Saya <strong>Chef Markesot</strong> 🍽️<br><br>Biar kamu nggak bingung, yuk kita cari menu paling cocok buat kamu sekarang!<br><br>Cukup <strong>10 pertanyaan singkat</strong> — mudah banget, santai aja 😊</div></div>
  <div style="background:white;border-radius:16px;padding:1.4rem;box-shadow:var(--shadow-sm);margin-bottom:1rem;">
    <div style="font-weight:700;font-size:.95rem;color:var(--text);margin-bottom:.9rem;">📋 Cara kerjanya:</div>
    <div style="display:flex;flex-direction:column;gap:.6rem;">
      <div style="display:flex;gap:.8rem;align-items:center;background:var(--gold-pale);border-radius:10px;padding:.8rem .9rem;">
        <div style="font-size:1.5rem">⚖️</div>
        <div style="flex:1"><div style="font-weight:700;font-size:.85rem;color:var(--maroon-dark)">6 pertanyaan — Pilih yang lebih penting</div><div style="font-size:.75rem;color:var(--text-light)">Pilih mana dari 2 hal yang lebih penting buatmu</div></div>
        <div style="background:var(--maroon);color:white;border-radius:6px;padding:.2rem .55rem;font-size:.7rem;font-weight:700;flex-shrink:0">6 soal</div>
      </div>
      <div style="display:flex;gap:.8rem;align-items:center;background:var(--green-bg);border-radius:10px;padding:.8rem .9rem;">
        <div style="font-size:1.5rem">🎛️</div>
        <div style="flex:1"><div style="font-weight:700;font-size:.85rem;color:var(--maroon-dark)">4 pertanyaan — Kondisimu hari ini</div><div style="font-size:.75rem;color:var(--text-light)">Ceritain kondisi & mood makanmu sekarang</div></div>
        <div style="background:var(--green);color:white;border-radius:6px;padding:.2rem .55rem;font-size:.7rem;font-weight:700;flex-shrink:0">4 soal</div>
      </div>
    </div>
  </div>
  <button class="btn-primary" onclick="dssGo(1)">Siap! Mulai Sekarang 🚀</button>`;
}

function dssPair(idx){
  const[i,j]=PAIRS[idx],A=CRITERIA[i],B=CRITERIA[j],ans=pairAns[idx];
  const winner=ans?.winner,intensity=ans?.intensity??1;
  const showInt=winner===0||winner===1;
  const iLabels=[{i:'😐',l:'Sedikit'},{i:'✅',l:'Lumayan'},{i:'⭐',l:'Jelas'},{i:'🔥',l:'Banget!'}];
  return`<div class="pair-counter">Pertanyaan ${idx+1} dari 6<div class="pair-dots">${[0,1,2,3,4,5].map(k=>`<div class="pdot ${k<idx?'done':k===idx?'active':''}"></div>`).join('')}</div></div>
  <div style="background:white;border-radius:16px;padding:1.4rem;box-shadow:var(--shadow-sm);">
    <div style="font-weight:700;font-size:1rem;color:var(--text);margin-bottom:.3rem;">Mana yang lebih penting buatmu?</div>
    <div style="font-size:.82rem;color:var(--text-light);margin-bottom:1.1rem;">Tap salah satu yang lebih kamu prioritaskan saat memilih makan.</div>
    <div class="versus-wrap">
      <div class="versus-side ${winner===0?'sel':''}" onclick="dssSelWinner(${idx},0)">
        <div class="versus-icon">${A.icon}</div>
        <div class="versus-name">${A.name}</div>
        <div class="versus-desc">${A.desc}</div>
        ${winner===0?'<div style="font-size:1.2rem">✅</div>':''}
      </div>
      <div class="vs-divider"><div class="vs-circle">VS</div></div>
      <div class="versus-side ${winner===1?'sel':''}" onclick="dssSelWinner(${idx},1)">
        <div class="versus-icon">${B.icon}</div>
        <div class="versus-name">${B.name}</div>
        <div class="versus-desc">${B.desc}</div>
        ${winner===1?'<div style="font-size:1.2rem">✅</div>':''}
      </div>
    </div>
    <div class="equal-btn ${winner==='equal'?'sel':''}" onclick="dssSelWinner(${idx},'equal')">😌 Dua-duanya sama pentingnya</div>
    ${showInt?`<div style="margin-top:1rem;padding-top:.9rem;border-top:1px solid var(--border)">
      <div style="font-size:.8rem;font-weight:700;color:var(--text);margin-bottom:.5rem">Seberapa lebih penting ${winner===0?A.name:B.name}?</div>
      <div class="intensity-row">${iLabels.map((l,k)=>`<div class="int-btn ${intensity===k?'sel':''}" onclick="dssSetInt(${idx},${k})"><span>${l.i}</span><span class="int-lbl">${l.l}</span></div>`).join('')}</div>
    </div>`:''}
    <button class="btn-primary" onclick="dssNextPair(${idx})" ${winner===null||winner===undefined?'disabled':''}>
      ${idx<5?'Pertanyaan Berikutnya →':'Lanjut ke Bagian 2 →'}
    </button>
  </div>`;
}

function dssSelWinner(idx,w){
  if(!pairAns[idx])pairAns[idx]={winner:null,intensity:1};
  pairAns[idx].winner=w;
  if(w==='equal')pairAns[idx].intensity=0;
  document.getElementById('dssBody').innerHTML=dssPair(idx);
  updateDSSProgress();setTimeout(animW,50);
}
function dssSetInt(idx,k){
  if(!pairAns[idx])pairAns[idx]={winner:null,intensity:1};
  pairAns[idx].intensity=k;
  document.getElementById('dssBody').innerHTML=dssPair(idx);
  updateDSSProgress();setTimeout(animW,50);
}
function dssNextPair(idx){if(!pairAns[idx])return;dssScreen=idx+2;renderDSS();}

const PREF_QS=[
  {cid:'harga',icon:'💰',q:'Gimana kondisi kantongmu hari ini?',hint:'Jujur aja, ini rahasia kita berdua! 😄',
   opts:[{v:1,i:'😅',l:'Lagi hemat',s:'Budget tipis'},{v:3,i:'😊',l:'Biasa aja',s:'Budget normal'},{v:5,i:'🤑',l:'Ada rezeki',s:'Nggak masalah mahal'}]},
  {cid:'rasa',icon:'😋',q:'Lagi pengen rasa yang gimana?',hint:'Pilih sesuai mood makanmu sekarang.',
   opts:[{v:1,i:'😐',l:'Biasa aja',s:'Yang penting kenyang'},{v:3,i:'😋',l:'Agak enak',s:'Lumayan pengen enak'},{v:5,i:'🤤',l:'Enak banget!',s:'Mood makan enak'}]},
  {cid:'sehat',icon:'🥗',q:'Lagi peduli sama makanan sehat?',hint:'Nggak ada yang menghakimi hehe 😇',
   opts:[{v:1,i:'🍟',l:'Nggak terlalu',s:'Yang penting enak'},{v:3,i:'🥙',l:'Agak penting',s:'Lumayan jaga'},{v:5,i:'🥗',l:'Penting banget',s:'Lagi jaga makan'}]},
  {cid:'kenyang',icon:'💪',q:'Butuh kenyang berapa lama?',hint:'Pilih sesuai aktivitasmu setelah makan.',
   opts:[{v:1,i:'🐦',l:'Ringan aja',s:'Nggak mau terlalu kenyang'},{v:3,i:'🙂',l:'Normal',s:'Cukup sampai sore'},{v:5,i:'🏋️',l:'Kenyang lama!',s:'Aktivitas panjang setelah ini'}]},
];

function dssPref(idx){
  const Q=PREF_QS[idx],sel=prefAns[Q.cid];
  return`<div class="chat-bubble"><div class="chat-avatar">👨‍🍳</div><div class="chat-text">Hampir selesai! Pertanyaan ${idx+1} dari 4 — tentang kondisimu hari ini ya 😊</div></div>
  <div style="background:white;border-radius:16px;padding:1.4rem;box-shadow:var(--shadow-sm);">
    <div style="font-size:1.8rem;margin-bottom:.5rem;">${Q.icon}</div>
    <div style="font-weight:700;font-size:1rem;color:var(--text);margin-bottom:.3rem;">${Q.q}</div>
    <div style="font-size:.82rem;color:var(--text-light);margin-bottom:1.1rem;">${Q.hint}</div>
    <div class="choice-grid">
      ${Q.opts.map(o=>`<div class="choice-btn ${sel===o.v?'sel':''}" onclick="dssSelPref('${Q.cid}',${o.v},${idx})">
        <div class="choice-btn-icon">${o.i}</div>
        <div class="choice-btn-label">${o.l}</div>
        <div class="choice-btn-sub">${o.s}</div>
        <div class="choice-check">✓</div>
      </div>`).join('')}
    </div>
    <button class="btn-primary" onclick="dssNextPref(${idx})" ${sel===null||sel===undefined?'disabled':''}>
      ${idx<3?'Pertanyaan Berikutnya →':'Lihat Rekomendasiku! 🎉'}
    </button>
  </div>`;
}

function dssSelPref(cid,v,idx){
  prefAns[cid]=v;
  document.getElementById('dssBody').innerHTML=dssPref(idx);
  updateDSSProgress();setTimeout(animW,50);
}
function dssNextPref(idx){dssScreen=idx<3?idx+8:11;renderDSS();}

function dssLoading(){
  return`<div style="text-align:center;padding:2rem 1rem;">
    <div class="dss-spinner"></div>
    <div style="font-weight:700;font-size:1rem;color:var(--text);margin-bottom:.4rem;">Sedang menganalisis pilihanmu...</div>
    <div style="font-size:.83rem;color:var(--text-light);margin-bottom:1.2rem;">Kami lagi menghitung menu terbaik buat kamu 🧠</div>
    <div id="loadSteps">
      <div class="load-step" style="animation-delay:.3s">⚖️ Membandingkan kriteria...</div>
      <div class="load-step" style="animation-delay:.9s">📊 Menghitung bobot prioritas...</div>
      <div class="load-step" style="animation-delay:1.5s">🍽️ Mencocokkan dengan menu...</div>
      <div class="load-step" style="animation-delay:2.1s">✅ Menyiapkan hasilnya...</div>
    </div>
  </div>`;
}

function runDSSLoading(){
  const steps=document.querySelectorAll('.load-step');
  [400,1000,1600,2200].forEach((d,i)=>setTimeout(()=>{if(steps[i])steps[i].classList.add('done');},d));
  setTimeout(()=>{dssScreen=12;renderDSS();},2900);
}

function dssResult(){
  const w=ahpW(buildMatrix()),ranked=calcScores(w),best=ranked[0],mx=ranked[0].score;
  const medals=['🥇','🥈','🥉','4️⃣','5️⃣'];
  const fills=['rf1','rf2','rf3','rfn','rfn'];

  return`<div class="winner-banner">
    <span class="w-crown">🏆</span>
    <div class="w-sub">Rekomendasi terbaik untukmu</div>
    <div class="w-name">${best.emoji} ${best.name}</div>
    <div class="w-pct">Cocok ${Math.round((best.score/mx)*100)}% dengan preferensimu</div>
    <div class="w-tags">${best.tags.map(t=>`<span class="w-tag">${t}</span>`).join('')}</div>
  </div>

  <div style="background:var(--gold-pale);border:1px solid rgba(201,168,76,.3);border-radius:14px;padding:1.2rem;margin-bottom:1rem;">
    <div style="font-weight:700;font-size:.88rem;color:var(--maroon-dark);margin-bottom:.7rem;">💡 Kenapa ${best.name}?</div>
    ${CRITERIA.map((c,i)=>`<div style="display:flex;align-items:center;gap:.7rem;padding:.4rem 0;border-bottom:1px solid rgba(201,168,76,.15);">
      <span style="font-size:1.2rem">${c.icon}</span>
      <div style="flex:1;font-size:.8rem;color:var(--text-mid)"><strong>${c.name}</strong> — Bobot ${Math.round(w[i]*100)}%, Rating ${best[c.id]}/5</div>
      <span>${'⭐'.repeat(Math.round(best[c.id]))}</span>
    </div>`).join('')}
  </div>

  <div style="background:white;border-radius:14px;padding:1.2rem;box-shadow:var(--shadow-sm);margin-bottom:1rem;">
    <div style="font-weight:700;font-size:.88rem;color:var(--text);margin-bottom:.8rem;">📋 Peringkat Semua Menu</div>
    <div class="rank-list">
      ${ranked.map((m,r)=>`<div class="rank-row">
        <div class="rank-emoji">${m.emoji}</div>
        <div class="rank-info">
          <div class="rank-name">${m.name}</div>
          <div class="rank-bar-track"><div class="${fills[r]} rank-bar-fill" data-w="${((m.score/mx)*100).toFixed(0)}%" style="width:0%"></div></div>
          <div class="rank-pct-label">Kecocokan: ${Math.round((m.score/mx)*100)}%</div>
        </div>
        <div class="rank-medal">${medals[r]}</div>
      </div>`).join('')}
    </div>
  </div>

  <button class="btn-order-now" onclick="closeDSSOpenOrder('${best.name}')">🛒 Pesan ${best.name} Sekarang!</button>
  <button class="btn-restart-dss" onclick="resetDSS()">🔄 Coba Lagi dengan Jawaban Lain</button>`;
}

function closeDSSOpenOrder(recName){
  closeDSS();
  setTimeout(()=>{openOrder();},300);
}

function resetDSS(){pairAns=Array(6).fill(null);prefAns={harga:null,rasa:null,sehat:null,kenyang:null};dssScreen=0;renderDSS();}
function dssGo(s){dssScreen=s;renderDSS();}

/* ═══════════════════════════════════════
   SHARED UTILS
═══════════════════════════════════════ */
function handleOverlayClick(e,id){if(e.target===document.getElementById(id)){if(id==='orderOverlay')closeOrder();else closeDSS();}}

function animW(){
  document.querySelectorAll('[data-w]').forEach(el=>{
    setTimeout(()=>{el.style.width=el.dataset.w;},100);
  });
}

/* scroll reveal */
const srObs=new IntersectionObserver(entries=>{
  entries.forEach(e=>{if(e.isIntersecting)e.target.classList.add('vis');});
},{threshold:.12});
document.querySelectorAll('.sr').forEach(el=>srObs.observe(el));