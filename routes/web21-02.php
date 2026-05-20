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
use App\Http\Controllers\Admin\PostIrrigationController;
use App\Http\Controllers\Admin\FertilizerRecordController;
use App\Http\Controllers\Admin\CostAnalysisController;
use App\Http\Controllers\Admin\HayMakingController;
use App\Http\Controllers\Admin\SilageMakingController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\HarvestStoreManageController;
use App\Http\Controllers\ContactFarmingController;
use App\Http\Controllers\CropCycleController;
use App\Http\Controllers\Admin\MasterStockController;
use App\Http\Controllers\Admin\LandMasterController;
use App\Http\Controllers\SoilMoistureController;





//==================================================================================================================================
Route::get('/', [LoginController::class, 'showLoginForm'])->name('login.form');
Route::post('/ccbf-dashboard', [LoginController::class, 'loginDashboard'])->name('admins.dashboard');
Route::get('/site-summary', [LoginController::class, 'showSiteSummaryPage'])->name('site.summary');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');
 Route::get('/contact-farming/export-csv', [ContactFarmingController::class, 'exportContactFarming'])
    ->name('contact-farming.export.csv');
//===================================================================================================================================
// Admin Dashboard
//===================================================================================================================================
//Route::get('/crop-cycle', [CropCycleController::class, 'create'])->name('crop-cycle.create');
Route::middleware(['auth', 'role:1'])->group(function () {
    Route::get('/soil-moisture', [SoilMoistureController::class, 'index'])->name('soil-moisture.index');
    Route::get('/soil-moisture/debug', [SoilMoistureController::class, 'debugData'])->name('soil-moisture.debug');
    Route::post('/soil-moisture/command', [SoilMoistureController::class, 'sendCommand']);


    //crop cycle route
    Route::get('/crop-cycle', [CropCycleController::class, 'index'])->name('crop-cycle.index');
    Route::get('/crop-cycle/{id}/details', [CropCycleController::class, 'details'])->name('cropcycle.details');
    Route::post('/crop-cycle/{id}/close', [CropCycleController::class, 'close'])->name('cropcycle.close');
    Route::get('/seasons', [CropCycleController::class, 'season'])->name('seasons.index');
    Route::post('/seasons/close/{id}', [CropCycleController::class, 'closeSeason'])->name('seasons.close');

    Route::post('/rainfall/store', [DashboardController::class, 'storeRainfall'])->name('rainfall.store');
    //contact farming routes
    Route::get('/contact-farming', [ContactFarmingController::class, 'index'])->name('contact-farming.index');
    Route::get('/contact-farming/create', [ContactFarmingController::class, 'create'])->name('contact-farming.create');
    Route::post('/contact-farming', [ContactFarmingController::class, 'store'])->name('contact-farming.store');
    Route::get('/contact-farming/{id}', [ContactFarmingController::class, 'show'])->name('contact-farming.show');
    Route::get('/contact-farming/{id}/edit', [ContactFarmingController::class, 'edit'])->name('contact-farming.edit');
    Route::put('/contact-farming/{id}', [ContactFarmingController::class, 'update'])->name('contact-farming.update');
    Route::delete('/contact-farming/{id}', [ContactFarmingController::class, 'destroy'])->name('contact-farming.destroy');
   Route::post('contact-farming/sell', [ContactFarmingController::class, 'sellRemaining'])->name('contact-farming.sell');



    // File download route
    Route::get('/contact-farming/{id}/download/{type}', [ContactFarmingController::class, 'downloadFile'])->name('contact-farming.download');
    Route::post('/get-plots', [ContactFarmingController::class, 'getPlots'])->name('get-plots');
    Route::post('/get-seed-varieties', [ContactFarmingController::class, 'getSeedVarieties'])->name('get-seed-varieties');

    Route::get('/ccbf-admin', [DashboardControlller::class, 'ccbf_dashboard'])->name('dashboard');
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
   Route::post('/diesel/{id}/add-stock', [MasterController::class, 'addStockd'])->name('diesel.addStock');
   Route::post('/diesel/consumption/store', [MasterController::class, 'storeConsumption'])->name('diesel.consumption.store');



//==============================================================================================================================================

         // Master land routes
    // Master land routes
    Route::get('/land', [MasterController::class, 'indexland'])->name('land.index');
    Route::post('/land', [MasterController::class, 'storeland'])->name('land.store');

    Route::get('/land/{id}/edit', [MasterController::class, 'editland'])->name('land.edit');
    Route::put('/land/{id}', [MasterController::class, 'updateland'])->name('land.update');
    Route::delete('/land/{id}', [MasterController::class, 'destroyland'])->name('land.destroy');
    Route::get('/get-plots-by-block', [MasterController::class, 'getPlotsByBlockland'])->name('get.plots.by.block');
    Route::get('/get-plot-area', [MasterController::class, 'getPlotArealand'])->name('get.plot.area');

   // MASTER SEED ROUTE
     Route::get('/seeds', [MasterController::class, 'seedIndex'])->name('seeds.index');
    Route::post('/seeds', [MasterController::class, 'storeseed'])->name('seeds.store');
    Route::put('/seeds/{id}', [MasterController::class, 'updateseed'])->name('seeds.update');
    Route::delete('/seeds/{id}', [MasterController::class, 'destroyseed'])->name('seeds.destroy');
    Route::get('/seeds/search', [MasterController::class, 'searchseed'])->name('seeds.search');
    Route::post('/seeds/{id}/add-stock', [MasterController::class, 'addStock'])->name('seeds.addStock');
    // Route::get('/seeds/details/{id}', [MasterController::class, 'seedDetails'])->name('seeds.details');
    Route::get('/get-seed-history/{id}', [MasterController::class, 'getSeedHistory']);
//master seed
Route::get('/master-seed', [MasterStockController::class, 'masterseedindex'])->name('master-seed.index');
Route::post('/seed-store', [MasterStockController::class, 'store'])->name('master-seed.store');
Route::put('/seed-update/{id}', [MasterStockController::class, 'update'])->name('master-seed.update');
Route::delete('/seed-delete/{id}', [MasterStockController::class, 'destroy'])->name('master-seed.destroy');
Route::get('/seed-show/{id}', [MasterStockController::class, 'show'])->name('seed.show');

Route::get('/seed-veriety', [MasterStockController::class, 'seedveriety'])->name('seed-veriety');
Route::post('/seed-veriety-store', [MasterStockController::class, 'seedverietyStore'])->name('seed.veriety.store');
Route::get('/seed-veriety', [MasterStockController::class, 'seedveriety'])->name('seed-veriety');
// Routes (web.php mein add kariye)
Route::post('/seed-veriety-store-multiple', [MasterStockController::class, 'seedverietyStoreMultiple'])->name('seed.veriety.store.multiple');
Route::put('/seed-veriety-update/{id}', [MasterStockController::class, 'seedverietyUpdate'])->name('seed.veriety.update');
Route::delete('/seed-veriety-delete/{id}', [MasterStockController::class, 'seedverietyDestroy'])->name('seed.veriety.destroy');


//==============================================================================================================================================

  Route::get('/machine/edit/{id}', [MasterController::class, 'editMachine'])->name('machines.edit');

Route::get('/machines', [MasterController::class, 'machine'])->name('machines.index');
Route::post('/machines', [MasterController::class, 'store'])->name('machines.store');
Route::get('/machines/{id}/details', [MasterController::class, 'getDetails'])->name('machines.details');
Route::put('/machines/{id}', [MasterController::class, 'update'])->name('machines.update');
Route::delete('/machines/{id}', [MasterController::class, 'destroy_machine'])->name('machines.destroy');
//==============================================================================================================================================

           // Master fertilizer routes
           Route::get('/fertilizers', [MasterController::class, 'indexfertilizerS'])->name('fertilizer.index');
           Route::post('/fertilizers/store', [MasterController::class, 'storefertilizers'])->name('fertilizer.store');
           Route::get('/fertilizer/{id}/edit', [MasterController::class, 'editfertilizer'])->name('fertilizer.edit');
           Route::put('/fertilizer/update/{id}', [MasterController::class, 'updatefertilizers'])->name('fertilizer.update');

           Route::delete('fertilizers/delete/{id}', [MasterController::class, 'deletefertilizers'])->name('fertilizer.de');
     // Fertilizer stock history view
           Route::post('/fertilizers/{id}/add-stock', [MasterController::class, 'addFertilizerStock'])->name('fertilizers.addStock');
            Route::get('/fertilizers/history/{id}', [MasterController::class, 'showFertilizerHistory'])->name('fertilizers.history');

            Route::get('/api/fertilizers/{fertilizer}', [MasterController::class, 'getFertilizerDetails']);
            Route::get('/api/fertilizers/{fertilizer}/history', [MasterController::class, 'getFertilizerHistory']);

            Route::get('//master-fertilizer/index', [MasterStockController::class, 'indexfertilizers'])->name('master-fertilizer.index');
            Route::post('/master-fertilizer', [MasterStockController::class, 'fertilizerStore'])->name('master-fertilizer.store');
            Route::put('/fertilizer-master/update/{id}', [MasterStockController::class, 'updatefertilizers'])->name('fertilizer-master.update');
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
Route::get('/chemicals', [MasterController::class, 'indexchemicals'])->name('chemicals.index');
Route::post('/chemicals/store', [MasterController::class, 'storechemicals'])->name('chemicals.store');
Route::put('/chemicals/update/{id}', [MasterController::class, 'updatechemicals'])->name('chemicals.update');
Route::delete('/chemicals/delete/{id}', [MasterController::class, 'destroychemicals'])->name('chemicals.destroy');
Route::post('/chemicals/{id}/add-stock', [MasterController::class, 'addChemicalStock'])->name('chemicals.addStock');

Route::get('//master-chemical/index', [MasterStockController::class, 'indexchemicals'])->name('master-chemical.index');
Route::post('/master-chemical', [MasterStockController::class, 'chemicalStore'])->name('master-chemical.store');
Route::put('/chemical-master/update/{id}', [MasterStockController::class, 'updatechemicals'])->name('chemical-master.update');

// master area-leveling routes
Route::get('/area-leveling', [AreaLevelingController::class, 'index'])->name('area.leveling');
Route::get('/area-leveling/export', [AreaLevelingController::class, 'export'])->name('area-leveling.export');

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
Route::delete('/water_sources/destroy/{id}', [MasterController::class, 'deleteWaterSource'])->name('water_sources.destroy');

//

   // users management
  Route::get('/user', [UsersController::class, 'index'])->name('users.index');
  Route::post('/users', [UsersController::class, 'store'])->name('users.store');
  Route::get('/users/{id}/edit', [UsersController::class, 'edit'])->name('users.edit');
  Route::put('/users/{id}', [UsersController::class, 'update'])->name('users.update');
  Route::delete('/users/{id}', [UsersController::class, 'destroy'])->name('users.destroy');
  Route::get('/users/show/{id}', [UsersController::class, 'show'])->name('users.show');
  Route::post('/users/change-password', [UsersController::class, 'changePassword'])->name('users.change-password');


  Route::get('/get-plots/{block}', [UsersController::class, 'getPlots']);
  Route::get('/get-area', [UsersController::class, 'getArea']);
  Route::get('/land-preparation', [LandPreparationController::class, 'index'])->name('land-preparation.index');
  Route::get('/master-blocks', [LandMasterController::class, 'indexblock'])->name('master.blocks');


//=========================================================================
Route::get('/sowing-operations', [SowingOperationController::class, 'index'])->name('sowing_operations.index');
    Route::get('/sowing-operations/search', [SowingOperationController::class, 'search'])->name('sowing_operations.search');
    Route::get('/inter-culture', [InterCultureController::class, 'index'])->name('inter-culture.index');
Route::get('/inter-culture/search', [InterCultureController::class, 'search'])->name('inter-culture.search');
//===================================
Route::get('/crop-protection', [CropProtectionController::class, 'index'])->name('crop.protection.index');
//==========================================
Route::get('/activity-monitoring', [ActivityMonitoringController::class, 'index'])->name('activity.monitoring');
Route::get('admin/post-irrigation', [PostIrrigationController::class, 'index'])->name('post-irrigation.index');

Route::get('admin/fertilizers-app', [FertilizerRecordController::class, 'index'])->name('index');

//==========================================



Route::prefix('/notification')->group(function () {
    Route::get('/create', [NotificationController::class, 'create'])->name('notifications.create');
    Route::post('/store', [NotificationController::class, 'store'])->name('notifications.store');
    Route::get('/weekly', [NotificationController::class, 'weekly'])->name('notifications.weekly');
});
 //Fetch blocks for selected user
Route::get('/notifications/get-user-blocks/{userId}', [NotificationController::class, 'getUserBlocks'])
    ->name('notifications.getUserBlocks');

// Fetch plots for selected block (filtered by user_id in query string)
Route::get('/notifications/get-block-plots/{blockId}', [NotificationController::class, 'getBlockPlots'])
    ->name('notifications.getBlockPlots');

Route::get('/admin/harvest', [InterCultureController::class, 'harvestList'])->name('harvest.list');
//==========================================

Route::get('admin/hay-making', [HayMakingController::class, 'index'])
     ->name('hay-making.index');
//==========================================
///store managemant harvest hey slige
Route::get('/harvest-management', [HarvestStoreManageController ::class, 'indexhh'])->name('harvest.store.manage');
Route::post('/harvest/record-sale/{harvest_store_id}', [HarvestStoreManageController ::class, 'recordSale'])->name('harvest.store.record_sale');

// Sales History List
Route::get('/harvest/sales-history', [HarvestStoreManageController::class, 'salesHistory'])->name('harvest.sales.history');
// Sales Report Detail
// Route::get('/harvest/{id}/sales-report', [HarvestStoreManageController::class, 'salesReport'])->name('harvest.sales.report');
Route::get('/harvest/{seed_id}/{product_id}/sales-report', [HarvestStoreManageController::class, 'salesReport'])
    ->name('harvest.sales.report');
//new
//store managemant harvest hey slige
Route::get('/harvest-store-manage', [ActivityMonitoringController::class, 'indexhh'])->name('harvest.store.manage');
Route::post('/harvest-store-manage/{id}/update-sale-price', [ActivityMonitoringController::class, 'updateSalePrice'])->name('harvest.store.update.sale_price');

     Route::get('/admin/cost-analysis', [CostAnalysisController::class, 'index'])->name('admin.activity.dashboard');
     // Add this to your routes/web.php file
     Route::get('/admin/cost-analysis/export-csv', [App\Http\Controllers\Admin\CostAnalysisController::class, 'exportCsv'])->name('admin.cost_analysis.export_csv');
     Route::get('/get-plots/{blockId}', [App\Http\Controllers\Admin\CostAnalysisController::class, 'getPlots'])->name('get.plots');
     Route::get('admin/cost-analysis/plots-by-block', [App\Http\Controllers\Admin\CostAnalysisController::class, 'getPlotsByBlock'])->name('admin.cost-analysis.plots-by-block');
Route::get('admin/silage-making', [SilageMakingController::class, 'index'])
     ->name('silage-making.index');


});
 Route::get('/diesel-consumption-by-date', [MasterController::class, 'getDieselConsumptionByDate']);
 Route::get('/diesel-purchased-by-date', [MasterController::class, 'getDieselpurchasedByDate']);

///--------------Second role 2---------------------------//----
Route::middleware(['auth', 'role:2,3,4,5'])->group(function () {

Route::get('/master-seed', [MasterStockController::class, 'masterseedindex'])->name('master.seed.index');
Route::post('/seed-store', [MasterStockController::class, 'store'])->name('seed.store');
Route::put('/seed-update/{id}', [MasterStockController::class, 'update'])->name('seed.update');
Route::delete('/seed-delete/{id}', [MasterStockController::class, 'destroy'])->name('seed.destroy');
Route::get('/seed-show/{id}', [MasterStockController::class, 'show'])->name('seed.show');
Route::get('/get-varieties/{seedId}', [MasterController::class, 'getVarieties'])->name('get.varieties');


//seed veriety
Route::get('/seed-veriety', [MasterStockController::class, 'seedveriety'])->name('seed-veriety');
Route::post('/seed-veriety-store', [MasterStockController::class, 'seedverietyStore'])->name('seed.veriety.store');
Route::get('/seed-veriety', [MasterStockController::class, 'seedveriety'])->name('seed-veriety');
// Routes (web.php mein add kariye)
Route::post('/seed-veriety-store-multiple', [MasterStockController::class, 'seedverietyStoreMultiple'])->name('seed.veriety.store.multiple');
Route::put('/seed-veriety-update/{id}', [MasterStockController::class, 'seedverietyUpdate'])->name('seed.veriety.update');
Route::delete('/seed-veriety-delete/{id}', [MasterStockController::class, 'seedverietyDestroy'])->name('seed.veriety.destroy');


 Route::get('/crop-cycle', [CropCycleController::class, 'index'])->name('crop-cycle.index');
 Route::get('/crop-cycle/{id}/details', [CropCycleController::class, 'details'])->name('cropcycle.details');
 Route::post('/crop-cycle/{id}/close', [CropCycleController::class, 'close'])->name('cropcycle.close');
  Route::get('/seasons', [CropCycleController::class, 'season'])->name('seasons.index');
    Route::post('/seasons/close/{id}', [CropCycleController::class, 'closeSeason'])->name('seasons.close');

Route::post('/rainfall/store', [DashboardController::class, 'storeRainfall'])->name('rainfall.store');



//contact forming route
Route::get('/contact-farming', [ContactFarmingController::class, 'index'])->name('contact-farming.index');
 Route::get('/contact-farming/create', [ContactFarmingController::class, 'create'])->name('contact-farming.create');
    Route::post('/contact-farming', [ContactFarmingController::class, 'store'])->name('contact-farming.store');
    Route::get('/contact-farming/{id}', [ContactFarmingController::class, 'show'])->name('contact-farming.show');
    Route::get('/contact-farming/{id}/edit', [ContactFarmingController::class, 'edit'])->name('contact-farming.edit');
    Route::put('/contact-farming/{id}', [ContactFarmingController::class, 'update'])->name('contact-farming.update');
    Route::delete('/contact-farming/{id}', [ContactFarmingController::class, 'destroy'])->name('contact-farming.destroy');
   Route::post('contact-farming/sell', [ContactFarmingController::class, 'sellRemaining'])->name('contact-farming.sell');


    // File download route
    Route::get('/contact-farming/download', [ContactFarmingController::class, 'downloadFile'])->name('contact-farming.download');
    Route::post('/get-plots', [ContactFarmingController::class, 'getPlots'])->name('get-plots');
    Route::post('/get-seed-varieties', [ContactFarmingController::class, 'getSeedVarieties'])->name('get-seed-varieties');

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
Route::post('/diesel/{id}/add-stock', [MasterController::class, 'addStockd'])->name('diesel.addStock');
Route::post('/diesel/consumption/store', [MasterController::class, 'storeConsumption'])->name('diesel.consumption.store');

//==============================================================================================================================================

         // Master land routes
    // Master land routes
    Route::get('/land', [MasterController::class, 'indexland'])->name('land.index');
    Route::post('/land', [MasterController::class, 'storeland'])->name('land.store');

    Route::get('/land/{id}/edit', [MasterController::class, 'editland'])->name('land.edit');
    Route::put('/land/{id}', [MasterController::class, 'updateland'])->name('land.update');
    Route::delete('/land/{id}', [MasterController::class, 'destroyland'])->name('land.destroy');
    Route::get('/get-plots-by-block', [MasterController::class, 'getPlotsByBlockland'])->name('get.plots.by.block');
    Route::get('/get-plot-area', [MasterController::class, 'getPlotArealand'])->name('get.plot.area');
    Route::get('/master-blocks', [LandMasterController::class, 'indexblock'])->name('master.blocks');
    Route::post('/master-block/store', [LandMasterController::class, 'store'])->name('master-block.store');
    Route::get('/block-show/{id}', [LandMasterController::class, 'edit'])->name('master-block.edit');
    Route::post('/block-update/{id}', [LandMasterController::class, 'update'])->name('block-show.update');
    Route::delete('/master-block/{id}', [LandMasterController::class, 'destroy'])->name('master-blocks.destroy');

   // MASTER SEED ROUTE
   Route::get('/seeds', [MasterController::class, 'seedIndex'])->name('seeds.index');
Route::post('/seeds', [MasterController::class, 'storeseed'])->name('seeds.store');
Route::put('/seeds/{id}', [MasterController::class, 'updateseed'])->name('seeds.update');
Route::delete('/seeds/{id}', [MasterController::class, 'destroyseed'])->name('seeds.destroy');
Route::get('/seeds/search', [MasterController::class, 'searchseed'])->name('seeds.search');
Route::post('/seeds/{id}/add-stock', [MasterController::class, 'addStock'])->name('seeds.addStock');
Route::get('/get-seed-history/{id}', [MasterController::class, 'getSeedHistory']);


Route::get('/machines', [MasterController::class, 'machine'])->name('machines.index');
Route::post('/machines', [MasterController::class, 'store'])->name('machines.store');
Route::get('/machines/{id}/details', [MasterController::class, 'getDetails'])->name('machines.details');
Route::put('/machines/{id}', [MasterController::class, 'update'])->name('machines.update');
Route::delete('/machines/{id}', [MasterController::class, 'destroy_machine'])->name('machines.destroy');
//==============================================================================================================================================

           // Master fertilizer routes
           Route::get('/fertilizers', [MasterController::class, 'indexfertilizerS'])->name('fertilizer.index');
           Route::post('/fertilizers/store', [MasterController::class, 'storefertilizers'])->name('fertilizer.store');
           Route::get('/fertilizer/{id}/edit', [MasterController::class, 'editfertilizer'])->name('fertilizer.edit');
           Route::put('/fertilizer/update/{id}', [MasterController::class, 'updatefertilizers'])->name('fertilizer.update');

           Route::delete('fertilizers/delete/{id}', [MasterController::class, 'deletefertilizers'])->name('fertilizer.de');
    // Fertilizer stock history view
            Route::post('/fertilizers/{id}/add-stock', [MasterController::class, 'addFertilizerStock'])->name('fertilizers.addStock');
            Route::get('/fertilizers/history/{id}', [MasterController::class, 'showFertilizerHistory'])->name('fertilizers.history');
            Route::get('/api/fertilizers/{fertilizer}', [MasterController::class, 'getFertilizerDetails']);
            Route::get('/api/fertilizers/{fertilizer}/history', [MasterController::class, 'getFertilizerHistory']);

            Route::get('/master-fertilizer/index', [MasterStockController::class, 'indexfertilizers'])->name('master-fertilizer.index');
            Route::post('/master-fertilizer', [MasterStockController::class, 'fertilizerStore'])->name('master-fertilizer.store');
            Route::put('/fertilizer-master/update/{id}', [MasterStockController::class, 'updatefertilizers'])->name('fertilizer-master.update');
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
Route::get('/chemicals', [MasterController::class, 'indexchemicals'])->name('chemicals.index');
Route::post('/chemicals/store', [MasterController::class, 'storechemicals'])->name('chemicals.store');
Route::put('/chemicals/update/{id}', [MasterController::class, 'updatechemicals'])->name('chemicals.update');
Route::delete('/chemicals/delete/{id}', [MasterController::class, 'destroychemicals'])->name('chemicals.destroy');
Route::post('/chemicals/{id}/add-stock', [MasterController::class, 'addChemicalStock'])->name('chemicals.addStock');
Route::get('//master-chemical/index', [MasterStockController::class, 'indexchemicals'])->name('master-chemical.index');
Route::post('/master-chemical', [MasterStockController::class, 'chemicalStore'])->name('master-chemical.store');
Route::put('/chemical-master/update/{id}', [MasterStockController::class, 'updatechemicals'])->name('chemical-master.update');

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
Route::delete('/water_sources/destroy/{id}', [MasterController::class, 'deleteWaterSource'])->name('water_sources.destroy');

   Route::get('/user', [UsersController::class, 'index'])->name('users.index');
  Route::post('/users', [UsersController::class, 'store'])->name('users.store');
  Route::get('/users/{id}/edit', [UsersController::class, 'edit'])->name('users.edit');
  Route::put('/users/{id}', [UsersController::class, 'update'])->name('users.update');
  Route::delete('/users/{id}', [UsersController::class, 'destroy'])->name('users.destroy');
   Route::get('/users/show/{id}', [UsersController::class, 'show'])->name('users.show');
   Route::post('/users/change-password', [UsersController::class, 'changePassword'])->name('users.change-password');


  Route::get('/get-plots/{block}', [UsersController::class, 'getPlots']);
  Route::get('/get-area', [UsersController::class, 'getArea']);
// master area-leveling routes
Route::get('/area-leveling', [AreaLevelingController::class, 'index'])->name('area.leveling');
Route::get('/admin/area-leveling/export', [AreaLevelingController::class, 'exportCsv'])->name('area_leveling.export');

//==============================================================================================================================================

//Pre Land Preparation routes
Route::get('/land-preparation', [LandPreparationController::class, 'index'])->name('land-preparation.index');
Route::get('/pre-land-preparation', [PreLandPreparationController::class, 'index'])->name('preLand.index');
Route::get('/pre-land-preparation/search', [PreLandPreparationController::class, 'search'])->name('preLand.search');
//==============================================================================================================================================

//Pre Irrigation routes
Route::get('/pre-irrigation', [PreIrrigationController::class, 'index'])->name('pre_irrigation.index');
//=========================================================================
Route::get('/sowing-operations', [SowingOperationController::class, 'index'])->name('sowing_operations.index');
    Route::get('/sowing-operations/search', [SowingOperationController::class, 'search'])->name('sowing_operations.search');
    Route::get('/inter-culture', [InterCultureController::class, 'index'])->name('inter-culture.index');
Route::get('/inter-culture/search', [InterCultureController::class, 'search'])->name('inter-culture.search');
//===================================
Route::get('/crop-protection', [CropProtectionController::class, 'index'])->name('crop.protection.index');
//==========================================
Route::get('/activity-monitoring', [ActivityMonitoringController::class, 'index'])->name('activity.monitoring');
Route::get('admin/post-irrigation', [PostIrrigationController::class, 'index'])->name('post-irrigation.index');

Route::get('admin/fertilizers-app', [FertilizerRecordController::class, 'index'])->name('index');


Route::get('/admin/harvest', [InterCultureController::class, 'harvestList'])->name('harvest.list');

Route::get('admin/hay-making', [HayMakingController::class, 'index'])
     ->name('hay-making.index');

     Route::get('/admin/cost-analysis', [CostAnalysisController::class, 'index'])->name('admin.activity.dashboard');
     // Add this to your routes/web.php file
///store managemant harvest hey slige
Route::get('/harvest-management', [HarvestStoreManageController ::class, 'indexhh'])->name('harvest.store.manage');
Route::post('/harvest/record-sale/{harvest_store_id}', [HarvestStoreManageController ::class, 'recordSale'])->name('harvest.store.record_sale');

// Sales History List
Route::get('/harvest/sales-history', [HarvestStoreManageController::class, 'salesHistory'])->name('harvest.sales.history');
// Sales Report Detail
Route::get('/harvest/{seed_id}/{product_id}/sales-report', [HarvestStoreManageController::class, 'salesReport'])
    ->name('harvest.sales.report');
   //new
   //store managemant harvest hey slige
Route::get('/harvest-store-manage', [ActivityMonitoringController::class, 'indexhh'])->name('harvest.store.manage');
Route::post('/harvest-store-manage/{id}/update-sale-price', [ActivityMonitoringController::class, 'updateSalePrice'])->name('harvest.store.update.sale_price');
     Route::get('admin/cost-analysis/plots-by-block', [App\Http\Controllers\Admin\CostAnalysisController::class, 'getPlotsByBlock'])->name('admin.cost-analysis.plots-by-block');
     Route::get('/get-plots/{blockId}', [App\Http\Controllers\Admin\CostAnalysisController::class, 'getPlots'])->name('get.plots');
Route::get('admin/silage-making', [SilageMakingController::class, 'index'])
     ->name('silage-making.index');

//notification
   Route::prefix('/notification')->group(function () {
    Route::get('/create', [NotificationController::class, 'create'])->name('notifications.create');
    Route::post('/store', [NotificationController::class, 'store'])->name('notifications.store');
    Route::get('/weekly', [NotificationController::class, 'weekly'])->name('notifications.weekly');
    // AJAX routes
    Route::get('/get-user-details/{userId}', [NotificationController::class, 'getUserDetails']);
    Route::get('/get-plots/{block}', [NotificationController::class, 'getPlots']);
});
 //Fetch blocks for selected user
Route::get('/notifications/get-user-blocks/{userId}', [NotificationController::class, 'getUserBlocks'])
    ->name('notifications.getUserBlocks');

// Fetch plots for selected block (filtered by user_id in query string)
Route::get('/notifications/get-block-plots/{blockId}', [NotificationController::class, 'getBlockPlots'])
    ->name('notifications.getBlockPlots');


    Route::get('/soil-moisture', [SoilMoistureController::class, 'index'])->name('soil-moisture.index');
    Route::get('/soil-moisture/debug', [SoilMoistureController::class, 'debugData'])->name('soil-moisture.debug');
    Route::post('/soil-moisture/command', [SoilMoistureController::class, 'sendCommand']);
    });

// Public route to open voice query demo page (edit middleware as required)
Route::get('/voice-query', function () {
    return view('voice-query');
})->name('voice.query');

// Public demo page for voice sowing queries
Route::get('/voice-sowing', function () {
    return view('voice-sowing');
})->name('voice.sowing');
