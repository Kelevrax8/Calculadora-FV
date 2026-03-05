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
  <header class="w-full bg-white border-Ipteblue/20 ">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between bg-linear-to-b from-white-80 via-Ipteblue2/40 to-Ipteblue/80 backdrop-blur-sm">
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
