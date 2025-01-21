<?php

namespace Modules\JangKeyte\src\Http\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str; // Import the Str class
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Modules\JangKeyte\src\Facades\JangKeyteStub;

class JangKeyteCommand extends Command
{
    protected $signature = 'crud:generate {model}';

    protected $description = 'Generate CRUD operations for a model based on the database table';

    protected $tableName;

    protected $moduleName;

    protected $stubPath;

    protected $columns = array();

    public function handle()
    {
        $model = $this->argument('model');
        $this->tableName = Str::snake($model);

        // Check if the table exists with the model name
        if (!Schema::hasTable($this->tableName)) {
            // Add 's' to the end of the model name
            $this->tableName .= 's';

            // Check if the table exists with 's' appended
            if (!Schema::hasTable($this->tableName)) {
                // Table doesn't exist, ask the user for the table name
                $this->info("Bảng dữ liệu '$this->tableName' không tồn tại.");
                $this->tableName = $this->ask('Nhập tên bảng dữ liệu');
                if ($this->tableName == '') {
                    $this->tableName .= 's';
                }
            }
        }

        $columns = $this->ask('Nhập các cột trong bảng dữ liệu (cách nhau bởi dấu , )');
        foreach (explode(',', $columns ?? 'title') as $column) {
            $column_single = explode(':', $column);
            $this->columns[$column_single[0]] = $column_single[1] ?? 'string';
        }

        $this->moduleName = $this->ask('Tạo trong module nào');
        $this->moduleName = $this->moduleName ?? 'JangKeyte';

        $this->stubPath = 'Modules\JangKeyte\resources\stubs\\';

        /*        
        $columns = Schema::getColumnListing($this->tableName);
        */

        $this->migrationCodes($model, $this->moduleName, $this->stubPath, $this->columns);
        //$this->seederCodes($model, $this->moduleName, $this->stubPath, $this->columns);
        $this->modelCodes($model, $this->moduleName, $this->stubPath, $this->columns);
        $this->requestCodes($model, $this->moduleName, $this->stubPath, $this->columns);
        $this->controllerIndexCodes($model, $this->moduleName, $this->stubPath);
        $this->controllerUpdateCodes($model, $this->moduleName, $this->stubPath);
        /*$this->controllerSearchCodes($model, $this->moduleName, $this->stubPath);*/
        $this->repositoryCodes($model, $this->moduleName, $this->stubPath, $this->columns);
        $this->repositoryInterfaceCodes($model, $this->moduleName, $this->stubPath, $this->columns);
        $this->routeCodes($model, $this->moduleName, $this->stubPath);
        $this->indexViewCodes($model, $this->moduleName, $this->stubPath, $this->columns);
        $this->createEditCodes($model, $this->moduleName, $this->stubPath, $this->columns);
        $this->exportViewCodes($model, $this->moduleName, $this->stubPath, $this->columns);
        $this->filterCodes($model, $this->moduleName, $this->stubPath, $this->columns);
        /*

        
        $this->createViewCodes($model);
        $this->createShowCodes($model);
        */
    }

    // Start Model Codes==========================================================================
    public function modelCodes($model, $moduleName, $stubPath, $columns)
    {
        $modelName = ucfirst($model);

        // Create the directory if it doesn't exist
        $directoryPath = base_path("modules/{$moduleName}/src/Models");
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $fillable = '';
        foreach ($columns as $column => $type) {
            $fillable .= ($fillable == '') ? "'$column'" : ",\n" . "        '$column'";
        }

        JangKeyteStub::from($stubPath . 'model.stub')
            ->to($directoryPath)->name($modelName)->ext('php')
            ->replaces([
                'MODULENAME' => $moduleName,
                'MODELNAME' => $modelName,
                'FILLABLE' => $fillable
            ])->generate();

        // Inform the user about the successful addition of model
        $this->info("Model cho $modelName được thêm thành công.");
    }

    // Start Filter Codes==========================================================================
    public function filterCodes($model, $moduleName, $stubPath, $columns)
    {
        $modelName = ucfirst($model);

        // Create the directory if it doesn't exist
        $directoryPath = base_path("modules/{$moduleName}/src/Models");
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $filterdata = '';
        foreach ($columns as $column => $type) {
            $filterdata .= ($filterdata == '') ? "'" . $column . "'" : "," . "'" . $column . "'";
        }

        JangKeyteStub::from($stubPath . 'filter.stub')
            ->to($directoryPath)
            ->name($modelName)
            ->ext('php')
            ->replaces([
                'MODULENAME' => $moduleName,
                'MODELNAME' => $modelName,
                'FILTERDATA' => '[' . $filterdata . ']'
            ])->generate();

        // Inform the user about the successful addition of model
        $this->info("Filter cho $modelName được thêm thành công.");
    }

    // Start Migration Codes==========================================================================
    public function migrationCodes($model, $moduleName, $stubPath, $columns)
    {
        $modelName = ucfirst($model);
        $dateTime = date('Y_m_d_his');

        // Create the directory if it doesn't exist
        $directoryPath = base_path("modules/{$moduleName}/database/migrations/");
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $code = '';
        foreach ($columns as $column => $type) {
            $type = $this->getColumnTypes($type, 'migrate');
            if ($column != 'id' && $column != 'created_at' && $column != 'updated_at') {
                $code .= ($code == '') ? "\$table->$type('$column');" : "\n" . "            \$table->$type('$column');";
            }
        }

        JangKeyteStub::from($stubPath . 'migration.stub')
            ->to($directoryPath)->name($dateTime . '_create_' . $model . 's_table')->ext('php')
            ->replaces([
                'MODEL' => $model,
                'CODE' => $code ?? ''
            ])->generate();

        echo "\033[1;33mĐang tạo bảng:\033[0m {$model}s\n";
        $startTime = microtime(true);
        Artisan::call('migrate');
        $runTime = round(microtime(true) - $startTime, 2);
        echo "\033[0;32mTạo thành công bảng:\033[0m {$model}s ({$runTime} giây)\n";

        // Inform the user about the successful addition of model
        echo "Migration cho $modelName được thêm thành công.";
    }

    // Start Migration Codes==========================================================================
    public function seederCodes($model, $moduleName, $stubPath, $columns)
    {
        $modelName = ucfirst($model);
        $dateTime = date('Y_m_d_his');

        // Create the directory if it doesn't exist
        $directoryPath = base_path("modules/{$moduleName}/database/seeders/");
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $code = '';
        foreach ($columns as $column => $type) {
            $type = $this->getColumnTypes($type, 'seeder');
            if ($column != 'id' && $column != 'created_at' && $column != 'updated_at') {
                $code .= ($code == '') ? "'$column' => fake()->$type," : "\n" . "                '$column' => fake()->$type,";
            }
        }

        JangKeyteStub::from($stubPath . 'seeder.stub')
            ->to($directoryPath)->name($modelName . 'TableSeeder')->ext('php')
            ->replaces([
                'MODEL' => $model,
                'MODELNAME' => $modelName,
                'MODULENAME' => $moduleName,
                'CODE' => $code ?? ''
            ])->generate();

        addSeedsFrom($directoryPath);

        // Inform the user about the successful addition of model
        $this->info("Seeder cho $modelName được thêm thành công.");
    }

    // Start Validation Codes==========================================================================
    public function requestCodes($model, $moduleName, $stubPath, $columns)
    {
        $modelName = ucfirst($model);

        $rules = '';
        foreach ($columns as $column => $type) {
            if ($column != 'id' && $column != 'created_at' && $column != 'updated_at') {
                $rules .= ($rules == '') ? "'$column' => 'required'" : ",\n" . "            '$column' => 'required'";
            }
        }

        $messages = '';
        foreach ($columns as $column => $type) {
            if ($column != 'id' && $column != 'created_at' && $column != 'updated_at') {
                $messages .= ($messages == '') ? "'$column.required' => '$column không được để trống.'" : ",\n" . "            '$column.required' => '$column không được để trống.'";
            }
        }

        // Create the directory if it doesn't exist
        $directoryPath = base_path("modules/{$moduleName}/src/Http/Requests/{$modelName}");
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        JangKeyteStub::from($stubPath . 'request.stub')
            ->to($directoryPath)->name('Update' . $modelName . 'Request')->ext('php')
            ->replaces([
                'MODELNAME' => $modelName,
                'MODULENAME' => $moduleName,
                'RULES' => $rules ?? '',
                'MESSAGES' => $messages ?? '',
                'FILTERS' => $filters ?? ''
            ])->generate();

        // Inform the user about the successful addition of validations
        $this->info("Validations cho $model được thêm thành công.");
    }

    // Start Repository Codes=========================================================================
    public function repositoryCodes($model, $moduleName, $stubPath, $columns)
    {
        $modelName = ucfirst($model);

        // Create the directory if it doesn't exist
        $directoryPath = base_path("modules/{$this->moduleName}/src/Repositories/{$modelName}");
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $code = '';
        foreach ($columns as $column => $type) {
            if ($column != 'id' && $column != 'image' && $column != 'created_at' && $column != 'updated_at') {
                $code .= ($code == '') ? "\$obj->$column = \$request->$column;" : "\n                \$obj->$column = \$request->$column;";
            }
        }

        JangKeyteStub::from($stubPath . 'repository.stub')
            ->to($directoryPath)->name($modelName . 'Repository')->ext('php')
            ->replaces([
                'MODEL' => $model,
                'MODELNAME' => $modelName,
                'MODULENAME' => $moduleName,
                'CODE' => $code ?? ''
            ])->generate();

        $this->info("Repository cho $modelName được tạo thành công!");
    }
    // Start Repository Codes=========================================================================

    // Start RepositoryInterface Codes=========================================================================
    public function repositoryInterfaceCodes($model, $moduleName, $stubPath, $columns)
    {
        $modelName = ucfirst($model);

        // Create the directory if it doesn't exist
        $directoryPath = base_path("modules/{$this->moduleName}/src/Repositories/{$modelName}");
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        JangKeyteStub::from($stubPath . 'repository.interface.stub')
            ->to($directoryPath)->name($modelName . 'RepositoryInterface')->ext('php')
            ->replaces([
                'MODULENAME' => $moduleName,
                'MODELNAME' => $modelName,
            ])->generate();

        $this->info("RepositoryInterface cho $modelName được tạo thành công!");
    }
    // Start RepositoryInterface Codes=========================================================================

    // Start Index Controller Codes=========================================================================
    public function controllerIndexCodes($model, $moduleName, $stubPath)
    {
        $modelName = ucfirst($model);

        // Create the directory if it doesn't exist
        $directoryPath = base_path("modules/{$moduleName}/src/Http/Controllers/{$modelName}/");
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        JangKeyteStub::from($stubPath . 'controller.index.stub')
            ->to($directoryPath)->name($modelName . 'Controller')->ext('php')
            ->replaces([
                'MODEL' => $model,
                'MODELNAME' => $modelName,
                'MODULENAME' => $moduleName,
            ])->generate();

        $this->info("Controller cho $modelName được tạo thành công!");
    }
    // Start Index Controller Codes=========================================================================

    // Start Update Controller Codes=========================================================================
    public function controllerUpdateCodes($model, $moduleName, $stubPath)
    {
        $modelName = ucfirst($model);

        // Create the directory if it doesn't exist
        $directoryPath = base_path("modules/{$moduleName}/src/Http/Controllers/{$modelName}/");
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        JangKeyteStub::from($stubPath . 'controller.update.stub')
            ->to($directoryPath)->name('Update' . $modelName . 'Controller')->ext('php')
            ->replaces([
                'MODEL' => $model,
                'MODELNAME' => $modelName,
                'MODULENAME' => $moduleName,
            ])->generate();

        $this->info("Update Controller cho $modelName được tạo thành công!");
    }
    // Start Update Controller Codes=========================================================================

    // Start Search Controller Codes=========================================================================
    public function controllerSearchCodes($model, $moduleName, $stubPath)
    {
        $modelName = ucfirst($model);

        // Create the directory if it doesn't exist
        $directoryPath = base_path("modules/{$moduleName}/src/Http/Controllers/{$modelName}/");
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        JangKeyteStub::from($stubPath . 'controller.update.stub')
            ->to($directoryPath)->name('Search' . $modelName . 'Controller')->ext('php')
            ->replaces([
                'MODEL' => $model,
                'MODELNAME' => $modelName,
                'MODULENAME' => $moduleName,
            ])->generate();

        $this->info("Search Controller cho $modelName được tạo thành công!");
    }
    // Start Search Controller Codes=========================================================================

    // Start Route Codes===============================================================================
    public function routeCodes($model, $moduleName, $stubPath)
    {
        $modelName = ucfirst($model);

        // Create the directory if it doesn't exist
        $directoryPath = base_path("modules/{$moduleName}/routes/");
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        JangKeyteStub::from($stubPath . 'route.stub')
            ->to($directoryPath)->name(strtolower($model))->ext('php')
            ->replaces([
                'MODEL' => strtolower($model),
                'MODELNAME' => $modelName,
                'MODULENAME' => $moduleName,
            ])->generate();

        $this->info("Định tuyến CRUD cho $model đã được thêm thành công vào modules/{$moduleName}/routes/" . $model . ".php.");
    }
    // Start Route Codes===============================================================================

    // Start Index Blade Codes=======================================================================
    public function indexViewCodes($model, $moduleName, $stubPath, $columns)
    {
        $models = $model . 's';
        $modelName = ucfirst($model);

        // Create the directory if it doesn't exist
        $directoryPath = base_path("modules/{$moduleName}/resources/views/{$model}/");
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $heading = '';
        $body = '';
        foreach ($columns as $column => $type) {
            if ($column != 'id' && $column != 'created_at' && $column != 'updated_at') {
                $headerColumn = ucfirst($column);
                $heading .= ($heading == '') ? "<th>{{ __('$headerColumn') }}</th>" : "\n                                    <th>{{ __('$headerColumn') }}</th>";
                if ($type == 'file') {
                    $body .= ($body == '') ? "<td><img src='{{ asset('storage/uploads/$models/' . (\$item->image ?? 'default.png')) }}' alt='' style='height:100px'></td>" : "\n                                    <td><img src='{{ asset('storage/uploads/$models/' . (\$item->image ?? 'default.png')) }}' alt='' style='height:100px'></td>";
                } else {
                    $body .= ($body == '') ? "<td>{{ \$item->$column }}</td>" : "\n                                    <td>{{ \$item->$column }}</td>";
                }
            }
        }

        JangKeyteStub::from($stubPath . 'view.index.stub')
            ->to($directoryPath)->name('index.blade')->ext('php')
            ->replaces([
                'MODEL' => $model,
                'MODELNAME' => $modelName,
                'MODULENAME' => $moduleName,
                'HEADING' => $heading ?? '',
                'BODY' => $body ?? '',
            ])->generate();

        $this->info("View danh sách $model được thêm thành công.");
    }

    // Start Create blade Codes===================================================================
    public function createViewCodes($model)
    {
        $modelName = ucfirst($model);
        $tableName = $this->tableName ?? Str::snake($model);

        $columnTypes = $this->getColumnTypes($tableName);

        $create = "@extends('{$this->moduleName}::layout.app')\n\n";
        $create .= "@section('heading', __('{$modelName}'))\n\n";
        $create .= "@section('button')\n";
        $create .= "    <a href='{{ route('{$model}_index') }}' class='btn btn-primary btn-sm ms-2'><i class='bi bi-folder-check'></i> {{ __('View All') }}</a>\n";
        $create .= "@endsection\n\n";
        $create .= "@section('main_content')\n";
        $create .= "<form method='POST' action='{{ URL('$model/store') }}'>\n";
        $create .= "    @csrf\n";
        $create .= "    <div class='card'>\n";
        $create .= "        <div class='card-body'>\n";
        $create .= "            <div class='row'>\n";

        foreach ($columnTypes as $columnName => $columnType) {
            if ($columnName != 'id' && $columnName != 'created_at' && $columnName != 'updated_at') {
                $inputField = $this->generateInputField($columnName, $columnType);
                $create .= $inputField;
            }
        }

        $create .= "            </div>\n";
        $create .= "        </div>\n";
        $create .= "    </div>\n";
        $create .= "    <button type='submit' class='btn btn-success'>Tạo mới</button>\n";
        $create .= "</form>\n";
        $create .= "@endsection";
        $directoryPath = base_path("modules/{$this->moduleName}/resources/views/{$model}/");

        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $controllerFilePath = base_path("modules/{$this->moduleName}/resources/views/{$model}/create.blade.php");
        file_put_contents($controllerFilePath, $create);

        $this->info("View tạo mới $model được thêm thành công.");
    }
    // End Create blade Codes===================================================================

    // Start Edit Blade Codes====================================================================
    public function createEditCodes($model, $moduleName, $stubPath, $columns)
    {
        $modelName = ucfirst($model);

        // Create the directory if it doesn't exist
        $directoryPath = base_path("modules/{$moduleName}/resources/views/{$model}/");
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $code = '';
        foreach ($columns as $column => $type) {
            if ($column != 'id' && $column != 'created_at' && $column != 'updated_at') {
                $code .= $this->generateInputField($column, $type);
            }
        }

        JangKeyteStub::from($stubPath . 'view.edit.stub')
            ->to($directoryPath)->name('edit.blade')->ext('php')
            ->replaces([
                'MODEL' => $model,
                'MODELNAME' => $modelName,
                'MODULENAME' => $moduleName,
                'CODE' => $code
            ])->generate();

        $this->info("View cập nhật $model được thêm thành công.");
    }
    // End Edit Blade Codes ====================================================================

    // Start Export Blade Codes====================================================================
    public function exportViewCodes($model, $moduleName, $stubPath, $columns)
    {
        $models = $model . 's';
        $modelName = ucfirst($model);

        // Create the directory if it doesn't exist
        $directoryPath = base_path("modules/{$moduleName}/resources/views/{$model}/");
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $heading = '';
        $body = '';
        foreach ($columns as $column => $type) {
            if ($column != 'id' && $column != 'created_at' && $column != 'updated_at') {
                $headerColumn = ucfirst($column);
                $heading .= ($heading == '') ? "<th>{{ __('$headerColumn') }}</th>" : "\n                                    <th>{{ __('$headerColumn') }}</th>";
                if ($type == 'file') {
                    $body .= ($body == '') ? "<td>{{ asset('storage/uploads/$models/' . (\$item->image ?? 'default.png')) }}</td>" : "\n                                    <td><img src='{{ asset('storage/uploads/$models/' . (\$item->image ?? 'default.png')) }}' alt='' style='height:100px'></td>";
                } else {
                    $body .= ($body == '') ? "<td>{{ \$item->$column }}</td>" : "\n                                    <td>{{ \$item->$column }}</td>";
                }
            }
        }

        JangKeyteStub::from($stubPath . 'view.index.stub')
            ->to($directoryPath)->name('index.blade')->ext('php')
            ->replaces([
                'HEADING' => $heading ?? '',
                'BODY' => $body ?? '',
            ])->generate();

        $this->info("View xuất dữ liệu $model được thêm thành công.");
    }
    // End Export Blade Codes ====================================================================

    // Start Show Blade Codes ==================================================================
    public function createShowCodes($model)
    {
        $modelName = ucfirst($model);
        $tableName = $this->tableName ?? Str::snake($model);

        $columnTypes = $this->getColumnTypes($tableName);

        $create = "@extends('{$this->moduleName}::layout.app')\n\n";
        $create .= "@section('heading', __('{$modelName}'))\n\n";
        $create .= "@section('button')\n";
        $create .= "    <a href='{{ route('{$model}_index') }}' class='btn btn-primary btn-sm ms-2'><i class='bi bi-folder-check'></i> {{ __('View All') }}</a>\n";
        $create .= "@endsection\n\n";
        $create .= "@section('main_content')\n";

        // Assuming you have a $item variable that contains the record to display
        $create .= "<table class='table'>\n";
        $create .= "    <thead>\n";
        $create .= "        <tr>\n";

        foreach ($columnTypes as $columnName => $columnType) {
            if ($columnName != 'id' && $columnName != 'created_at' && $columnName != 'updated_at') {
                $labelText = ucwords(str_replace('_', ' ', $columnName));
                $create .= "            <th>{$labelText}</th>\n";
            }
        }

        $create .= "        </tr>\n";
        $create .= "    </thead>\n";
        $create .= "    <tbody>\n";
        $create .= "        <tr>\n";

        foreach ($columnTypes as $columnName => $columnType) {
            if ($columnName != 'id' && $columnName != 'created_at' && $columnName != 'updated_at') {
                $create .= "            <td>{{ \${$model}_single->{$columnName} }}</td>\n";
            }
        }

        $create .= "        </tr>\n";
        $create .= "    </tbody>\n";
        $create .= "</table>\n";
        $create .= "@endsection";

        $directoryPath = base_path("modules/{$this->moduleName}/resources/views/{$model}/");

        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $controllerFilePath = base_path("modules/{$this->moduleName}/resources/views/{$model}/show.blade.php");
        file_put_contents($controllerFilePath, $create);

        $this->info("View chi tiết $model được thêm thành công.");
    }
    // Start Show Blade Codes====================================================================

    private function getColumnTypes($type, $useFor = false)
    {
        if ($useFor == 'migrate') {
            // Define a mapping for fixing column type data
            $columnTypeMappings = [
                'int' => 'integer',
                'bool' => 'boolean',
                'file' => 'string'
            ];

            return $columnTypeMappings[$type] ?? $type ?? 'string';
        } else if ($useFor == 'seeder') {
            // Define a mapping for column types to get fake data
            $columnTypeMappings = [
                'string' => 'name()',
                'text' => 'paragraph(2)',
                'int' => 'randomDigitNotNull()',
                'integer' => 'randomDigitNotNull()',
                'bool' => 'boolean()',
                'boolean' => 'boolean()',
                'file' => 'file(public_path("storage/uploads/tmp/"), public_path("storage/uploads/' . $this->tableName . '/"), false)',
                'date' => 'date()',
                'datetime' => 'dateTimeBetween("-1 week", "+3 week")'
            ];

            return $columnTypeMappings[$type] ?? 'lexify()';
        } else {
            // Define a mapping for column types to HTML input types
            $columnTypeMappings = [
                'string' => 'text',
                'text' => 'textarea',
                'integer' => 'number',
                'decimal' => 'number',
                'boolean' => 'checkbox',
                'file' => 'file',
                'date' => 'date',
                'datetime' => 'datetime',
                //'datetime' => 'datetime-local',
                'timestamp' => 'date',
                'email' => 'email',
                'password' => 'password',
            ];

            return $columnTypeMappings[$type] ?? 'text';
        }
    }

    private function generateInputField($columnName, $columnType)
    {
        // Remove any brackets from the column type (e.g., int(11) => int, decimal(8,2) => decimal)
        $columnType = preg_replace('/\([^)]*\)/', '', $columnType);

        // Determine the HTML input type based on the column type
        $inputType = $this->getColumnTypes($columnType) ?? 'text';

        // Format the label text (replace underscores with spaces and capitalize words)
        $labelText = ucwords(str_replace('_', ' ', $columnName));

        // Generate the input field HTML
        $inputField = "\n                        <div class='row mb-3'>\n";
        $inputField .= "                            <label for='{$columnName}' class='col-sm-2 col-form-label'>{{ __('{$labelText}') }}</label>\n";
        $inputField .= "                            <div class='col-sm-10'>\n";
        switch ($inputType) {
            case 'checkbox':
                $inputField .= "                                <div class='form-check form-switch mt-2'>\n";
                $inputField .= "                                    <input class='form-check-input' type='checkbox' role='switch' name='{$columnName}'>\n";
                $inputField .= "                                    <label class='form-check-label' for='{$columnName}'>{$columnName}</label>\n";
                $inputField .= "                                </div>\n";
                break;
            case 'textarea':
                $inputField .= "                                <textarea name='{$columnName}' class='form-control @error('{$columnName}') is-invalid @enderror' rows='4'>{{ old('$columnName', \$item->$columnName) }}</textarea>\n";
                break;
            case 'file':
                $inputField .= "                                <input type='{$inputType}' name='{$columnName}' class='form-control @error('{$columnName}') is-invalid @enderror' value='{{ old('$columnName', \$item->$columnName) }}'/>\n";
                break;
            case 'date':
                $inputField .= "                                <input type='{$inputType}' name='{$columnName}' class='form-control @error('{$columnName}') is-invalid @enderror' value='{{ old('$columnName', \$item->$columnName ?? date('Y-m-d')) }}'/>\n";
                break;
            case 'datetime':
                $inputField .= "                                <input type='{$inputType}' name='{$columnName}' class='form-control @error('{$columnName}') is-invalid @enderror' value='{{ old('$columnName', \$item->$columnName ?? date('Y-m-d h:i:s')) }}'/>\n";
                break;
            default:
                $inputField .= "                                <input type='{$inputType}' name='{$columnName}' class='form-control @error('{$columnName}') is-invalid @enderror' value='{{ old('$columnName', \$item->$columnName) }}'/>\n";
        }
        $inputField .= "                                @error('{$columnName}') <div class='invalid-feedback'>{{ \$message }}</div> @enderror\n";
        $inputField .= "                            </div>\n";
        $inputField .= "                        </div>\n";

        return $inputField;
    }
}
