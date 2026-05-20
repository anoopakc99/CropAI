<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MasterController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\AreaLevelingController;
use App\Http\Controllers\Admin\PreLandPreparationController;
use App\Http\Controllers\Admin\PreIrrigationController;
use App\Http\Controllers\Admin\LandPreparationController;
use App\Http\Controllers\Admin\SowingOperationController;
use App\Http\Controllers\Admin\InterCultureController;
use App\Http\Controllers\Admin\CropProtectionController;
use App\Http\Controllers\Admin\ActivityMonitoringController;







//==================================================================================================================================
Route::get('/', [LoginController::class, 'showLoginForm'])->name('login.form');
Route::post('/ccbf-dashboard', [LoginController::class, 'loginDashboard'])->name('admins.dashboard');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');
//===================================================================================================================================
// Admin Dashboard
//===================================================================================================================================

Route::middleware(['role'])->group(function () {
    Route::get('/ccbf-admin', [DashboardController::class, 'ccbf_dashboard'])->name('dashboard');
    // Admin MASTER consolidated
      Route::get('master/consolidated', [MasterController::class, 'consolidated'])->name('consolidated');
//===============================================================================================================================
      // Admin MASTER tractor
      Route::prefix('master')->group(function () {
         Route::get('/tractor', [MasterController::class, 'indexTractor'])->name('tractors.index');
         Route::post('/tractor/store', [MasterController::class, 'storeTractor'])->name('tractors.store');
         Route::post('/tractor/update/{id}', [MasterController::class, 'updateTractor'])->name('tractors.update');
         Route::delete('/tractor/destroy/{id}', [MasterController::class, 'destroyTractor'])->name('tractors.destroy');
         Route::get('/tractor/search', [MasterController::class, 'searchTractor'])->name('tractors.search');
     });
//==============================================================================================================================================
// Admin MASTER diesel
Route::get('/diesels', [MasterController::class, 'indexdiesel'])->name('diesels.index');
Route::post('/diesels/store', [MasterController::class, 'storediesel'])->name('diesels.store');
Route::post('/diesels/update/{id}', [MasterController::class, 'updatediesel'])->name('diesels.update');
Route::delete('/diesels/destroy/{id}', [MasterController::class, 'destroydiesel'])->name('diesels.destroy');
//==============================================================================================================================================

         // Master land routes
         Route::get('/land', [MasterController::class, 'indexland'])->name('land.index');
         Route::post('/land', [MasterController::class, 'storeland'])->name('land.store');
         
         Route::get('/land/{id}/edit', [MasterController::class, 'editland'])->name('land.edit');
         Route::put('/land/{id}', [MasterController::class, 'updateland'])->name('land.update');
         Route::delete('/land/{id}', [MasterController::class, 'destroyland'])->name('land.destroy');
         Route::get('/get-plots-by-block', [MasterController::class, 'getPlotsByBlockland'])->name('get.plots.by.block');
         Route::get('/get-plot-area', [MasterController::class, 'getPlotArealand'])->name('get.plot.area');
    //==============================================================================================================================================

   // MASTER SEED ROUTE 
   Route::get('/seeds', [MasterController::class, 'seedIndex'])->name('seeds.index');
   Route::post('/seeds', [MasterController::class, 'storeseed'])->name('seeds.store');
   Route::put('/seeds/{id}', [MasterController::class, 'updateseed'])->name('seeds.update');
   Route::delete('/seeds/{id}', [MasterController::class, 'destroyseed'])->name('seeds.destroy');
   Route::get('/seeds/search', [MasterController::class, 'searchseed'])->name('seeds.search');
//==============================================================================================================================================

  
       // MASTER machine ROUTE 
    Route::get('machines', [MasterController::class, 'machine'])->name('machines.index');
    Route::post('/machines', [MasterController::class, 'store'])->name('machines.store');
Route::put('/machines/{machine}', [MasterController::class, 'update'])->name('machines.update');
Route::delete('/machines/{id}', 'MasterController@destroy')->name('machines.destroy');
Route::get('/machines/{machine}/details', [MasterController::class, 'getDetails'])->name('machines.getDetails');
//==============================================================================================================================================

       // Master fertilizer routes
       Route::get('/fertilizers', [MasterController::class, 'indexfertilizerS'])->name('fertilizer.index');
       Route::post('/fertilizers/store', [MasterController::class, 'storefertilizers'])->name('fertilizer.store');
       Route::get('/fertilizer/{id}/edit', [MasterController::class, 'editfertilizer'])->name('fertilizer.edit');
       Route::put('/fertilizer/update/{id}', [MasterController::class, 'updatefertilizers'])->name('fertilizer.update');
       
       Route::delete('fertilizers/delete/{id}', [MasterController::class, 'deletefertilizers'])->name('fertilizer.de');
       //==============================================================================================================================================


         // Master manpower routes
         Route::get('/master-manpower', [MasterController::class, 'indexmanpower'])->name('master.manpower');
Route::post('/master-manpower/store', [MasterController::class, 'storemanpower'])->name('master.manpower.store');
Route::get('/master-manpower/edit/{id}', [MasterController::class, 'editmanpower'])->name('master.manpower.edit');
Route::post('/master-manpower/update/{id}', [MasterController::class, 'updatemanpower'])->name('master.manpower.update');
Route::delete('/master-manpower/delete/{id}', [MasterController::class, 'destroymanpower'])->name('master.manpower.delete');
//==============================================================================================================================================

       
//master block routes
    Route::get('/master-bloc', [MasterController::class, 'indexblock'])->name('master_bloc.index');
Route::post('/master-bloc/store', [MasterController::class, 'storeblock'])->name('master_bloc.store');
Route::get('/master-bloc/edit/{id}', [MasterController::class, 'edit'])->name('master_bloc.edit');
Route::post('/master-bloc/update/{id}', [MasterController::class, 'updateblock'])->name('master_bloc.update');
Route::delete('/master-bloc/delete/{id}', [MasterController::class, 'destroyblock'])->name('master_bloc.destroy');
//==============================================================================================================================================

// master area-leveling routes
Route::get('/area-leveling', [AreaLevelingController::class, 'index'])->name('area.leveling');
//==============================================================================================================================================

//Pre Land Preparation routes
Route::get('/pre-land-preparation', [PreLandPreparationController::class, 'index'])->name('preLand.index');
Route::get('/pre-land-preparation/search', [PreLandPreparationController::class, 'search'])->name('preLand.search');
//==============================================================================================================================================

//Pre Irrigation routes
Route::get('/pre-irrigation', [PreIrrigationController::class, 'index'])->name('pre_irrigation.index');
//==============================================================================================================================================
Route::get('/master_irrigation_types', [MasterController::class, 'indexmaster_irrigation_types'])->name('master_irrigation_types.index');
Route::post('/master_irrigation_types/store', [MasterController::class, 'storedmaster_irrigation_types'])->name('master_irrigation_types.store');
Route::post('/master_irrigation_types/update/{id}', [MasterController::class, 'updatedmaster_irrigation_types'])->name('master_irrigation_types.update');
Route::delete('/master_irrigation_types/destroy/{id}', [MasterController::class, 'destroymaster_irrigation_types'])->name('master_irrigation_types.destroy');
//==============================================================================================================================================
Route::get('/master_capacities', [MasterController::class, 'indexMasterCapacities'])->name('capacities.index');
Route::post('/capacities/store', [MasterController::class, 'storeMasterCapacities'])->name('capacities.store');
Route::post('/capacities/update/{id}', [MasterController::class, 'updateMasterCapacities'])->name('capacities.update');
Route::delete('/capacities/destroy/{id}', [MasterController::class, 'destroyMasterCapacities'])->name('capacities.destroy');
//==============================================================================================================================================
// Water Sources
Route::get('/water_sources', [MasterController::class, 'indexWaterSource'])->name('water_sources.index');
Route::post('/water_sources/store', [MasterController::class, 'storeWaterSource'])->name('water_sources.store');
Route::post('/water_sources/update/{id}', [MasterController::class, 'updateWaterSource'])->name('water_sources.update');
Route::delete('/water_sources/destroy/{id}', [MasterController::class, 'destroyWaterSource'])->name('water_sources.destroy');

//

   // users management
//    Route::get('/user', [UsersController::class, 'index'])->name('users.index');
//    Route::post('/users/store', [UsersController::class, 'store'])->name('users.store');
//    Route::get('/users/edit/{id}', [UsersController::class, 'edit'])->name('users.edit');
//    Route::put('/users/update/{id}', [UsersController::class, 'update'])->name('users.update');
//    Route::delete('/users/delete/{id}', [UsersController::class, 'destroy'])->name('users.destroy');
//    Route::get('/get-plots-by-block', [UsersController::class, 'getPlotsByBlock'])->name('get.plots.by.block');
//    Route::get('/get-area-by-plot', [UsersController::class, 'getAreaByPlot'])->name('get.area.by.plot');
// User Management Routes
  // User CRUD routes
  Route::get('/user', [UsersController::class, 'index'])->name('users.index');
  Route::post('/users', [UsersController::class, 'store'])->name('users.store');
  Route::get('/users/{id}/edit', [UsersController::class, 'edit'])->name('users.edit');
  Route::put('/users/{id}', [UsersController::class, 'update'])->name('users.update');
  Route::delete('/users/{id}', [UsersController::class, 'destroy'])->name('users.destroy');
  
  // API routes for dynamic content
  Route::get('/get-plots/{block}', [UsersController::class, 'getPlots']);
  Route::get('/get-area', [UsersController::class, 'getArea']);
//============
Route::get('/land-preparation', [LandPreparationController::class, 'index'])->name('land-preparation.index');

//=========================================================================
Route::get('/sowing-operations', [SowingOperationController::class, 'index'])->name('sowing_operations.index');
    Route::get('/sowing-operations/search', [SowingOperationController::class, 'search'])->name('sowing_operations.search');
    Route::get('/inter-culture', [InterCultureController::class, 'index'])->name('inter-culture.index');
Route::get('/inter-culture/search', [InterCultureController::class, 'search'])->name('inter-culture.search');
//===================================
Route::get('/crop-protection', [CropProtectionController::class, 'index'])->name('crop.protection.index');
//==========================================
Route::get('/activity-monitoring', [ActivityMonitoringController::class, 'index'])->name('activity.monitoring');
});