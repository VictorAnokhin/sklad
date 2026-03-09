<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * AdminController — migrated from admin/ module (idstatus >= 3)
 */
class AdminController extends Controller
{
    public function index(Request $request)
    {
        $fid = session('fid', '');

        $users    = DB::table('users')->where('firma', $fid)->orderBy('secondname')->get();
        $conf     = DB::table('conf')->where('firma', $fid)->orderBy('type')->orderBy('name')->get();
        $settings = DB::table('settings')->where('firma', $fid)->first();

        return view('admin.index', compact('users', 'conf', 'settings', 'fid'));
    }

    public function save(Request $request)
    {
        $fid = session('fid', '');
        $run = $request->input('run', '');

        if ($run === 'save_conf') {
            $id   = $request->input('id', '');
            $data = [
                'name'      => convert_to_base($request->input('name', '')),
                'type'      => $request->input('type', ''),
                'color'     => $request->input('color', ''),
                'status'    => $request->input('status', '1'),
                'vision'    => $request->input('vision', '1'),
                'hide'      => $request->input('hide', '0'),
                'constanta' => $request->input('constanta', '0'),
                'firma'     => $fid,
            ];
            if ($id === '') {
                DB::table('conf')->insert($data);
            } else {
                DB::table('conf')->where('id', $id)->update($data);
            }
        }

        if ($run === 'del_conf') {
            $id = $request->input('id', '');
            DB::table('conf')->where('id', $id)->where('firma', $fid)->delete();
        }

        return redirect()->back()->with('success', 'Збережено');
    }
}
