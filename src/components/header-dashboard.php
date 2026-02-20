<?php defined('APP') or die('Access denied'); ?>
<!doctype html>
<html lang="es" class="h-full">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle ?? 'Dashboard - IPTE') ?></title>

  <!-- Tailwind CSS 4.x (browser build) -->
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <style type="text/tailwindcss">
    @theme {
      --color-Ipteblue: #171933;
      --color-Ipteblue2: #0665F7;
    }
  </style>
</head>
<body class="h-full flex flex-col overflow-hidden bg-gray-100">

  <!-- Dashboard top bar -->
  <header class="w-full bg-Ipteblue border-b border-white/10 shrink-0">
    <div class="px-6 h-16 flex items-center justify-between">

      <!-- Left: Logo + app name -->
      <div class="flex items-center gap-4">
        <a href="../pages/dashboard.php" class="flex items-center">
          <img
            src="/Images/Logo-IPTE.png"
            alt="IPTE"
            class="h-9 w-auto object-contain brightness-0 invert"
          />
        </a>
        <span class="hidden sm:block text-white/30 text-lg font-light select-none">|</span>
        <span class="hidden sm:block text-white text-sm font-semibold tracking-wide">
          Calculadora FV
        </span>
      </div>

      <!-- Center: Module navigation (desktop) -->
      <nav class="hidden md:flex items-center gap-1">
        <a
          href="/pages/dashboard.php"
          class="px-4 py-2 rounded-md text-sm font-medium transition-colors
                 <?= (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'bg-Ipteblue2 text-white' : 'text-white/60 hover:text-white hover:bg-white/10' ?>"
        >
          Inicio
        </a>
        <a
          href="/pages/calculadora.php"
          class="px-4 py-2 rounded-md text-sm font-medium transition-colors
                 <?= (basename($_SERVER['PHP_SELF']) === 'calculadora.php') ? 'bg-Ipteblue2 text-white' : 'text-white/60 hover:text-white hover:bg-white/10' ?>"
        >
          Calculadora FV
        </a>
        <a
          href="/pages/inventario.php"
          class="px-4 py-2 rounded-md text-sm font-medium transition-colors
                 <?= (basename($_SERVER['PHP_SELF']) === 'inventario.php') ? 'bg-Ipteblue2 text-white' : 'text-white/60 hover:text-white hover:bg-white/10' ?>"
        >
          Inventario
        </a>
      </nav>

      <!-- Right: User indicator + mobile hamburger -->
      <div class="flex items-center gap-3">
        <span class="text-sm text-white/50 hidden sm:block">usuario@ipte.com</span>
        <div class="h-8 w-8 rounded-full bg-Ipteblue2 flex items-center justify-center text-white text-xs font-bold select-none">
          U
        </div>

        <!-- Hamburger button (mobile only) -->
        <button
          id="mobile-menu-btn"
          type="button"
          class="md:hidden flex items-center justify-center h-9 w-9 rounded-md text-white/70 hover:text-white hover:bg-white/10 transition-colors"
          aria-label="Abrir menú"
        >
          <!-- Icon wrapper: both icons stacked, cross-fade via opacity -->
          <span class="relative h-5 w-5">
            <svg id="icon-open" class="absolute inset-0 h-5 w-5 transition-all duration-300 ease-in-out opacity-100 rotate-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
            <svg id="icon-close" class="absolute inset-0 h-5 w-5 transition-all duration-300 ease-in-out opacity-0 -rotate-90" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
          </span>
        </button>
      </div>

    </div>

    <!-- Mobile dropdown menu -->
    <nav id="mobile-menu" class="md:hidden overflow-hidden flex flex-col gap-1 border-white/10 px-4 transition-all duration-300 ease-in-out" style="max-height:0; opacity:0; padding-top:0; padding-bottom:0;">
      <a
        href="/pages/dashboard.php"
        class="px-4 py-2.5 rounded-md text-sm font-medium transition-colors
               <?= (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'bg-Ipteblue2 text-white' : 'text-white/60 hover:text-white hover:bg-white/10' ?>"
      >
        Inicio
      </a>
      <a
        href="/pages/calculadora.php"
        class="px-4 py-2.5 rounded-md text-sm font-medium transition-colors
               <?= (basename($_SERVER['PHP_SELF']) === 'calculadora.php') ? 'bg-Ipteblue2 text-white' : 'text-white/60 hover:text-white hover:bg-white/10' ?>"
      >
        Calculadora FV
      </a>
      <a
        href="/pages/inventario.php"
        class="px-4 py-2.5 rounded-md text-sm font-medium transition-colors
               <?= (basename($_SERVER['PHP_SELF']) === 'inventario.php') ? 'bg-Ipteblue2 text-white' : 'text-white/60 hover:text-white hover:bg-white/10' ?>"
      >
        Inventario
      </a>
    </nav>

  </header>

  <script>
    const btn        = document.getElementById('mobile-menu-btn');
    const menu       = document.getElementById('mobile-menu');
    const iconOpen   = document.getElementById('icon-open');
    const iconClose  = document.getElementById('icon-close');
    let isOpen = false;

    btn.addEventListener('click', () => {
      isOpen = !isOpen;

      if (isOpen) {
        // Apply padding first so scrollHeight includes it
        menu.style.paddingTop    = '0.5rem';
        menu.style.paddingBottom = '0.75rem';
        menu.style.borderTopWidth = '1px';
        // Reveal menu with correct full height
        menu.style.maxHeight  = menu.scrollHeight + 48 + 'px';
        menu.style.opacity    = '1';
        // Swap icon: hide hamburger, show ×
        iconOpen.style.opacity   = '0';
        iconOpen.style.transform = 'rotate(90deg)';
        iconClose.style.opacity  = '1';
        iconClose.style.transform = 'rotate(0deg)';
      } else {
        // Collapse menu
        menu.style.maxHeight  = '0';
        menu.style.opacity    = '0';
        menu.style.paddingTop = '0';
        menu.style.paddingBottom = '0';
        menu.style.borderTopWidth = '0';
        // Swap icon: show hamburger, hide ×
        iconOpen.style.opacity   = '1';
        iconOpen.style.transform = 'rotate(0deg)';
        iconClose.style.opacity  = '0';
        iconClose.style.transform = 'rotate(-90deg)';
      }
    });
  </script>
