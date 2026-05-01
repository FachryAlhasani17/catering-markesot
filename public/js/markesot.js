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
const storedQty = localStorage.getItem('mk_cart_qty');
if (storedQty) {
  try { Object.assign(qty, JSON.parse(storedQty)); } catch(e){}
}

window.addEventListener('DOMContentLoaded', () => {
  if(localStorage.getItem('mk_auto_open') === '1') {
     localStorage.removeItem('mk_auto_open');
     setTimeout(openOrder, 500); 
  }
});

const oTotal=()=>MENUS.reduce((s,m)=>s+m.price*(qty[m.id]||0),0);
const oDp=()=>Math.round(oTotal()*DP_PCT/100);

function openOrder(){
  oStep=1;payMethod=null;uploaded=null;
  cName = window.USER_NAME || '';
  cPhone = window.USER_PHONE || '';
  cAddress = window.USER_ADDRESS || '';
  cEmail = window.USER_EMAIL || '';
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
  if(oStep===1) {
    b.innerHTML=oS1();
  } else if(oStep===2) {
    b.innerHTML=oS2();
    if (payMethod === 'bank') {
      if (bankInfoCache) {
        setTimeout(updateBankDOM, 10);
      } else {
        fetch('/bank-info')
          .then(r => r.json())
          .then(res => {
            bankInfoCache = res;
            updateBankDOM();
          });
      }
    }
  } else {
    b.innerHTML=oS3();
  }
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

let itemNotes = {};
const storedNotes = localStorage.getItem('mk_cart_notes');
if (storedNotes) { try { Object.assign(itemNotes, JSON.parse(storedNotes)); } catch(e){} }

function oS1(){
  const ordered = MENUS.filter(m => qty[m.id] > 0);
  const t = oTotal(), has = t > 0;

  let h = '';

  if (!has) {
    h += `<div style="text-align:center;padding:2.5rem 1rem;">
      <div style="font-size:3rem;margin-bottom:0.8rem;">🛒</div>
      <div style="font-weight:700;font-size:1.1rem;color:#333;margin-bottom:0.4rem;">Keranjang Masih Kosong</div>
      <div style="font-size:0.85rem;color:#888;line-height:1.5;margin-bottom:1.5rem;">Pilih menu pada halaman utama terlebih dahulu, lalu kembali ke sini untuk melanjutkan pesanan.</div>
      <button class="btn-primary" onclick="closeOrder(); setTimeout(() => document.getElementById('menu')?.scrollIntoView({behavior:'smooth'}), 100);" style="width:100%;">Lihat Menu Kami</button>
    </div>`;
  } else {
    // Group ordered items by category
    const cats = {};
    ordered.forEach(m => {
      const c = m.category_name || (m.cat === 'drink' ? 'Minuman' : 'Makanan');
      if(!cats[c]) cats[c] = [];
      cats[c].push(m);
    });

    for (const [catName, items] of Object.entries(cats)) {
      h += `<div class="menu-cat-label"><span style="font-weight:700;">${catName}</span></div>`;
      items.forEach(m => h += mRow(m));
    }

    h += `<div class="order-box"><div style="font-weight:700;font-size:0.9rem;margin-bottom:0.6rem;color:#333;">Ringkasan Pesanan</div>`;
    ordered.forEach(m => {
      const note = itemNotes[m.id] || '';
      h += `<div style="border-bottom:1px solid #f0f0f0;padding:0.6rem 0;">
        <div class="orow"><span>${m.name} ×${qty[m.id]}</span><span>${fmt(m.price*qty[m.id])}</span></div>
        <input type="text" placeholder="Catatan: pedas, tanpa sayur, dll." value="${note}" 
          oninput="itemNotes[${m.id}]=this.value;localStorage.setItem('mk_cart_notes',JSON.stringify(itemNotes))" 
          style="width:100%;border:1px solid #e8e8e8;border-radius:8px;padding:0.4rem 0.6rem;font-size:0.78rem;margin-top:0.4rem;color:#555;outline:none;box-sizing:border-box;"
        >
      </div>`;
    });
    h += `<div class="orow orow-total"><span>Total</span><span>${fmt(t)}</span></div></div>`;
  }

  if (has) {
    h += `<button class="btn-primary" onclick="oGoStep(2)">Lanjut ke Pembayaran</button>`;
    h += `<button class="btn-ghost" onclick="closeOrder()" style="margin-top:0.5rem;">Tambah Menu Lagi</button>`;
  }
  return h;
}

function mRow(m){
  const q=qty[m.id]||0;
  const imgStyle = m.image 
    ? `background-image:url('${m.image}');background-size:cover;background-position:center;` 
    : `background:linear-gradient(135deg,#f5e4be,#e8c97a);display:flex;align-items:center;justify-content:center;font-size:1.5rem;`;
  return`<div class="menu-row">
    <div style="width:44px;height:44px;border-radius:10px;overflow:hidden;flex-shrink:0;${imgStyle}">${m.image?'':m.emoji}</div>
    <div class="menu-info"><div class="menu-row-name">${m.name}</div><div class="menu-row-price">${fmt(m.price)}</div></div>
    <div class="qty-wrap"><button class="qty-btn" onclick="chgQty(${m.id},-1)" ${q===0?'disabled':''}>−</button><div class="qty-val">${q}</div><button class="qty-btn" onclick="chgQty(${m.id},1)">+</button></div>
  </div>`;
}

function chgQty(id,d){
  qty[id]=Math.max(0,(qty[id]||0)+d);
  localStorage.setItem('mk_cart_qty', JSON.stringify(qty));
  renderOrder();
  if (typeof renderLandingSteppers === 'function') renderLandingSteppers();
}
function oGoStep(n){oStep=n;if(n===2){payMethod=null;uploaded=null;}renderOrder();}

function addLandingItem(id) {
    chgQty(id, 1);
}

function openMenuDetail(id) {
  const m = MENUS.find(x => x.id == id);
  if(!m) return;
  
  if (m.image) {
    document.getElementById('mdImg').src = m.image;
    document.getElementById('mdImg').style.display = 'block';
    document.getElementById('mdEmoji').style.display = 'none';
  } else {
    document.getElementById('mdImg').style.display = 'none';
    document.getElementById('mdEmoji').innerText = m.emoji || '🍽️';
    document.getElementById('mdEmoji').style.display = 'flex';
  }

  document.getElementById('mdCat').innerText = m.category_name || (m.cat === 'drink' ? 'Minuman' : 'Makanan');
  document.getElementById('mdName').innerText = m.name;
  document.getElementById('mdPrice').innerText = fmt(m.price);
  document.getElementById('mdDesc').innerText = m.desc || '-';
  
  document.getElementById('mdRasa').innerText = (m.rasa||0) + '/5';
  document.getElementById('mdHarga').innerText = (m.harga||0) + '/5';
  document.getElementById('mdSehat').innerText = (m.sehat||0) + '/5';
  document.getElementById('mdKenyang').innerText = (m.kenyang||0) + '/5';
  
  const tagsWrap = document.getElementById('mdTags');
  if(tagsWrap) tagsWrap.innerHTML = '';
  /*
  if(m.tags && Array.isArray(m.tags)) {
    m.tags.forEach(t => {
      tagsWrap.innerHTML += `<span style="background:#e0e7ff; color:#4338ca; font-size:0.75rem; font-weight:700; padding:0.2rem 0.6rem; border-radius:12px;">#${t}</span>`;
    });
  } else if (typeof m.tags === 'string') {
    tagsWrap.innerHTML += `<span style="background:#e0e7ff; color:#4338ca; font-size:0.75rem; font-weight:700; padding:0.2rem 0.6rem; border-radius:12px;">#${m.tags}</span>`;
  }
  */

  const actWrap = document.getElementById('mdActionWrap');
  if(qty[id] > 0) {
    actWrap.innerHTML = `
      <div style="display:flex; justify-content:space-between; align-items:center; background:#f9f9f9; border:1px solid #eee; padding:0.6rem 1rem; border-radius:14px;">
        <span style="font-weight:700; color:var(--text); font-size:0.95rem;">Pesanan: <span style="color:var(--maroon);">${qty[id]} porsi</span></span>
        <button class="btn-primary" style="margin:0; width:auto; padding:0.5rem 1.2rem; font-size:0.85rem;" onclick="document.getElementById('menuDetailModal').classList.remove('open'); openOrder();">Lihat Keranjang</button>
      </div>
    `;
  } else {
    actWrap.innerHTML = `<button class="btn-primary" style="margin:0; width:100%;" onclick="addLandingItem(${id}); document.getElementById('menuDetailModal').classList.remove('open');">Tambahkan ke Pesanan</button>`;
  }

  document.getElementById('menuDetailModal').classList.add('open');
}

function renderLandingSteppers() {
    if (!window.APP_MENUS) return;
    
    window.APP_MENUS.forEach(m => {
        const q = qty[m.id] || 0;
        const stepperWrap = document.getElementById(`stepper-${m.id}`);
        if (!stepperWrap) return;
        
        const btnInit = stepperWrap.querySelector('.add-btn-init');
        const stepper = stepperWrap.querySelector('.stepper-controls');
        const disp = stepperWrap.querySelector('.qty-display');
        
        if (btnInit && stepper && disp) {
            if (q > 0) {
                btnInit.style.display = 'none';
                stepper.style.display = 'flex';
                disp.innerText = q;
            } else {
                btnInit.style.display = 'block';
                stepper.style.display = 'none';
            }
        }
    });

    const uniqueItemsCount = Object.keys(qty).filter(k => qty[k] > 0).length;
    let badge = document.getElementById('cart-badge');
    if (!badge) {
        const fab = document.querySelector('.fab-order');
        if (fab) {
            badge = document.createElement('div');
            badge.id = 'cart-badge';
            badge.className = 'cart-badge';
            fab.appendChild(badge);
        }
    }
    
    if (badge) {
        if (uniqueItemsCount > 0) {
            const prevCount = parseInt(badge.innerText || '0');
            badge.innerText = uniqueItemsCount;
            badge.style.display = 'flex';
            
            if (prevCount !== uniqueItemsCount) {
                badge.classList.remove('pop');
                void badge.offsetWidth; 
                badge.classList.add('pop');
            }
        } else {
            badge.style.display = 'none';
        }
    }
}

let cName='', cPhone='', cAddress='', cDate='', cEmail='', cPassword='',
    lastOrderNumber='', lastPayMethod='', lastTotal=0, lastDp=0, lastOrderRowsHTML='';
let bankPayFull = false;

function oS2(){
  if (!window.IS_LOGGED_IN) {
    return `<div style="text-align:center; padding: 2rem 1rem; margin-top: 1rem; border-radius: 12px;">
      <div style="font-size: 3rem; margin-bottom: 1rem;">🔒</div>
      <h3 style="margin-bottom: 0.5rem; font-size: 1.3rem; color: #333;">Silakan Login Terlebih Dahulu</h3>
      <p style="color: var(--text-light); margin-bottom: 2rem; font-size: 0.95rem; line-height: 1.5;">Anda harus masuk ke akun Anda untuk menyelesaikan pesanan dan melanjutkan pembayaran.</p>
      
      <button type="button" onclick="localStorage.setItem('mk_auto_open','1'); window.location.href=window.GOOGLE_LOGIN_URL" style="width: 100%; background:white;border:1px solid #ddd;padding:0.9rem;border-radius:10px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:0.6rem;cursor:pointer;margin-bottom:1rem;box-shadow: 0 2px 4px rgba(0,0,0,0.03); font-size: 0.95rem;">
        <svg width="22" height="22" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/><path fill="none" d="M0 0h48v48H0z"/></svg>
        Login dengan Google
      </button>

      <button type="button" onclick="localStorage.setItem('mk_auto_open','1'); window.location.href=window.LOGIN_URL" class="btn-primary" style="width: 100%; display:flex; align-items:center; justify-content:center; gap: 0.5rem; padding: 0.9rem; border-radius: 10px; font-size: 0.95rem;">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
        Login dengan Akun (Email)
      </button>
      
      <div style="font-size: 0.95rem; color: #666; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #eee;">
        Belum punya akun? <br>
        <a href="${window.LOGIN_URL}" onclick="localStorage.setItem('mk_auto_open','1')" style="color: var(--maroon); font-weight: 700; text-decoration: underline; display: inline-block; margin-top: 0.5rem; font-size: 1rem;">Daftar di sini</a>
      </div>
      
      <button class="btn-ghost" onclick="oGoStep(1)" style="margin-top: 1.5rem;">Kembali ke Menu</button>
    </div>`;
  }

  if(!cName && window.USER_NAME) cName = window.USER_NAME;
  if(!cPhone && window.USER_PHONE) cPhone = window.USER_PHONE;
  if(!cAddress && window.USER_ADDRESS) cAddress = window.USER_ADDRESS;

  const t=oTotal(), d=oDp();
  const now = new Date();
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
  const minDateTime = now.toISOString().slice(0,16);

  let h = `<div class="dp-banner"><div class="dp-ico"><img src="/images/icons/info.png" class="icon-img" alt="" onerror="this.style.display='none'"></div><div class="dp-info"><h4>Kebijakan DP ${DP_PCT}%</h4><p>DP telah ditetapkan. Pelunasan saat pengambilan.</p></div><div class="dp-right"><div class="dp-num">${fmt(d)}</div><div class="dp-lbl">DP minimum</div></div></div>
  <div class="form-section">
    <div class="form-section-label">Data Pemesan</div>
    <input type="text" id="custName" placeholder="Nama Lengkap (min. 3 karakter)" value="${cName}" oninput="cName=this.value;checkData()" class="cust-input">
    <input type="tel" id="custPhone" placeholder="No. WhatsApp (contoh: 08123...)" value="${cPhone}" oninput="cPhone=this.value;checkData()" class="cust-input">
    <textarea id="custAddress" placeholder="Alamat lengkap (untuk pengambilan / pengiriman)" oninput="cAddress=this.value;checkData()" class="cust-input cust-textarea" rows="2">${cAddress}</textarea>
    
    <label class="cust-label" style="margin-top: 1.2rem;">Waktu Pesanan Dibutuhkan (Tanggal & Waktu)</label>
    <input type="datetime-local" id="custDate" value="${cDate}" min="${minDateTime}" oninput="cDate=this.value;checkData()" class="cust-input">
  </div>
  <div class="form-section-label" style="margin-top:1.4rem;margin-bottom:.7rem;">Metode Pembayaran</div>
  <div class="pay-opts">
    <div class="pay-opt ${payMethod==='cash'?'sel':''}" onclick="selPay('cash')"><div class="pay-opt-icon"><img src="/images/icons/cash.png" class="icon-img" alt="Tunai" onerror="this.style.display='none'"></div><div class="pay-opt-name">Tunai</div><div class="pay-opt-hint">Bayar saat pengambilan</div></div>
    <div class="pay-opt ${payMethod==='bank'?'sel':''}" onclick="selPay('bank')"><div class="pay-opt-icon"><img src="/images/icons/bank.png" class="icon-img" alt="Transfer" onerror="this.style.display='none'"></div><div class="pay-opt-name">Transfer Bank</div><div class="pay-opt-hint">BRI / BNI / Mandiri</div></div>
  </div>`;

  if(payMethod==='cash'){
    h+=`<div class="pay-detail"><h4>Informasi Pembayaran Tunai</h4><div style="background:var(--green-bg);border:1px solid rgba(30,127,81,.25);border-radius:12px;padding:1.1rem 1.2rem;"><div style="font-weight:700;font-size:.92rem;color:var(--green);margin-bottom:.3rem;">Bayar Lunas saat Pengambilan</div><div style="font-size:.78rem;color:var(--text-light);">Tidak diperlukan DP. Pembayaran dilakukan langsung di tempat.</div><div style="border-top:1px solid rgba(30,127,81,.2);padding-top:.7rem;margin-top:.7rem;display:flex;justify-content:space-between;align-items:center;"><span style="font-size:.82rem;color:var(--text-light);">Total yang dibayar</span><span style="font-size:1.15rem;font-weight:800;color:var(--green);">${fmt(t)}</span></div></div></div>`;
  }
  if(payMethod==='bank'){
    const payAmt = bankPayFull ? t : d;
    h+=`<div class="pay-detail"><h4>Opsi Pembayaran Transfer</h4>
      <div style="display:flex;gap:0.6rem;margin-bottom:1.2rem;">
        <div onclick="bankPayFull=false;renderOrder()" style="flex:1;padding:0.8rem;border-radius:12px;border:2px solid ${!bankPayFull?'var(--maroon)':'#ddd'};background:${!bankPayFull?'#fdf2f2':'#fff'};cursor:pointer;text-align:center;transition:all 0.2s;">
          <div style="font-weight:700;font-size:0.9rem;color:${!bankPayFull?'var(--maroon)':'#555'};">Bayar DP</div>
          <div style="font-size:0.78rem;color:#888;margin-top:0.2rem;">DP ${DP_PCT}% = ${fmt(d)}</div>
          <div style="font-size:0.7rem;color:#aaa;margin-top:0.15rem;">Pelunasan saat ambil</div>
        </div>
        <div onclick="bankPayFull=true;renderOrder()" style="flex:1;padding:0.8rem;border-radius:12px;border:2px solid ${bankPayFull?'var(--maroon)':'#ddd'};background:${bankPayFull?'#fdf2f2':'#fff'};cursor:pointer;text-align:center;transition:all 0.2s;">
          <div style="font-weight:700;font-size:0.9rem;color:${bankPayFull?'var(--maroon)':'#555'};">Bayar Lunas</div>
          <div style="font-size:0.78rem;color:#888;margin-top:0.2rem;">Full ${fmt(t)}</div>
          <div style="font-size:0.7rem;color:#aaa;margin-top:0.15rem;">Tidak ada sisa bayar</div>
        </div>
      </div>
      <h4>Detail Rekening Bank</h4><div class="bank-line"><span class="bl-label">Bank</span><span class="bl-val" id="bankNameTxt">Loading...</span></div><div class="bank-line"><span class="bl-label">No. Rekening</span><span class="bl-val"><span id="bankAccTxt">Loading...</span> <button class="copy-btn" onclick="cp(document.getElementById('bankAccTxt').innerText)">Copy</button></span></div><div class="bank-line"><span class="bl-label">Atas Nama</span><span class="bl-val" id="bankHolderTxt">Loading...</span></div><div class="bank-line"><span class="bl-label">Nominal Transfer</span><span class="bl-val" style="color:var(--maroon);font-weight:800;">${fmt(payAmt)}</span></div></div>`;
    h+=`<span class="upload-label">Upload Bukti Transfer</span><div class="upload-zone"><input type="file" accept=".png, .jpg, .jpeg, .heic, .webp, image/png, image/jpeg, image/heic, image/webp" id="paymentFile" onchange="handleFile(event)"/><div class="upload-ico"><img src="/images/icons/upload.png" class="icon-img" alt="Upload" onerror="this.style.display='none'"></div><div class="upload-txt" id="uploadText">Klik atau seret foto bukti transfer</div><div class="upload-hint">JPG, PNG, HEIC — maks. 5MB</div><img class="preview-img ${uploaded?'show':''}" id="prevImg" ${uploaded?`src="${uploaded.previewExt}"`:''}/></div>`;
  }
  let summaryBox = '';
  if (payMethod==='cash') {
    summaryBox = `<div class="order-box" style="margin-top:1.4rem;"><div class="orow"><span>Total Pesanan</span><span>${fmt(t)}</span></div><div class="orow orow-total"><span>Bayar Lunas (Tunai)</span><span>${fmt(t)}</span></div></div>`;
  } else if (payMethod==='bank' && bankPayFull) {
    summaryBox = `<div class="order-box" style="margin-top:1.4rem;"><div class="orow"><span>Total Pesanan</span><span>${fmt(t)}</span></div><div class="orow orow-total"><span>Transfer Lunas</span><span>${fmt(t)}</span></div></div>`;
  } else if (payMethod==='bank') {
    summaryBox = `<div class="order-box" style="margin-top:1.4rem;"><div class="orow"><span>Total Pesanan</span><span>${fmt(t)}</span></div><div class="orow"><span>DP Transfer (${DP_PCT}%)</span><span style="color:var(--gold-light)">${fmt(d)}</span></div><div class="orow orow-total"><span>Sisa Pelunasan</span><span>${fmt(t-d)}</span></div></div>`;
  }
  h += summaryBox;
  h+=`<div id="validationMsg" style="color:#ef4444; font-size:0.85rem; margin-bottom:12px; text-align:left; font-weight:600; display:none; background:#fef2f2; padding:10px 15px; border-radius:8px; border:1px solid #fca5a5; line-height:1.5;"></div>
  <button class="btn-primary" id="pesanBtn" onclick="submitOrder()" disabled>Kirim Pesanan</button>
  <button class="btn-ghost" onclick="oGoStep(1)">Ubah Menu</button>`;
  return h;
}

let bankInfoCache = null;

function updateBankDOM() {
  if(!bankInfoCache) return;
  const bn = document.getElementById('bankNameTxt'),
        bnc = document.getElementById('bankAccTxt'),
        bhl = document.getElementById('bankHolderTxt');
  if(bn) bn.innerText = bankInfoCache.bank_name || '-';
  if(bnc) bnc.innerText = bankInfoCache.account_number || '-';
  if(bhl) bhl.innerText = bankInfoCache.account_name || '-';
}

function selPay(m){
  payMethod=m; renderOrder(); checkData();
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
  const msg = document.getElementById('validationMsg');
  if(!btn) return;
  const validName    = cName.trim().length >= 3;
  const validPhone   = cPhone.trim().length >= 10;
  const validAddress = cAddress.trim().length >= 5;
  const validEmail   = window.IS_LOGGED_IN ? true : cEmail.trim().length >= 5;
  const validPass    = window.IS_LOGGED_IN ? true : cPassword.trim().length >= 4;
  const validDate    = cDate.trim().length > 0;
  const validProof   = payMethod === 'bank' ? !!uploaded : true;

  let missing = [];
  if (!validName) missing.push("Nama (min. 3 huruf)");
  if (!validPhone) missing.push("Telepon/WA (min. 10 angka)");
  if (!validEmail) missing.push("Email valid");
  if (!validPass) missing.push("Password (min. 4 karakter)");
  if (!validAddress) missing.push("Alamat Pengiriman (min. 5 huruf)");
  if (!validDate) missing.push("Tanggal & Waktu Acara");
  
  if (!payMethod) {
      missing.push("Pilih Metode Pembayaran");
  } else if (!validProof) {
      missing.push("Upload Foto Bukti Transfer");
  }

  if(missing.length === 0) {
    btn.disabled = false;
    if (msg) msg.style.display = 'none';
  } else {
    btn.disabled = true;
    if (msg) {
      msg.style.display = 'block';
      msg.innerHTML = "<span style='display:block;margin-bottom:4px;'>Belum lengkap:</span>• " + missing.join("<br/>• ");
    }
  }
}

function cp(txt){navigator.clipboard.writeText(txt).then(()=>{document.querySelectorAll('.copy-btn').forEach(b=>{if(b.textContent==='Copy'){b.textContent='✓';setTimeout(()=>b.textContent='Copy',1300);}});});}

function submitOrder(){
  const btn = document.getElementById('pesanBtn');
  btn.disabled = true;
  if(document.getElementById('pesanLoad')) document.getElementById('pesanLoad').style.display = 'inline';
  
  const fd = new FormData();
  fd.append('_token', window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '');
  fd.append('customer_name',    cName);
  fd.append('customer_phone',   cPhone);
  fd.append('customer_address', cAddress);
  if (!window.IS_LOGGED_IN) {
     fd.append('email', cEmail);
     fd.append('password', cPassword);
  }
  fd.append('event_date',       cDate);
  fd.append('payment_method',   payMethod);
  if(payMethod === 'bank') fd.append('bank_pay_full', bankPayFull ? '1' : '0');
  // Bukti hanya diperlukan untuk transfer bank
  if(payMethod === 'bank' && uploaded) fd.append('payment_proof', uploaded);
  
  let itemIdx = 0;
  MENUS.filter(m=>qty[m.id]>0).forEach(m => {
     fd.append(`items[${itemIdx}][menu_item_id]`, m.id);
     fd.append(`items[${itemIdx}][qty]`, qty[m.id]);
     fd.append(`items[${itemIdx}][notes]`, itemNotes[m.id] || '');
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
       lastPayMethod   = res.payment_method || payMethod;
       lastTotal       = res.total_amount   || oTotal();
       lastDp          = res.dp_amount      || oDp();
       lastOrderRowsHTML = MENUS.filter(m=>qty[m.id]>0).map(m=>`<div class="orow"><span>${m.name} ×${qty[m.id]}</span><span>${fmt(m.price*qty[m.id])}</span></div>`).join('');
       oStep = 3;
       renderOrder();
       localStorage.removeItem('mk_cart_qty');
       localStorage.removeItem('mk_cart_notes');
       MENUS.forEach(m=>qty[m.id]=0);
       if (typeof renderLandingSteppers === 'function') renderLandingSteppers();
     } else if(res.errors || res.error) {
       alert('Error: ' + JSON.stringify(res.errors || res.error));
       btn.disabled = false;
       if(document.getElementById('pesanLoad')) document.getElementById('pesanLoad').style.display = 'none';
     }
  })
  .catch(e => {
     alert("Terjadi kesalahan jaringan.");
     btn.disabled = false;
     if(document.getElementById('pesanLoad')) document.getElementById('pesanLoad').style.display = 'none';
  });
}

function oS3(){
  const t = lastTotal || oTotal();
  const d = lastDp   || oDp();
  const isCash = lastPayMethod === 'cash';
  let rows = lastOrderRowsHTML || MENUS.filter(m=>qty[m.id]>0).map(m=>`<div class="orow"><span>${m.name} ×${qty[m.id]}</span><span>${fmt(m.price*qty[m.id])}</span></div>`).join('');

  const paymentInfo = isCash
    ? `<div class="orow orow-total"><span>Total Pesanan</span><span>${fmt(t)}</span></div>
       <div class="orow" style="color:rgba(255,255,255,.85);font-size:.82rem;"><span>Pembayaran</span><span>Tunai saat pengambilan</span></div>`
    : `<div class="orow orow-total"><span>Total Pesanan</span><span>${fmt(t)}</span></div>
       <div class="orow" style="color:rgba(255,255,255,.72)"><span>DP ditransfer</span><span>${fmt(d)}</span></div>
       <div class="orow" style="color:rgba(255,255,255,.72)"><span>Sisa saat pengambilan</span><span>${fmt(t-d)}</span></div>`;

  const payNote = isCash
    ? `<div style="background:#eaf7f1;border:1px solid rgba(30,127,81,.25);border-radius:13px;padding:1rem;font-size:.84rem;color:var(--green);line-height:1.65;margin-bottom:1.2rem;text-align:center;"><strong>Bayar lunas (${fmt(t)})</strong> saat pengambilan pesanan.</div>`
    : `<div style="background:#fdf4ec;border-radius:13px;padding:1rem;font-size:.84rem;color:var(--text-light);line-height:1.65;margin-bottom:1.2rem;text-align:center;">DP <strong>${fmt(d)}</strong> sedang diverifikasi admin. Sisa <strong>${fmt(t-d)}</strong> dibayar saat pengambilan.</div>`;

  return `<div class="success-wrap">
    <div class="success-ico"><img src="/images/icons/success.png" class="icon-img" style="width:80px;height:80px;object-fit:contain;" alt="Sukses" onerror="this.parentElement.textContent='OK'"></div>
    <div class="success-ttl">Pesanan Terkirim!</div>
    <div class="success-sub">Order ID: <strong>${lastOrderNumber}</strong><br>Terima kasih! Tim Markesot akan segera memproses.</div>
    <div class="order-box" style="margin-bottom:1.2rem;">${rows}${paymentInfo}</div>
    ${payNote}
    <button class="btn-primary" onclick="window.location.reload()">Selesai</button>
  </div>`;
}

function resetOrder(){MENUS.forEach(m=>qty[m.id]=0);payMethod=null;uploaded=null;oStep=1;cName='';cPhone='';cAddress='';cDate='';closeOrder();}

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

document.addEventListener('DOMContentLoaded', () => {
    if (typeof renderLandingSteppers === 'function') {
        renderLandingSteppers();
    }
});