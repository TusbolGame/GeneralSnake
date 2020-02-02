<?php

namespace App\Http\Controllers\Api;

use App\Entities\Setting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SettingsController extends Controller
{
    //
    public function all() {
        $settings = Setting::all()->pluck('value', 'name');

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    public function getSetttings(Request $request) {
        $settings = Setting::query();

        $input = $request->input();

        if (isset($input['name'])) {
            $settings->where('name', $input['name']);
        }

        $settings = $settings->get();

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }


    public function addSetting(Request $request) {
        $input = $request->input();

        $setting = new Setting();
        $setting->name = $input['name'];
        $setting->value = $input['value'];
        $setting->save();

        return response()->json([
            'success' => true
        ]);
    }

    public function deleteSetting(Request $request) {
        $input = $request->input();

        $setting = Setting::find($input['id']);

        if (!$setting) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $setting->delete();

        return response()->json([
            'success' => true
        ]);
    }

    public function saveSetting(Request $request)
    {
        $input = $request->input();

        foreach ($input as $key => $value) {
            if ($key == 'locale') continue;
            $setting = Setting::where('name', $key)->first();

            if (!$setting) {
                $setting = new Setting();
                $setting->name = $key;
            }

            if ($setting) {
                $setting->value = $value;
                $setting->save();
            }
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
