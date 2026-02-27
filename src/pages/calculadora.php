<?php
define('APP', true);
$pageTitle = 'Calculadora FV - IPTE';
$extraHead = <<<'HEAD'
<link rel="stylesheet" href="/lib/leaflet.css"/>
<style>
  /* Tailwind resets max-width on img elements which breaks Leaflet tiles */
  .leaflet-container img { max-width: none !important; max-height: none !important; }
</style>
HEAD;
include '../components/header-dashboard.php';
?>

<main class="flex-1 overflow-y-auto bg-gray-100">
  <div class="max-w-7xl mx-auto px-6 py-8">

    <!-- Page title -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-Ipteblue">Calculadora FV</h1>
      <p class="text-sm text-gray-500 mt-1">Dimensionamiento de sistemas fotovoltaicos interconectados</p>
    </div>

    <!-- Blocks -->
    <?php include '../components/calc/bloque-calc1.php'; ?>
    <?php include '../components/calc/bloque-calc2.php'; ?>
    <!-- future blocks included here -->

  </div><!-- /max-w-7xl -->
</main>

<?php
$extraScripts = <<<'HTML'
<script src="/lib/leaflet.js"></script>
<script>
// ============================================================
//  BLOQUE 1 – Map, NASA POWER fetch, HSP mode toggle
// ============================================================
// NOTE: only one copy of this block should exist in the file
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

    nasaBtn.disabled = false;
    nasaBtn.classList.remove('text-gray-400', 'cursor-not-allowed', 'bg-gray-50');
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

  let monthlyGHI = null;
  let hspMode    = 'min';

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
      ? 'Usando el mes con menor radiación (diseño conservador)'
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
      const res  = await fetch('/pages/php_action/get_climate_data.php', { method: 'POST', body });
      const json = await res.json();
      if (!res.ok || json.error) throw new Error(json.error || 'Error desconocido');

      monthlyGHI = json.monthly.map(m => m.ghi);
      tminInput.value = json.tmin.toFixed(1);
      tmaxInput.value = json.tmax.toFixed(1);

      hspToggle.classList.remove('hidden');
      hspModeHint.classList.remove('hidden');
      applyHSPMode('min');

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

    const b1 = document.getElementById('bloque-1');
    const b2 = document.getElementById('bloque-2');

    b1.classList.add('hidden');
    b2.classList.remove('hidden');
    b2.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  // ── Back to Block 1 ──────────────────────────────────────
  document.getElementById('btn-bloque2-volver').addEventListener('click', function () {
    document.getElementById('bloque-2').classList.add('hidden');
    const b1 = document.getElementById('bloque-1');
    b1.classList.remove('hidden');
    b1.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

})();
</script>
HTML;
include '../components/footer.php';
?>