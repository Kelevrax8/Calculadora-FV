<?php
define('APP', true);
$pageTitle = 'Calculadora FV - IPTE';
include 'components/header.php';
?>

  <!-- Hero / Main section -->
  <main class="relative flex-1 flex items-center justify-start overflow-hidden">

    <!-- Background image -->
    <div
      class="absolute inset-0 bg-cover bg-center bg-no-repeat"
      style="background-image: url('/Images/Paneles.webp');"
    ></div>

    <!-- Gradient overlay: dark on the left, transparent on the right -->
    <div class="absolute inset-0 bg-gradient-to-r from-Ipteblue/90 via-Ipteblue/60 to-transparent"></div>

    <!-- Content -->
    <div class="relative z-10 max-w-7xl mx-auto px-8 sm:px-12 lg:px-20 py-24">
      <div class="max-w-xl">
        <span class="inline-block text-Ipteblue2 text-sm font-semibold tracking-widest uppercase mb-4">
          Soluciones Tecnológicas
        </span>
        <h1 class="text-4xl sm:text-5xl font-bold text-white leading-tight mb-6">
          Calculadora para<br>dimensionamiento de sistemas fotovoltaicos
        </h1>
        <p class="text-white/70 text-base sm:text-lg leading-relaxed mb-8">
          Módulo web diseñado para el dimensionamiento de Sistemas Fotovoltaicos conectados
          a la red eléctrica, proporcionando resultados precisos y confiables para optimizar el diseño y 
          rendimiento de los sistemas solares.
        </p>
        <a
          href="/pages/dashboard.php"
          class="inline-flex items-center rounded-md bg-Ipteblue2 px-6 py-3 text-sm font-semibold text-white hover:bg-white hover:text-Ipteblue transition-colors duration-200"
        >
          Iniciar sesión
        </a>

      </div>
    </div>

  </main>

<?php include 'components/footer.php'; ?>