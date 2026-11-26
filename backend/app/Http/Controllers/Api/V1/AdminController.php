<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Modules\Users\Services\UserService;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function __construct(protected UserService $userService) {}

    public function dashboard(): JsonResponse
    {
        return response()->json($this->userService->dashboardStats());
    }

    public function reports(): JsonResponse
    {
        $since = now()->subDays(30);

        $daily = fn ($rows) => $rows
            ->groupBy(fn ($row) => now()->parse($row->created_at)->toDateString())
            ->map(fn ($group, $date) => ['date' => $date, 'count' => $group->count()])
            ->sortBy('date')
            ->values();

        $published = Product::where('status', ProductStatus::Published->value)
            ->get(['category_id', 'seller_id']);

        $perCategory = $published->groupBy(fn ($p) => (string) $p->category_id)->map->count();
        $perSeller = $published->groupBy(fn ($p) => (string) $p->seller_id)->map->count();

        return response()->json([
            'daily_registrations' => $daily(User::where('created_at', '>=', $since)->get(['created_at'])),
            'daily_auctions' => $daily(Auction::where('created_at', '>=', $since)->get(['created_at'])),
            'top_categories' => Category::all()
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'products_count' => (int) ($perCategory[(string) $c->id] ?? 0)])
                ->sortByDesc('products_count')
                ->take(10)
                ->values(),
            'top_sellers' => User::all()
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'products_count' => (int) ($perSeller[(string) $u->id] ?? 0)])
                ->sortByDesc('products_count')
                ->take(10)
                ->values(),
        ]);
    }
}
