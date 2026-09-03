<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller; use App\Services\NavigationService; use Illuminate\Http\Request;
class NavigationController extends Controller { public function __invoke(Request $request, NavigationService $navigation){return response()->json($navigation->for($request->user()));} }