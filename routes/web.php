<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| The ERP is a modular monolith: every module registers its own routes in
| routes/modules/{module}.php and they are all loaded here.
|
*/

foreach (glob(__DIR__.'/modules/*.php') as $moduleRoutes) {
    require $moduleRoutes;
}
