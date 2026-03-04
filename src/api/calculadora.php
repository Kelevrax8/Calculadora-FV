<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\CalculadoraApiController;
use App\Core\Database;
use App\Repositories\InverterRepository;
use App\Repositories\PVModuleRepository;
use App\Services\NasaService;

// ── Bootstrap ─────────────────────────────────────────────────────────────────
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ERROR | E_PARSE);
header('Content-Type: application/json');

// ── Dependency wiring ─────────────────────────────────────────────────────────
$pdo        = Database::getInstance()->getPdo();
$controller = new CalculadoraApiController(
    new NasaService($pdo),
    new PVModuleRepository($pdo),
    new InverterRepository($pdo),
);

// ── Request parsing ───────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';
$body   = (array) (json_decode(file_get_contents('php://input'), true) ?? $_POST);

// ── Routing ───────────────────────────────────────────────────────────────────
$result = match ($action) {
    'get_climate_data' => $controller->getClimateData($body),
    'get_pv_modules'   => $controller->getPVModules(),
    'get_inverters'    => $controller->getInverters(),
    default            => ['error' => 'Unknown action', '__status' => 400],
};

// ── HTTP status (controllers signal non-200 via __status key) ─────────────────
if (isset($result['__status'])) {
    http_response_code($result['__status']);
    unset($result['__status']);
}

// ── Response ──────────────────────────────────────────────────────────────────
ob_clean();
echo json_encode($result);
