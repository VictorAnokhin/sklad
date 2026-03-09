<?php

namespace App\Http\Controllers;

use App\Services\FilterService;
use Illuminate\Http\Request;

class FilterController extends Controller
{
    public function __construct(private FilterService $filter) {}

    public function apply(Request $request)
    {
        $doc = $request->input('doc', session('doc', ''));
        $fid = session('fid', '');
        $this->filter->save($request, $doc, $fid);
        return redirect()->back();
    }

    public function clear(Request $request)
    {
        $doc = $request->input('doc', session('doc', ''));
        $this->filter->clear($doc);
        return redirect()->back();
    }
}
