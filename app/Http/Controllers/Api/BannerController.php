<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index(Request $request)
    {
        $banners = Banner::active()
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $banners->map(fn (Banner $banner) => $this->formatBanner($banner))->values(),
        ]);
    }

    protected function formatBanner(Banner $banner): array
    {
        return [
            'id' => $banner->id,
            'title' => $banner->title,
            'subtitle' => $banner->subtitle,
            'badge' => $banner->badge,
            'image' => $banner->image,
            'icon' => $banner->icon,
            'link' => $banner->link,
            'cta_label' => $banner->cta_label,
            'position' => $banner->position,
            'is_active' => $banner->is_active,
            'created_at' => optional($banner->created_at)?->toIso8601String(),
            'updated_at' => optional($banner->updated_at)?->toIso8601String(),
        ];
    }
}

