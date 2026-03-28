<?php

namespace App\Services\Analytics;

use App\Models\Expense;
use App\Models\Group;
use App\Models\User;
use App\Services\Analytics\DTO\AnalyticsFilterDTO;
use App\Services\Analytics\Interfaces\AnalyticsServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AnalyticsService implements AnalyticsServiceInterface
{
    private const CACHE_PREFIX = 'analytics_';
    private const CACHE_TTL = 3600; // 1 час

    public function getGroupAnalyticsDashboard(string $groupId, AnalyticsFilterDTO $dto): array
    {
        $cacheKey = self::CACHE_PREFIX . 'dashboard_' . $groupId . '_' . $dto->period;
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        $group = Group::findOrFail($groupId);
        
        $query = Expense::where('group_id', $groupId);
        
        if ($dto->period && $dto->period !== 'all') {
            $query->where('date', '>=', $this->getStartDate($dto->period));
        }
        
        $totalExpenses = (float) $query->sum('amount');
        $totalCount = $query->count();
        $avgExpense = $totalCount > 0 ? $totalExpenses / $totalCount : 0;
        $maxExpense = (float) $query->max('amount');
        
        $data = [
            'total_expenses' => $totalExpenses,
            'total_count' => $totalCount,
            'avg_expense' => $avgExpense,
            'max_expense' => $maxExpense,
        ];
        
        Cache::put($cacheKey, $data, self::CACHE_TTL);
        
        return $data;
    }

    public function getCategoryBreakdown(string $groupId, AnalyticsFilterDTO $dto): array
    {
        $cacheKey = self::CACHE_PREFIX . 'categories_' . $groupId . '_' . $dto->period;
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        $query = Expense::where('group_id', $groupId)
            ->whereNotNull('category_id')
            ->with('category');
        
        if ($dto->period && $dto->period !== 'all') {
            $query->where('date', '>=', $this->getStartDate($dto->period));
        }
        
        $categories = $query->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->orderBy('total', 'desc')
            ->get();
        
        $totalExpenses = $categories->sum('total');
        
        $data = $categories->map(function ($item) use ($totalExpenses) {
            $category = $item->category;
            return [
                'category_id' => $item->category_id,
                'category_name' => $category?->name ?? 'Без категории',
                'icon' => $category?->icon ?? '📦',
                'color' => $category?->color ?? '#6B7280',
                'total' => (float) $item->total,
                'percentage' => $totalExpenses > 0 ? round(($item->total / $totalExpenses) * 100, 1) : 0
            ];
        });
        
        Cache::put($cacheKey, $data, self::CACHE_TTL);
        
        return $data->toArray();
    }

    public function getUserSpendingComparison(string $groupId, AnalyticsFilterDTO $dto): array
    {
        $cacheKey = self::CACHE_PREFIX . 'users_' . $groupId . '_' . $dto->period;
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        $query = Expense::where('group_id', $groupId)
            ->with('payer');
        
        if ($dto->period && $dto->period !== 'all') {
            $query->where('date', '>=', $this->getStartDate($dto->period));
        }
        
        $users = $query->select('payer_id', DB::raw('SUM(amount) as total'))
            ->groupBy('payer_id')
            ->orderBy('total', 'desc')
            ->get();
        
        $totalExpenses = $users->sum('total');
        
        $data = $users->map(function ($item) use ($totalExpenses) {
            $user = $item->payer;
            return [
                'user_id' => $item->payer_id,
                'user_name' => $user?->full_name ?? $user?->username ?? 'Неизвестно',
                'first_name' => $user?->first_name,
                'last_name' => $user?->last_name,
                'username' => $user?->username,
                'email' => $user?->email,
                'total' => (float) $item->total,
                'percentage' => $totalExpenses > 0 ? round(($item->total / $totalExpenses) * 100, 1) : 0
            ];
        });
        
        Cache::put($cacheKey, $data, self::CACHE_TTL);
        
        return $data->toArray();
    }

    public function getPeriodComparison(string $groupId, AnalyticsFilterDTO $dto): array
    {
        $cacheKey = self::CACHE_PREFIX . 'comparison_' . $groupId . '_' . $dto->period;
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        $now = Carbon::now();
        
        switch ($dto->period) {
            case 'week':
                $currentStart = $now->copy()->startOfWeek();
                $previousStart = $now->copy()->subWeek()->startOfWeek();
                $currentEnd = $now->copy()->endOfWeek();
                $previousEnd = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'month':
                $currentStart = $now->copy()->startOfMonth();
                $previousStart = $now->copy()->subMonth()->startOfMonth();
                $currentEnd = $now->copy()->endOfMonth();
                $previousEnd = $now->copy()->subMonth()->endOfMonth();
                break;
            case 'year':
                $currentStart = $now->copy()->startOfYear();
                $previousStart = $now->copy()->subYear()->startOfYear();
                $currentEnd = $now->copy()->endOfYear();
                $previousEnd = $now->copy()->subYear()->endOfYear();
                break;
            default:
                $currentStart = $now->copy()->startOfMonth();
                $previousStart = $now->copy()->subMonth()->startOfMonth();
                $currentEnd = $now->copy()->endOfMonth();
                $previousEnd = $now->copy()->subMonth()->endOfMonth();
        }
        
        $currentExpenses = Expense::where('group_id', $groupId)
            ->whereBetween('date', [$currentStart, $currentEnd])
            ->get();
        
        $previousExpenses = Expense::where('group_id', $groupId)
            ->whereBetween('date', [$previousStart, $previousEnd])
            ->get();
        
        $currentTotal = (float) $currentExpenses->sum('amount');
        $previousTotal = (float) $previousExpenses->sum('amount');
        
        $change = $previousTotal > 0 
            ? round(($currentTotal - $previousTotal) / $previousTotal * 100, 1)
            : ($currentTotal > 0 ? 100 : 0);
        
        $data = [
            'current' => [
                'total' => $currentTotal,
                'count' => $currentExpenses->count(),
                'start_date' => $currentStart->toDateString(),
                'end_date' => $currentEnd->toDateString()
            ],
            'previous' => [
                'total' => $previousTotal,
                'count' => $previousExpenses->count(),
                'start_date' => $previousStart->toDateString(),
                'end_date' => $previousEnd->toDateString()
            ],
            'change' => $change
        ];
        
        Cache::put($cacheKey, $data, self::CACHE_TTL);
        
        return $data;
    }

    public function getGroupSpendingTrend(string $groupId, AnalyticsFilterDTO $dto): array
    {
        $cacheKey = self::CACHE_PREFIX . 'trend_' . $groupId . '_' . $dto->period;
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        $query = Expense::where('group_id', $groupId);
        
        if ($dto->period && $dto->period !== 'all') {
            $query->where('date', '>=', $this->getStartDate($dto->period));
        }
        
        $trend = $query->select(
                DB::raw("TO_CHAR(date, 'YYYY-MM-DD') as date"),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        
        $data = $trend->map(function ($item) {
            return [
                'date' => $item->date,
                'total' => (float) $item->total
            ];
        });
        
        Cache::put($cacheKey, $data, self::CACHE_TTL);
        
        return $data->toArray();
    }

    public function getExpenseDistribution(string $groupId, AnalyticsFilterDTO $dto): array
    {
        // Аналогично с кешированием
        $cacheKey = self::CACHE_PREFIX . 'distribution_' . $groupId . '_' . $dto->period;
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        $query = Expense::where('group_id', $groupId);
        
        if ($dto->period && $dto->period !== 'all') {
            $query->where('date', '>=', $this->getStartDate($dto->period));
        }
        
        $data = [
            'by_amount' => [
                'under_1000' => $query->clone()->where('amount', '<', 1000)->count(),
                '1000_5000' => $query->clone()->whereBetween('amount', [1000, 5000])->count(),
                '5000_10000' => $query->clone()->whereBetween('amount', [5000, 10000])->count(),
                'over_10000' => $query->clone()->where('amount', '>', 10000)->count(),
            ]
        ];
        
        Cache::put($cacheKey, $data, self::CACHE_TTL);
        
        return $data;
    }

    public function getTopSpendingCategories(string $groupId, AnalyticsFilterDTO $dto): array
    {
        $cacheKey = self::CACHE_PREFIX . 'top_categories_' . $groupId . '_' . $dto->period;
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        $query = Expense::where('group_id', $groupId)
            ->whereNotNull('category_id')
            ->with('category');
        
        if ($dto->period && $dto->period !== 'all') {
            $query->where('date', '>=', $this->getStartDate($dto->period));
        }
        
        $categories = $query->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();
        
        $data = $categories->map(function ($item) {
            $category = $item->category;
            return [
                'category_id' => $item->category_id,
                'name' => $category?->name ?? 'Без категории',
                'icon' => $category?->icon ?? '📦',
                'total' => (float) $item->total
            ];
        });
        
        Cache::put($cacheKey, $data, self::CACHE_TTL);
        
        return $data->toArray();
    }

    public function getUserSpendingStats(string $groupId, AnalyticsFilterDTO $dto): array
    {
        return [];
    }

    public function getSavingsOpportunities(string $groupId, AnalyticsFilterDTO $dto): array
    {
        return [];
    }

    public function getSpendingPredictions(string $groupId, AnalyticsFilterDTO $dto): array
    {
        return [];
    }

    public function getUserAnalyticsDashboard(User $user, AnalyticsFilterDTO $dto): array
    {
        return [];
    }

    public function generateAnalyticsReport(string $groupId, AnalyticsFilterDTO $dto): array
    {
        return [];
    }

    private function getStartDate(string $period): Carbon
    {
        return match ($period) {
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            'year' => Carbon::now()->subYear(),
            default => Carbon::now()->subMonth(),
        };
    }
}