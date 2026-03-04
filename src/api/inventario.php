<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\InventarioApiController;
use App\Core\Database;
use App\Repositories\InverterRepository;
use App\Repositories\ManufacturerRepository;
use App\Repositories\PVModuleRepository;

// ── Bootstrap ─────────────────────────────────────────────────────────────────
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ERROR | E_PARSE);
header('Content-Type: application/json');

// ── Dependency wiring ─────────────────────────────────────────────────────────
$pdo        = Database::getInstance()->getPdo();
$controller = new InventarioApiController(
    new ManufacturerRepository($pdo),
    new PVModuleRepository($pdo),
    new InverterRepository($pdo),
);

// ── Request parsing ───────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';
$body   = (array) (json_decode(file_get_contents('php://input'), true) ?? []);

// ── Routing ───────────────────────────────────────────────────────────────────
$result = match ($action) {
    'list_manufacturers'  => $controller->listManufacturers($_GET),
    'save_manufacturer'   => $controller->saveManufacturer($body),
    'delete_manufacturer' => $controller->deleteManufacturer($body),
    'manufacturers_select'=> $controller->manufacturersForSelect(),
    'list_modules'        => $controller->listModules($_GET),
    'save_module'         => $controller->saveModule($body),
    'delete_module'       => $controller->deleteModule($body),
    'list_inverters'      => $controller->listInverters($_GET),
    'save_inverter'       => $controller->saveInverter($body),
    'delete_inverter'     => $controller->deleteInverter($body),
    default               => ['error' => 'Unknown action'],
};

// ── Response ──────────────────────────────────────────────────────────────────
ob_clean();
echo json_encode($result);
