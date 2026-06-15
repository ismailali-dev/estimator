<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends VoyagerBaseController
{
    public function index(Request $request)
    {
        // 👉 get default Voyager data
        $view = parent::index($request);

        // 👉 get original data
        $data = $view->getData();

        $collection = $data['dataTypeContent'];

        // ✅ APPLY YOUR LOGIC HERE
        $grouped = $collection->groupBy('user_id');

        $filtered = $grouped->map(function ($subs) {

            $active = $subs->first(function ($item) {
                return strtolower($item->status) === 'active';
            });

            return $active ? $active : $subs->first();
        })->values();

        // ❗ IMPORTANT: replace data
        $data['dataTypeContent'] = $filtered;

        // ❗ return SAME view with modified data
        return view($view->getName(), $data);
    }
}