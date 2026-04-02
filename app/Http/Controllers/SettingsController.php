<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use Illuminate\Http\Request;

/**
 * SettingsController — migrated from admin/ module (idstatus >= 3)
 */
class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $fid = session('fid', '');

        $data = Settings::init($fid);

        return view('settings.index', array_merge($data, compact('fid')));
    }

    public function show(Request $request)
    {
        $id = $request->input('id', '');
        $fid = session('fid', '');
        
        $setting = null;
        if ($id) {
            $setting = \Illuminate\Support\Facades\DB::table('conf')->where('id', $id)->where('firma', $fid)->first();
        } else {
            $setting = (object)[];
        }

        return view('settings.show', compact('setting', 'fid'));
    }

    public function save(Request $request)
    {
        $fid = session('fid', '');
        $run = $request->input('run', '');

        if ($run === 'save_conf') {
            $id = $request->input('id', '');
            $data = [
                'name' => $request->input('name', ''),
                'type' => $request->input('type', ''),
                'color' => $request->input('color', ''),
                'status' => $request->input('status', '1'),
                'vision' => $request->input('vision', '1'),
                'hide' => $request->input('hide', '0'),
                'constanta' => $request->input('constanta', '0'),
                'firma' => $fid,
            ];

            Settings::saveConf($id, $data);
        }

        if ($run === 'del_conf') {
            $id = $request->input('id', '');
            Settings::deleteConf($id, $fid);
        }

        return redirect()->route('settings.index')->with('success', 'Дані змінено!');
    }
}