<?php defined('APP') or die('Access denied'); ?>
<!doctype html>
<html lang="es" class="h-full">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle ?? 'Calculadora FV - IPTE') ?></title>

  <!-- Tailwind CSS 4.x (CLI build) -->
  <link rel="stylesheet" href="/output.css">
</head>
<body class="h-full flex flex-col overflow-hidden bg-Ipteblue2">

  <!-- Top bar -->
  <header class="w-full bg-linear-to-r from-white via-Ipteblue2/40 to-Ipteblue/80">
    <div class="w-full px-4 sm:px-6 lg:px-8 h-14 flex items-center">
      <!-- Logo (left) -->
      <a href="/" class="flex items-center">
        <img
          src="/Images/Logo-IPTE.png"
          alt="Logo de la empresa"
          class="h-12 w-auto object-contain"
        />
      </a>
    </div>
  </header>
