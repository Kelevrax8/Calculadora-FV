<!doctype html>
<html lang="es" class="h-full">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Calculadora FV - IPTE</title>

  <!-- Tailwind CSS 4.x (browser build) -->
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <style type="text/tailwindcss">
    @theme {
      --color-Ipteblue: #171933;
      --color-Ipteblue2: #0665F7;
    }
  </style>
</head>
<body class="h-full flex flex-col overflow-hidden bg-Ipteblue2">

  <!-- Top bar -->
  <header class="w-full bg-white border-b border-Ipteblue/20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <!-- Logo (left) -->
      <a href="#" class="flex items-center">
        <img
          src="./Images/Logo-IPTE.png"
          alt="Logo de la empresa"
          class="h-15 w-auto object-contain"
        />
      </a>

      <!-- Login button (right) -->
      <button
        type="button"
        class="inline-flex items-center rounded-md bg-Ipteblue px-4 py-2 text-sm font-semibold text-white hover:bg-Ipteblue2 transition-colors"
      >
        Iniciar sesión
      </button>
    </div>
  </header>

  <!-- Hero / Main section -->
  <main class="relative flex-1 flex items-center justify-start overflow-hidden">

    <!-- Background image -->
    <div
      class="absolute inset-0 bg-cover bg-center bg-no-repeat"
      style="background-image: url('./Images/Paneles.webp');"
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
          Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod
          tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim
          veniam, quis nostrud exercitation ullamco laboris.
        </p>
        <button
          type="button"
          class="inline-flex items-center rounded-md bg-Ipteblue2 px-6 py-3 text-sm font-semibold text-white hover:bg-white hover:text-Ipteblue transition-colors duration-200"
        >
          Iniciar sesión
        </button>
      </div>
    </div>

  </main>

  <!-- Footer -->
  <footer class="w-full bg-Ipteblue py-3">
    <p class="text-center text-xs text-white/70">
      &copy; 2026 Propiedad de IPTE Soluciones S.A. de C.V.
    </p>
  </footer>

</body>
</html>