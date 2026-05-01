@php
    $record = $getRecord();
    $payment = $record->payments()->whereNotNull('proof_image')->latest()->first();
    $imageUrl = $payment ? asset('storage/' . $payment->proof_image) : null;
@endphp

@if($imageUrl)
<div>
    {{-- Thumbnail --}}
    <div style="text-align: center; margin-bottom: 12px;">
        <img 
            src="{{ $imageUrl }}" 
            alt="Bukti Transfer" 
            onclick="document.getElementById('proofLightbox').style.display='flex'"
            style="max-width: 100%; max-height: 400px; object-fit: contain; border-radius: 8px; border: 1px solid #ddd; cursor: zoom-in;"
        />
    </div>

    {{-- Action buttons (Filament-style) --}}
    <div style="display: flex; gap: 8px; justify-content: center;">
        <button 
            type="button"
            onclick="document.getElementById('proofLightbox').style.display='flex'"
            class="fi-btn fi-btn-size-sm fi-btn-color-info"
            style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; font-size: 0.8rem; font-weight: 600; border-radius: 8px; border: none; background: rgb(59 130 246); color: white; cursor: pointer;"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6" />
            </svg>
            Lihat Penuh
        </button>
        <a 
            href="{{ $imageUrl }}" 
            download
            class="fi-btn fi-btn-size-sm fi-btn-color-success"
            style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; font-size: 0.8rem; font-weight: 600; border-radius: 8px; border: none; background: rgb(16 185 129); color: white; cursor: pointer; text-decoration: none;"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Download
        </a>
    </div>

    {{-- Lightbox popup --}}
    <div 
        id="proofLightbox" 
        onclick="if(event.target===this) this.style.display='none'"
        style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.85); z-index:999999; align-items:center; justify-content:center; padding:2rem; cursor:zoom-out;"
    >
        <div style="position: relative; max-width: 90vw; max-height: 90vh;">
            <img 
                src="{{ $imageUrl }}" 
                alt="Bukti Transfer" 
                style="max-width: 90vw; max-height: 85vh; object-fit: contain; border-radius: 8px; box-shadow: 0 20px 60px rgba(0,0,0,0.5);"
            />
            <div style="display:flex; gap:8px; justify-content:center; margin-top:16px;">
                <a 
                    href="{{ $imageUrl }}" 
                    download
                    onclick="event.stopPropagation()"
                    style="display:inline-flex; align-items:center; gap:6px; padding:8px 20px; font-size:0.85rem; font-weight:600; border-radius:8px; background:rgb(16 185 129); color:white; text-decoration:none; cursor:pointer;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Download
                </a>
                <button 
                    type="button"
                    onclick="document.getElementById('proofLightbox').style.display='none'"
                    style="display:inline-flex; align-items:center; gap:6px; padding:8px 20px; font-size:0.85rem; font-weight:600; border-radius:8px; background:rgba(255,255,255,0.15); color:white; border:1px solid rgba(255,255,255,0.3); cursor:pointer;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endif
