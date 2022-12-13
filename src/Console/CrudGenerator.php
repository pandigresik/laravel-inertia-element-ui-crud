<?php

namespace XT\ElementUiCrud\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

    protected function getFields(){
        $modelNameArg = $this->argument('model');
        $modelName = "App\Models\\$modelNameArg";
        $model = new $modelName;
        $columns = $model->getFillable();

        $allColumns = [];
        foreach ($columns as $columnName){
            if (in_array($columnName, ['id','created_at','updated_at'])){
                continue;
            }
            $column = Schema::getConnection()->getDoctrineColumn($model->getTable(), $columnName);
            $datatype = $column->getType()->getName();
            $length = $column->getLength();
            $type = 'text';
            if ($datatype == 'text'){
                $type = 'textarea';
            } else if ($datatype == 'boolean'){
                $type = 'switch';
            }
            $allColumns[] = [
                'name' => $columnName,
                'datatype' => $datatype,
                'required' => $column->getNotnull(),
                'type' => $type,
                'length' => $length
            ];
        }

        return $allColumns;
//        $this->info(print_r($allColumns));exit();
    }

    protected function getStub($type)
    {
        $stubFilePath = __DIR__ . '/../../stubs/'.$type.'.stub';
        return file_get_contents($stubFilePath);
    }

    protected function view($name, $template){
        $columns = $this->getFields();
        $listFields = [
            [
                'name' => 'id',
                'title' => '#Id',
                'sortable' => true,
                'width' => '80px'
            ]
        ];
        $fields = [];
        foreach ($columns as $column){
            $displayName = Str::ucfirst(str_replace(['-','_'],[' ',' '],$column['name']));
            $listFields[] = [
                'name' => $column['name'],
                'title' => $displayName,
                'sortable' => true,
            ];

            $fields[] = [
                'property' => $column['name'],
                'label' => $displayName,
                'type' => $column['type'],
            ];
        }
        $layoutVar = 'AppLayout';
        $layoutFile = 'AppLayout';
        if (strtolower($template) == 'breeze'){
            $layoutFile = 'Authenticated';
            $layoutVar = 'BreezeAuthenticatedLayout';
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
