<?php

namespace App\Services\Analytics\Interfaces;

use App\Models\User;
use App\Services\Analytics\DTO\AnalyticsFilterDTO;

interface AnalyticsServiceInterface
{
    public function getGroupAnalyticsDashboard(string $groupId, AnalyticsFilterDTO $dto): array;
    public function getCategoryBreakdown(string $groupId, AnalyticsFilterDTO $dto): array;
    public function getUserSpendingComparison(string $groupId, AnalyticsFilterDTO $dto): array;
    public function getPeriodComparison(string $groupId, AnalyticsFilterDTO $dto): array;
    public function getGroupSpendingTrend(string $groupId, AnalyticsFilterDTO $dto): array;
    public function getExpenseDistribution(string $groupId, AnalyticsFilterDTO $dto): array;
    public function getTopSpendingCategories(string $groupId, AnalyticsFilterDTO $dto): array;
    public function getUserSpendingStats(string $groupId, AnalyticsFilterDTO $dto): array;
    public function getSavingsOpportunities(string $groupId, AnalyticsFilterDTO $dto): array;
    public function getSpendingPredictions(string $groupId, AnalyticsFilterDTO $dto): array;
    public function getUserAnalyticsDashboard(User $user, AnalyticsFilterDTO $dto): array;
    public function generateAnalyticsReport(string $groupId, AnalyticsFilterDTO $dto): array;
}