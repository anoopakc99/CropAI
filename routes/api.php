<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Admin\MasterController;
use App\Http\Controllers\VoiceSowingController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
//web api call this fuction
  Route::get('/api/diesel/{id}', [MasterController::class, 'getDieselDetails'])->name('diesel.details');
    Route::get('/api/diesel/{id}/history', [MasterController::class, 'getDieselHistory'])->name('diesel.history');
Route::get('/chemicals/{id}', [MasterController::class, 'getChemicalDetails']);
    Route::get('/chemicals/{id}/history', [MasterController::class, 'getChemicalHistory']);
Route::get('/api/fertilizers/{fertilizer}', [MasterController::class, 'getFertilizerDetails']);
Route::get('/api/fertilizers/{fertilizer}/history', [MasterController::class, 'getFertilizerHistory']);

Route::get('/tractors/{id}', function ($id) {
    $tractor = DB::table('master_tractors')->where('id', $id)->first();
    return response()->json($tractor);
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [ApiController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [ApiController::class, 'logout']);

Route::middleware(['auth:sanctum'])->group(function () {
    
     Route::get('/filters', [ApiController::class, 'getFilters']);
   Route::post('/history', [ApiController::class, 'getHistory']);
  //wether
  Route::get('/weather', [ApiController::class, 'getForecast']);
 //----pre land prepration Routes----------------//

    //site-blocks-plots
    Route::post('/site-blocks-plots', [ApiController::class, 'getSiteBlocksPlots']);
     //getTractorNames
     Route::post('/tractors-names', [ApiController::class, 'getTractorNames']);

      //getMachineNames
    Route::post('/machine-names', [ApiController::class, 'getMachineNames']);

      //area-leveling-Store
      Route::post('/area-leveling/store', [ApiController::class, 'storeAreaLeveling']);

        //preLandPreparationInsert
    Route::post('/pre-land-preparation/store', [ApiController::class, 'storePreLandPreparation']);

   //getMasterManpowerCategories
   Route::post('/manpower/categories', [ApiController::class, 'getMasterManpowerCategories']);

     //master_irrigation_types
     Route::post('/irrigation-types', [ApiController::class, 'master_irrigation_types']);

       //master_water_source
    Route::post('/water-source', [ApiController::class, 'master_water_source']);

     //master_capacity
     Route::post('/capacity', [ApiController::class, 'master_capacity']);

    //storePreIrrigation
    Route::post('/pre-irrigation/store', [ApiController::class, 'storePreIrrigation']);

    //LandPreparation
    Route::post('/land-preparation-store', [ApiController::class, 'LandPreparation']);
    //soil-condiotion get
    Route::post('/soil-condion', [ApiController::class, 'getSoilCondition']);
     //seed-varieties
     Route::post('/seed-varieties', [ApiController::class, 'getSeedVarieties']);
     //sowing-methods
     Route::post('/sowing-methods', [ApiController::class, 'getSowingMethods']);
     //getperpoe
     Route::post('/get-purpose', [ApiController::class, 'getpurpose']);
     //getswoing_source
     Route::post('/get-sowing-source', [ApiController::class, 'getswoing_source']);
        //sowing-operations
    Route::post('/sowing-operations', [ApiController::class, 'storeSowingOperation']);

    //post irrigation
    Route::post('/post-irrigation', [ApiController::class, 'postIrrigation']);

    //master_fertilizer
    Route::post('/fertilizer-name', [ApiController::class, 'masetr_fatilizer']);
    //postFertilizer
    Route::post('/post-fertilizer', [ApiController::class, 'postFertilizer']);

    //inter-culture
  Route::post('/inter-culture', [ApiController::class, 'storeInterCulture']);
  //storeActivityMonitoring
Route::post('/activity-monitoring', [ApiController::class, 'storeActivityMonitoring']);
  //crop-protection
  Route::post('/crop-protection', [ApiController::class, 'storeCropProtection']);
  //chemical master data 
    Route::post('/crop-chemicals', [ApiController::class, 'chemicals']);


  //check-fodder-crop
  Route::post('/check-fodder-crop', [ApiController::class, 'checkFodderCrop']);
   //check-Hay-Making
   Route::post('/check-Hay-Making', [ApiController::class, 'checkHayMaking']);
   //checksilageMaking
   Route::post('/check-silage-Making', [ApiController::class, 'checksilageMaking']);
  //harvesting
  Route::post('/harvesting', [ApiController::class, 'storeHarvestUpdate']);
  //hay making
  Route::post('/store-hay-making', [ApiController::class, 'storeHayMaking']);
  //storesilageMaking
  Route::post('/store-silage-Making', [ApiController::class, 'storesilageMaking']);
  
  //notification
   Route::post('/notification', [ApiController::class, 'notification']);
    Route::post('/update-notification', [ApiController::class, 'updateNotification']);
   
    Route::post('/update-activity', [ApiController::class, 'updateActivity']); // General heartbeat
    Route::post('/update-location-movement', [ApiController::class, 'updateLocationAndMovement']); // GPS & Movement
    Route::post('/diesel-consumption', [ApiController::class, 'storeConsumption']);
});
// Voice Sowing API (public): handle sowing-specific voice queries
Route::post('/voice-sowing', [VoiceSowingController::class, 'voiceFetch']);