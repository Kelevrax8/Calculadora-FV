<?php defined('APP') or die('Access denied'); ?>
<!doctype html>
<html lang="es" class="h-full">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle ?? 'Calculadora FV - IPTE') ?></title>

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
      <a href="/" class="flex items-center">
        <img
          src="/Images/Logo-IPTE.png"
          alt="Logo de la empresa"
          class="h-15 w-auto object-contain"
        />
      </a>

    </div>
  </header>
