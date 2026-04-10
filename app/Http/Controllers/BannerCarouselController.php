<?php

namespace App\Http\Controllers;

use App\Models\BannerCarousel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BannerCarouselController extends Controller
{
    public function apiIndex(Request $request)
    {
        $fid = (string) $request->input('fid', session('fid', '2'));

        $items = DB::table('banner_carousels')
            ->where('firma', $fid)
            ->where('vision', 1)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(static fn ($item) => BannerCarousel::decorate($item));

        return response()->json([
            'items' => $items,
        ]);
    }
}
