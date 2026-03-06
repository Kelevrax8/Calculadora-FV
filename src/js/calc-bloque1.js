// ============================================================
//  NAVIGATION – History API (browser back/forward support)
// ============================================================
(function () {
  // Set initial history entry so the first back press stays on this page
  history.replaceState({ step: 1 }, '', '#paso-1');

  window.showStep = function (step) {
    // Clear downstream blocks so stale selections never carry forward
    if (step <= 3 && typeof window.resetBlock4 === 'function') window.resetBlock4();
    if (step <= 2 && typeof window.resetBlock3 === 'function') window.resetBlock3();
    if (step <= 1 && typeof window.resetBlock2 === 'function') window.resetBlock2();

    const blocks = [
      document.getElementById('bloque-1'),
      document.getElementById('bloque-2'),
      document.getElementById('bloque-3'),
      document.getElementById('bloque-4'),
    ];
    blocks.forEach((b, i) => b.classList.toggle('hidden', i + 1 !== step));
    const target = blocks[step - 1];
    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    if (step === 2 && typeof window.loadPVModules === 'function') window.loadPVModules();
    if (step === 3 && typeof window.loadInverters  === 'function') window.loadInverters();
    if (step === 4 && typeof window.loadBlock4     === 'function') window.loadBlock4();
  };

  window.addEventListener('popstate', function (e) {
    const step = (e.state && e.state.step) ? e.state.step : 1;
    window.showStep(step);
  });
})();

// ============================================================
//  BLOQUE 1 – Map, NASA POWER fetch, HSP mode toggle
// ============================================================
(function () {

  // ── Map ──────────────────────────────────────────────────
  const map = L.map('map', { center: [23.6345, -102.5528], zoom: 5 });
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 18,
  }).addTo(map);

  const latInput = document.getElementById('latitud');
  const lngInput = document.getElementById('longitud');
  const badge    = document.getElementById('coord-badge');
  const nasaBtn  = document.getElementById('btn-nasa-api');
  let marker = null;

  map.on('click', function (e) {
    const lat = e.latlng.lat.toFixed(4);
    const lng = e.latlng.lng.toFixed(4);

    latInput.value = lat;
    lngInput.value = lng;
    document.getElementById('badge-lat').textContent = lat;
    document.getElementById('badge-lng').textContent = lng;
    badge.classList.remove('hidden');

    if (marker) { marker.setLatLng(e.latlng); }
    else         { marker = L.marker(e.latlng).addTo(map); }

    // If location changed after a successful fetch, invalidate stale climate data
    const locationChanged = monthlyGHI !== null;
    if (locationChanged) {
      monthlyGHI  = null;
      monthlyData = null;

      // Clear NASA-filled fields and their blue highlights
      hspInput.value  = '';
      tminInput.value = '';
      tmaxInput.value = '';
      [hspInput, tminInput, tmaxInput].forEach(el => {
        el.classList.remove('bg-blue-50', 'border-Ipteblue');
      });

      // Hide HSP mode toggle since it no longer has valid data
      hspToggle.classList.add('hidden');
      hspModeHint.classList.add('hidden');
      hspModeHint.textContent = '';

      // Reset NASA button inner content back to fetch prompt
      nasaBtn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
             viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 3v1m0 16v1m8.66-9h-1M4.34 12h-1m15-6.36-.71.71M6.05 17.66l-.71.71m12.02 0-.71-.71M6.05 6.34l-.71-.71M12 7a5 5 0 1 0 0 10A5 5 0 0 0 12 7Z"/>
        </svg>
        Consultar NASA POWER`;
    }

    // Always ensure button is enabled and in the ready (blue) state
    nasaBtn.disabled = false;
    nasaBtn.classList.remove('text-green-600', 'border-green-300', 'bg-green-50',
                             'text-gray-400', 'cursor-not-allowed', 'bg-gray-50');
    nasaBtn.classList.add('text-Ipteblue', 'cursor-pointer', 'bg-white');
    nasaBtn.title = 'Obtener datos solares para esta ubicación';
  });

  // ── HSP mode toggle ───────────────────────────────────────
  const hspInput    = document.getElementById('hsp');
  const tminInput   = document.getElementById('tmin');
  const tmaxInput   = document.getElementById('tmax');
  const nasaError   = document.getElementById('nasa-error');
  const hspToggle   = document.getElementById('hsp-mode-toggle');
  const hspModeHint = document.getElementById('hsp-mode-hint');
  const hspModeBtns = document.querySelectorAll('.hsp-mode-btn');

  let monthlyGHI  = null;
  let monthlyData  = null;   // full monthly objects — survives calcState resets
  let hspMode      = 'avg';

  function computeHSP() {
    if (!monthlyGHI) return null;
    return hspMode === 'avg'
      ? monthlyGHI.reduce((a, b) => a + b, 0) / monthlyGHI.length
      : Math.min(...monthlyGHI);
  }

  function applyHSPMode(mode) {
    hspMode = mode;
    hspInput.value = computeHSP().toFixed(2);
    hspModeBtns.forEach(btn => {
      const active = btn.dataset.mode === mode;
      btn.classList.toggle('bg-Ipteblue', active);
      btn.classList.toggle('text-white',  active);
      btn.classList.toggle('bg-white',    !active);
      btn.classList.toggle('text-gray-500', !active);
    });
    hspModeHint.textContent = mode === 'min'
      ? 'Usando el mes con menor radiación'
      : 'Usando el promedio anual de radiación';
  }

  hspModeBtns.forEach(btn => btn.addEventListener('click', () => applyHSPMode(btn.dataset.mode)));

  // ── NASA POWER fetch ──────────────────────────────────────
  nasaBtn.addEventListener('click', async function () {
    const lat = latInput.value;
    const lng = lngInput.value;
    if (!lat || !lng) return;

    nasaBtn.disabled = true;
    nasaBtn.innerHTML = `
      <svg class="w-3.5 h-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
      </svg>
      Consultando NASA POWER…`;
    nasaError.classList.add('hidden');
    nasaError.textContent = '';

    try {
      const body = new FormData();
      body.append('lat', lat);
      body.append('lng', lng);
      const res  = await fetch('/api/calculadora.php?action=get_climate_data', { method: 'POST', body });
      const json = await res.json();
      if (!res.ok || json.error) throw new Error(json.error || 'Error desconocido');

      monthlyGHI  = json.monthly.map(m => m.ghi);
      monthlyData = json.monthly;   // persists across resets
      tminInput.value = json.tmin.toFixed(1);
      tmaxInput.value = json.tmax.toFixed(1);

      // Save full monthly data for block 4 monthly production table
      window.calcState          = window.calcState || {};
      window.calcState.monthly  = json.monthly;

      hspToggle.classList.remove('hidden');
      hspModeHint.classList.remove('hidden');
      applyHSPMode('avg');

      [hspInput, tminInput, tmaxInput].forEach(el => {
        el.classList.add('bg-blue-50', 'border-Ipteblue');
      });

      nasaBtn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
             viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
        </svg>
        Datos obtenidos${json.source === 'cache' ? ' (caché)' : ''}`;
      nasaBtn.classList.remove('text-Ipteblue');
      nasaBtn.classList.add('text-green-600', 'border-green-300', 'bg-green-50');
      nasaBtn.disabled = false;

    } catch (err) {
      nasaError.textContent = '⚠ ' + err.message;
      nasaError.classList.remove('hidden');
      nasaBtn.disabled = false;
      nasaBtn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
             viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 3v1m0 16v1m8.66-9h-1M4.34 12h-1m15-6.36-.71.71M6.05 17.66l-.71.71m12.02 0-.71-.71M6.05 6.34l-.71-.71M12 7a5 5 0 1 0 0 10A5 5 0 0 0 12 7Z"/>
        </svg>
        Reintentar NASA POWER`;
    }
  });

  // ── Reset block 1 (called by btn-reiniciar in block 4) ────
  window.resetBlock1 = function () {
    // Closure state
    monthlyGHI  = null;
    monthlyData = null;
    hspMode     = 'avg';

    // Input fields
    latInput.value  = '';
    lngInput.value  = '';
    hspInput.value  = '';
    tminInput.value = '';
    tmaxInput.value = '';
    document.getElementById('consumo_anual_kwh').value = '';

    // Remove blue highlight from NASA-filled fields
    [hspInput, tminInput, tmaxInput].forEach(el => {
      el.classList.remove('bg-blue-50', 'border-Ipteblue');
    });

    // Hide coordinate badge
    badge.classList.add('hidden');
    document.getElementById('badge-lat').textContent = '—';
    document.getElementById('badge-lng').textContent = '—';

    // Remove map marker
    if (marker) { marker.remove(); marker = null; }

    // Reset map view to initial position
    map.setView([23.6345, -102.5528], 5);

    // Reset NASA button to initial disabled state
    nasaBtn.disabled = true;
    nasaBtn.innerHTML = `
      <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
           viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 3v1m0 16v1m8.66-9h-1M4.34 12h-1m15-6.36-.71.71M6.05 17.66l-.71.71m12.02 0-.71-.71M6.05 6.34l-.71-.71M12 7a5 5 0 1 0 0 10A5 5 0 0 0 12 7Z"/>
      </svg>
      Consultar NASA POWER`;
    nasaBtn.classList.remove('text-green-600', 'border-green-300', 'bg-green-50',
                             'text-Ipteblue', 'cursor-pointer', 'bg-white');
    nasaBtn.classList.add('text-gray-400', 'cursor-not-allowed', 'bg-gray-50');
    nasaBtn.title = 'Selecciona primero una ubicación en el mapa';

    // Hide HSP mode toggle + hint
    hspToggle.classList.add('hidden');
    hspModeHint.classList.add('hidden');
    hspModeHint.textContent = '';

    // Reset HSP mode buttons to default (avg active)
    hspModeBtns.forEach(btn => {
      const active = btn.dataset.mode === 'avg';
      btn.classList.toggle('bg-Ipteblue',   active);
      btn.classList.toggle('text-white',    active);
      btn.classList.toggle('bg-white',      !active);
      btn.classList.toggle('text-gray-500', !active);
    });

    // Hide any lingering error
    nasaError.classList.add('hidden');
    nasaError.textContent = '';
  };

  // ── Continue to Block 2 ───────────────────────────────────
  document.getElementById('btn-bloque1-continuar').addEventListener('click', function () {
    const errors = [];

    if (!latInput.value || !lngInput.value)
      errors.push('Selecciona una ubicación en el mapa.');
    if (!document.getElementById('consumo_anual_kwh').value || +document.getElementById('consumo_anual_kwh').value <= 0)
      errors.push('Ingresa el consumo anual mayor a 0.');
    if (!hspInput.value || +hspInput.value <= 0)
      errors.push('Ingresa o consulta las Horas Solar Pico (HSP).');
    if (!tminInput.value)
      errors.push('Ingresa la temperatura mínima.');
    if (!tmaxInput.value)
      errors.push('Ingresa la temperatura máxima.');

    if (errors.length > 0) {
      nasaError.textContent = '⚠ ' + errors[0];
      nasaError.classList.remove('hidden');
      nasaError.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }

    nasaError.classList.add('hidden');

    // Re-inject monthly data in case resetBlock2() wiped calcState.monthly
    // (e.g. user navigated back without re-fetching NASA)
    if (monthlyData) {
      window.calcState         = window.calcState || {};
      window.calcState.monthly = monthlyData;
    }

    history.pushState({ step: 2 }, '', '#paso-2');
    window.showStep(2);
  });

  // ── Back to Block 1 ──────────────────────────────────────
  document.getElementById('btn-bloque2-volver').addEventListener('click', function () {
    history.pushState({ step: 1 }, '', '#paso-1');
    window.showStep(1);
  });

})();
