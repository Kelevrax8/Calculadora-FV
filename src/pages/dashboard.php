<?php
define('APP', true);
$pageTitle = 'Dashboard - IPTE';
include '../components/header-dashboard.php';
?>

  <!-- Dashboard main -->
  <main class="flex-1 overflow-y-auto">
    <div class="max-w-6xl mx-auto px-6 py-10">

      <!-- Page heading -->
      <div class="mb-8">
        <h1 class="text-2xl font-bold text-Ipteblue">Panel principal</h1>
        <p class="text-sm text-gray-400 mt-1">Selecciona un módulo para comenzar</p>
      </div>

      <!-- Module cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

        <!-- Card: Calculadora FV -->
        <a
          href="/pages/calculadora.php"
          class="group relative flex flex-col rounded-2xl bg-white border border-gray-200 shadow-sm overflow-hidden hover:shadow-lg hover:border-Ipteblue2 transition-all duration-200"
        >
          <!-- Accent strip -->
          <div class="h-1.5 w-full bg-Ipteblue2"></div>

          <div class="p-8 flex flex-col gap-4">
            <!-- Icon -->
            <div class="h-12 w-12 rounded-xl bg-Ipteblue2/10 flex items-center justify-center">
              <svg class="h-6 w-6 text-Ipteblue2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
              </svg>
            </div>

            <!-- Text -->
            <div>
              <h2 class="text-lg font-bold text-Ipteblue group-hover:text-Ipteblue2 transition-colors">
                Calculadora FV
              </h2>
              <p class="text-sm text-gray-400 mt-1 leading-relaxed">
                Dimensionamiento de sistemas fotovoltaicos conectados a la red eléctrica.
                Obtén resultados precisos para optimizar el diseño y rendimiento del sistema.
              </p>
            </div>

            <!-- CTA -->
            <div class="mt-auto pt-4 flex items-center gap-1 text-sm font-semibold text-Ipteblue2">
              Abrir módulo
              <svg class="h-4 w-4 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
              </svg>
            </div>
          </div>
        </a>

        <!-- Card: Inventario -->
        <a
          href="/pages/inventario.php"
          class="group relative flex flex-col rounded-2xl bg-white border border-gray-200 shadow-sm overflow-hidden hover:shadow-lg hover:border-Ipteblue2 transition-all duration-200"
        >
          <!-- Accent strip -->
          <div class="h-1.5 w-full bg-Ipteblue"></div>

          <div class="p-8 flex flex-col gap-4">
            <!-- Icon -->
            <div class="h-12 w-12 rounded-xl bg-Ipteblue/10 flex items-center justify-center">
              <svg class="h-6 w-6 text-Ipteblue" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
              </svg>
            </div>

            <!-- Text -->
            <div>
              <h2 class="text-lg font-bold text-Ipteblue group-hover:text-Ipteblue2 transition-colors">
                Inventario
              </h2>
              <p class="text-sm text-gray-400 mt-1 leading-relaxed">
                Gestión de equipo para sistemas fotovoltaicos. Administra paneles,
                inversores y demás componentes del catálogo.
              </p>
            </div>

            <!-- CTA -->
            <div class="mt-auto pt-4 flex items-center gap-1 text-sm font-semibold text-Ipteblue">
              Abrir módulo
              <svg class="h-4 w-4 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
              </svg>
            </div>
          </div>
        </a>

      </div>
    </div>
  </main>

<?php include '../components/footer.php'; ?>

