<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Tenant\Services\TenantContext;
use App\Models\AppModule;
use Illuminate\View\View;

final class ModulesController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    /**
     * Mostrar módulos activos del cliente actual.
     */
    public function index(): View
    {
        $tenant = $this->tenantContext->getTenant();

        // Módulos con descripciones detalladas
        $modulesDescriptions = $this->getModulesDescriptions();

        // Obtener módulos activos según fase y overrides
        $query = AppModule::where('is_active', true)
            ->orderBy('sort_order');

        $modules = $query->get()->filter(function (AppModule $module) {
            return $this->tenantContext->canAccessModule($module->key);
        })->map(function (AppModule $module) use ($modulesDescriptions) {
            $module->detailed_description = $modulesDescriptions[$module->key] ?? null;

            return $module;
        });

        $stats = [
            'total' => $modules->count(),
            'active' => $modules->filter(fn ($m) => $this->tenantContext->canAccessModule($m->key))->count(),
        ];

        return view('modules.index', compact('modules', 'stats', 'tenant'));
    }

    /**
     * Descripciones detalladas de cada módulo.
     */
    private function getModulesDescriptions(): array
    {
        return [
            'auth' => [
                'title' => 'Autenticación y Seguridad',
                'description' => 'Sistema completo de inicio de sesión con roles y permisos granulares.',
                'features' => [
                    'Inicio de sesión seguro con contraseña',
                    'Roles: Administrador, Gerente, Contador, Conductor',
                    'Permisos granulares por funcionalidad',
                    'Registro de actividad del usuario',
                ],
                'icon' => '🔐',
            ],
            'dashboard' => [
                'title' => 'Dashboard Principal',
                'description' => 'Panel de control con métricas y gráficos en tiempo real.',
                'features' => [
                    'KPIs de inventario en tiempo real',
                    'Gráficos de ventas y movimientos',
                    'Alertas de stock mínimo',
                    'Vista personalizada por rol',
                ],
                'icon' => '📊',
            ],
            'drivers' => [
                'title' => 'Gestión de Conductores',
                'description' => 'Administración de conductores, rutas y planillas de surtido.',
                'features' => [
                    'Registro de conductores',
                    'Asignación de rutas',
                    'Planillas de surtido digital',
                    'Seguimiento de entregas',
                ],
                'icon' => '🚚',
            ],
            'inventory' => [
                'title' => 'Inventario Completo',
                'description' => 'Gestión de bodega principal, vehículos y máquinas.',
                'features' => [
                    'Bodega principal con control de entrada/salida',
                    'Inventario de vehículos por ruta',
                    'Bodegas independientes por máquina',
                    'Ajustes de inventario',
                    'Importación desde Excel',
                ],
                'icon' => '📦',
            ],
            'products' => [
                'title' => 'Catálogo de Productos',
                'description' => 'Gestión del catálogo de productos con precios y stock mínimo.',
                'features' => [
                    'CRUD completo de productos',
                    'Categorización de productos',
                    'Definición de stock mínimo',
                    'Historial de cambios de precio',
                ],
                'icon' => '🏷️',
            ],
            'machines' => [
                'title' => 'Máquinas Expendedoras',
                'description' => 'Control total de las máquinas expendedoras y sus inventarios.',
                'features' => [
                    'Registro de máquinas con ubicación',
                    'Bodegas independientes por máquina',
                    'Control de stock por máquina',
                    'Sincronización con WorldOffice',
                ],
                'icon' => '🎰',
            ],
            'transfers' => [
                'title' => 'Traslados entre Bodegas',
                'description' => 'Órdenes de traslado con aprobación y trazabilidad completa.',
                'features' => [
                    'Crear órdenes de traslado',
                    'Flujo de aprobación',
                    'Registro de cantidades despachadas/recibidas',
                    'Movimientos de inventario automáticos',
                ],
                'icon' => '🔄',
            ],
            'routes' => [
                'title' => 'Gestión de Rutas',
                'description' => 'Administración de rutas de distribución y asignación de conductores.',
                'features' => [
                    'Crear y editar rutas',
                    'Asignar conductor a cada ruta',
                    'Asignar máquinas a rutas',
                    'Optimización de entregas',
                ],
                'icon' => '🛣️',
            ],
            'users' => [
                'title' => 'Administración de Usuarios',
                'description' => 'Gestión de usuarios, roles y permisos del sistema.',
                'features' => [
                    'Crear y editar usuarios',
                    'Asignación de roles',
                    'Restablecimiento de contraseñas',
                    'Activar/desactivar usuarios',
                ],
                'icon' => '👥',
            ],
            'ocr' => [
                'title' => 'OCR con Inteligencia Artificial',
                'description' => 'Lectura automática de planillas mediante IA (Gemini/OpenAI).',
                'features' => [
                    'Escaneo de planillas de surtido',
                    'Reconocimiento de texto con IA',
                    'Importación masiva de datos',
                    'Validación automática de lecturas',
                ],
                'icon' => '📷',
            ],
            'sales' => [
                'title' => 'Ventas de Máquinas',
                'description' => 'Registro de ventas realizadas en cada máquina expendedora.',
                'features' => [
                    'Registro de ventas por máquina',
                    'Historial de ventas',
                    'Cálculo de comisiones',
                    'Reportes de ventas por período',
                ],
                'icon' => '💰',
            ],
            'reports' => [
                'title' => 'Reportes y Exportación',
                'description' => 'Generación de reportes detallados y exportación a Excel/PDF.',
                'features' => [
                    'Reportes de movimientos',
                    'Exportación a Excel',
                    'Reportes de inventario',
                    'Historial de traslados',
                ],
                'icon' => '📈',
            ],
            'analytics' => [
                'title' => 'Analytics y Estadísticas',
                'description' => 'Gráficos interactivos y análisis de datos del negocio.',
                'features' => [
                    'Dashboards analíticos',
                    'Tendencias de ventas',
                    'Análisis de inventario',
                    'Predicciones de demanda',
                ],
                'icon' => '📉',
            ],
            'alerts' => [
                'title' => 'Alertas de Stock',
                'description' => 'Notificaciones automáticas cuando el stock está bajo.',
                'features' => [
                    'Configuración de stock mínimo',
                    'Alertas por email',
                    'Dashboard de productos críticos',
                    'Notificaciones en tiempo real',
                ],
                'icon' => '🔔',
            ],
            'world_office' => [
                'title' => 'Integración WorldOffice',
                'description' => 'Exportación automática de movimientos a WorldOffice.',
                'features' => [
                    'Exportación contable',
                    'Sincronización de productos',
                    'Generación de documentos',
                    'Integración con sistema contable',
                ],
                'icon' => '💼',
            ],
            'geolocation' => [
                'title' => 'Geolocalización',
                'description' => 'Rastreo en tiempo real de conductores y vehículos.',
                'features' => [
                    'GPS en tiempo real',
                    'Historial de ubicaciones',
                    'Rutas recorridas',
                    'Control de tiempos de entrega',
                ],
                'icon' => '📍',
            ],
            'api' => [
                'title' => 'API REST',
                'description' => 'Acceso programático al sistema para integraciones externas.',
                'features' => [
                    'Endpoints REST completos',
                    'Autenticación con tokens',
                    'Documentación de API',
                    'Webhooks para notificaciones',
                ],
                'icon' => '🔗',
            ],
            'white_label' => [
                'title' => 'White-label',
                'description' => 'Personalización completa de marca para resellers.',
                'features' => [
                    'Logo personalizado',
                    'Colores de marca',
                    'Dominio propio',
                    'Portal de clientes personalizado',
                ],
                'icon' => '✨',
            ],
        ];
    }
}
