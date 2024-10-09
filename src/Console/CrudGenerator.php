<?php

namespace XT\ElementUiCrud\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class CrudGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:generator
    {name : Class (singular) for example User} {model : Model class}  {--t|template=jetstream : Template jetstream or breeze}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create CRUD operations';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Generating files...');

        $name = $this->argument('name');
        $template = $this->option('template');

        $this->view($name, $template);
        $this->controller($name);
        $this->request($name);

        File::append(base_path('routes/web.php'), 'Route::resource(\'' . Str::plural(strtolower($name)) . "', \App\Http\Controllers\\".$name."Controller::class);".PHP_EOL);

        Artisan::call('route:clear');
        $this->info('Files generated');
        return 0;
    }

    protected function readColumnsFromTable($tableName) {

        $indexes = collect(Schema::getIndexes($tableName));
        return collect(Schema::getColumns($tableName))->map(function($column) use ($tableName, $indexes) {
            $columnName = $column['name'];
            $nullable = boolval($column['nullable']);
            $defaultValue = $column['default'];
            $length = str_replace(array( $column['type_name'], '(', ')' ), '', $column['type']);
        

            //Checked unique index
            $columnUniqueIndexes = $indexes->filter(function($index) use ($columnName) {
                $isUnique = boolval($index['unique']);
                $isPrimary = boolval($index['primary']);
                return in_array($columnName, $index['columns']) && ($isUnique && !$isPrimary);
            });

            $datatype = Schema::getColumnType($tableName, $columnName);
            

            $type = 'text';
            if ($datatype == 'text'){
                $type = 'textarea';
            } else if ($datatype == 'boolean'){
                $type = 'switch';
            } else if ( ($datatype == 'tinyint' && $length == 1) || ($datatype == 'tinyint' && $columnName == 'status') ){
                $type = 'switch';
            }

            return [
                'name' => $columnName,
                'datatype' => $datatype,
                'length' => $length,
                'title' => Str::title(str_replace('_', ' ', $columnName)),
                'required' => !$nullable,
                'type' => $type,
                'unique' => $columnUniqueIndexes->count() > 0,
                'defaultValue' => $defaultValue,
            ];
        });
    }

    protected function getFields(){
        $modelNameArg = $this->argument('model');
        $modelName = "App\Models\\$modelNameArg";
        $model = new $modelName;
        $tableName = $model->getTable();

        return self::readColumnsFromTable($tableName)->jsonSerialize();
    }

    protected function getStub($type)
    {
        $stubFilePath = __DIR__ . '/../../stubs/'.$type.'.stub';
        return file_get_contents($stubFilePath);
    }

    protected function view($name, $template){
        $columns = $this->getFields();
        $listFields = [
            [ 'name' => 'id', 'title' => '#Id', 'width' => '80px' ]
        ];
        $fields = [];
        foreach ($columns as $column){
            $displayName = Str::ucfirst(str_replace(['-','_'],[' ',' '],$column['name']));
            $listFields[] = [ 'name' => $column['name'], 'title' => $displayName ];

            $fields[] = [ 'property' => $column['name'], 'label' => $displayName, 'type' => $column['type'] ];
        }
        $layoutVar = 'AppLayout';
        $layoutFile = 'AppLayout';
        if (strtolower($template) == 'breeze'){
            $layoutFile = 'AuthenticatedLayout';
            $layoutVar = 'AuthenticatedLayout';
        }

        $entityNamePluralUcFirst = Str::ucfirst(Str::plural($name));
        $listTemplate = str_replace(
            [
                '{{LayoutFile}}',
                '{{LayoutVar}}',
                '{{listFields}}',
                '{{entityNamePluralLowerCase}}',
                '{{entityNamePluralUcFirst}}',
            ],
            [
                $layoutFile,
                $layoutVar,
                json_encode($listFields, JSON_PRETTY_PRINT),
                strtolower(Str::plural($name)),
                $entityNamePluralUcFirst,
            ],
            $this->getStub('List.vue')
        );

        $createTemplate = str_replace(
            [
                '{{LayoutFile}}',
                '{{LayoutVar}}',
                '{{createFields}}',
                '{{entityNamePluralLowerCase}}',
                '{{entityNamePluralUcFirst}}',
            ],
            [
                $layoutFile,
                $layoutVar,
                json_encode($fields, JSON_PRETTY_PRINT),
                strtolower(Str::plural($name)),
                $entityNamePluralUcFirst,
            ],
            $this->getStub('Create.vue')
        );

        $updateTemplate = str_replace(
            [
                '{{LayoutFile}}',
                '{{LayoutVar}}',
                '{{updateFields}}',
                '{{entityNamePluralLowerCase}}',
                '{{entityNamePluralUcFirst}}',
            ],
            [
                $layoutFile,
                $layoutVar,
                json_encode($fields, JSON_PRETTY_PRINT),
                strtolower(Str::plural($name)),
                $entityNamePluralUcFirst,
            ],
            $this->getStub('Update.vue')
        );

        $path = resource_path("/js/Pages/{$entityNamePluralUcFirst}");
        if(!file_exists($path))
            mkdir($path, 0777, true);

        file_put_contents("{$path}/List.vue", $listTemplate);
        file_put_contents("{$path}/Create.vue", $createTemplate);
        file_put_contents("{$path}/Update.vue", $updateTemplate);
    }

    protected function controller($name)
    {
        $modelNameArg = $this->argument('model');
        $controllerTemplate = str_replace(
            [
                '{{entityName}}',
                '{{modelName}}',
                '{{searchFields}}',
                '{{entityNamePluralLowerCase}}',
                '{{entityNameSingularLowerCase}}',
                '{{entityNamePluralUcFirst}}',
                '{{entityNameSingularUcFirst}}'
            ],
            [
                $name,
                Str::ucfirst($modelNameArg),
                '',
                strtolower(Str::plural($name)),
                strtolower($name),
                Str::ucfirst(Str::plural($name)),
                Str::ucfirst($name),
            ],
            $this->getStub('Controller')
        );

        file_put_contents(app_path("/Http/Controllers/{$name}Controller.php"), $controllerTemplate);
    }

    protected function request($name)
    {
        $columns = $this->getFields();
        $str = '';
        foreach ($columns as $column){
            $params = [];
            if ($column['required']){
                $params[] = 'required';
            }
            if ($column['datatype'] == 'string'){
                $params[] = 'max:'.$column['length'];
            } else {
                continue;
            }
            $str .= "\t\t\t'".$column['name']."' => [
                '".implode("',".PHP_EOL."\t\t\t\t'", $params)."'
            ],".PHP_EOL;
        }


        $storeRequestTemplate = str_replace(
            ['{{requestType}}','{{modelName}}','{{rulesContent}}'],
            ['Store',$name,$str],
            $this->getStub('Request')
        );

        $updateRequestTemplate = str_replace(
            ['{{requestType}}','{{modelName}}','{{rulesContent}}'],
            ['Update',$name,$str],
            $this->getStub('Request')
        );


        $folderName = Str::ucfirst($name);

        if(!file_exists($path = app_path('/Http/Requests')))
            mkdir($path, 0777, true);

        if(!file_exists($path = app_path("/Http/Requests/{$folderName}")))
            mkdir($path, 0777, true);


        file_put_contents(app_path("/Http/Requests/{$folderName}/Store{$name}Request.php"), $storeRequestTemplate);
        file_put_contents(app_path("/Http/Requests/{$folderName}/Update{$name}Request.php"), $updateRequestTemplate);
    }
}
