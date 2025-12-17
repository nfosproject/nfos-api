<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{

    public function index(Request $request)
    {
        $banners = Banner::ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $banners->map(fn (Banner $banner) => $this->formatBanner($banner))->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $banner = Banner::create($data);

        return response()->json([
            'success' => true,
            'data' => $this->formatBanner($banner),
        ], 201);
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $this->validatedData($request, $banner);

        $banner->update($data);

        return response()->json([
            'success' => true,
            'data' => $this->formatBanner($banner->fresh()),
        ]);
    }

    public function destroy(Banner $banner)
    {
        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Banner deleted successfully.',
        ]);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'banners' => 'required|array',
            'banners.*.id' => 'required|string|exists:banners,id',
            'banners.*.position' => 'required|integer|min:0',
        ]);

        foreach ($request->banners as $item) {
            Banner::where('id', $item['id'])->update(['position' => $item['position']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Banners reordered successfully.',
        ]);
    }

    protected function validatedData(Request $request, ?Banner $banner = null): array
    {
        $validated = $request->validate([
            'title' => [$banner ? 'sometimes' : 'required', 'string', 'max:255'],
            'subtitle' => ['sometimes', 'nullable', 'string', 'max:500'],
            'badge' => ['sometimes', 'nullable', 'string', 'max:100'],
            'image' => [$banner ? 'sometimes' : 'required', 'string', 'url', 'max:1000'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:50'],
            'link' => ['sometimes', 'string', 'max:255'],
            'cta_label' => ['sometimes', 'string', 'max:100'],
            'position' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data = array_merge($banner?->toArray() ?? [], $validated);

        $data['link'] = $data['link'] ?? '/store';
        $data['cta_label'] = $data['cta_label'] ?? 'Shop now';
        $data['position'] = $data['position'] ?? 0;
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
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

