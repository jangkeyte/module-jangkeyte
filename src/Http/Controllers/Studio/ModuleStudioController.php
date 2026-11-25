<?php

namespace JangKeyte\Http\Controllers\Studio;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use JangKeyte\Http\Requests\Studio\StoreCrudStudioRequest;
use JangKeyte\Services\Generator\CrudGeneratorService;
use JangKeyte\Services\Generator\FieldDefinition;
use JangKeyte\Services\Generator\ModelDiscoveryService;
use JangKeyte\Services\ModuleStudioService;
use JangKeyte\Support\ModuleLoader;
use RuntimeException;

class ModuleStudioController extends Controller
{
    public function __construct(
        private readonly ModuleStudioService $studioService,
        private readonly CrudGeneratorService $crudGenerator,
        private readonly ModelDiscoveryService $modelDiscovery,
        private readonly ModuleLoader $moduleLoader,
    ) {}

    public function index(Request $request): View
    {
        $builderEnabled = function_exists('jangkeyte_builder_enabled') && jangkeyte_builder_enabled();
        $user = $request->user();
        $isSuperAdmin = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();

        return view('JangKeyte::studio.index', [
            'modules' => $this->studioService->listModulesWithSchemas(),
            'builderEnabled' => $builderEnabled,
            'canManageBuilder' => $builderEnabled && $isSuperAdmin,
        ]);
    }

    public function createCrud(Request $request): View
    {
        $defaultModule = $request->query('module');

        return view('JangKeyte::studio.create_crud', [
            'existingModules' => array_keys($this->moduleLoader->allModules()),
            'availableModels' => $this->modelDiscovery->availableModels(),
            'fieldTypes' => FieldDefinition::ALLOWED_TYPES,
            'presetIcons' => [
                'bi bi-collection',
                'bi bi-bus-front',
                'bi bi-box-seam',
                'bi bi-journal-text',
                'bi bi-cart',
                'bi bi-calendar-event',
                'bi bi-geo-alt',
                'bi bi-people',
                'bi bi-tags',
                'bi bi-building',
                'bi bi-file-earmark-text',
                'bi bi-cash-stack',
                'bi bi-star',
                'bi bi-images',
                'bi bi-truck',
                'bi bi-map',
                'bi bi-briefcase',
                'bi bi-gear',
            ],
            'defaultModule' => is_string($defaultModule) ? trim($defaultModule) : '',
        ]);
    }

    public function storeCrud(StoreCrudStudioRequest $request): RedirectResponse
    {
        try {
            $definition = $request->toCrudDefinition();
            $this->crudGenerator->generate($definition);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.modules.index')
            ->with('success', __('CRUD :model in module :module generated successfully.', [
                'model' => $definition->modelName,
                'module' => $definition->moduleName,
            ]));
    }

    public function toggle(string $module): RedirectResponse
    {
        try {
            $enabled = $this->studioService->toggleModule($module);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return redirect()
                ->route('admin.modules.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.modules.index')
            ->with('success', $enabled
                ? __('Module :module enabled.', ['module' => $module])
                : __('Module :module disabled.', ['module' => $module]));
    }

    public function destroy(Request $request, string $module): RedirectResponse
    {
        $dropTables = $request->boolean('drop_tables');

        try {
            $this->studioService->deleteModule($module, $dropTables);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return redirect()
                ->route('admin.modules.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.modules.index')
            ->with('success', __('Module :module deleted successfully.', ['module' => $module]));
    }
}
