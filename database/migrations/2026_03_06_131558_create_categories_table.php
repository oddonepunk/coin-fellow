<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
   public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'icon')) {
                $table->string('icon')->nullable()->after('name');
            }
            if (!Schema::hasColumn('categories', 'color')) {
                $table->string('color')->nullable()->after('icon');
            }
            if (!Schema::hasColumn('categories', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('color');
            }
            if (!Schema::hasColumn('categories', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->after('is_default');
            }
        });
        $defaultCategories = [
            ['name' => 'Продукты', 'icon' => '🛒', 'color' => '#3B82F6', 'is_default' => true],
            ['name' => 'Транспорт', 'icon' => '🚗', 'color' => '#10B981', 'is_default' => true],
            ['name' => 'Кафе', 'icon' => '☕', 'color' => '#F59E0B', 'is_default' => true],
            ['name' => 'Ресторан', 'icon' => '🍽️', 'color' => '#EF4444', 'is_default' => true],
            ['name' => 'Развлечения', 'icon' => '🎬', 'color' => '#8B5CF6', 'is_default' => true],
            ['name' => 'Здоровье', 'icon' => '🏥', 'color' => '#EC4899', 'is_default' => true],
            ['name' => 'Образование', 'icon' => '📚', 'color' => '#6366F1', 'is_default' => true],
            ['name' => 'Одежда', 'icon' => '👕', 'color' => '#F97316', 'is_default' => true],
            ['name' => 'Красота', 'icon' => '💄', 'color' => '#D946EF', 'is_default' => true],
            ['name' => 'Подарки', 'icon' => '🎁', 'color' => '#F43F5E', 'is_default' => true],
            ['name' => 'Жилье', 'icon' => '🏠', 'color' => '#0EA5E9', 'is_default' => true],
            ['name' => 'Связь', 'icon' => '📱', 'color' => '#64748B', 'is_default' => true],
            ['name' => 'Спорт', 'icon' => '⚽', 'color' => '#22C55E', 'is_default' => true],
            ['name' => 'Путешествия', 'icon' => '✈️', 'color' => '#06B6D4', 'is_default' => true],
            ['name' => 'Другое', 'icon' => '📦', 'color' => '#6B7280', 'is_default' => true],
        ];

        foreach ($defaultCategories as $category) {
            DB::table('categories')->updateOrInsert(
                ['name' => $category['name']],
                $category
            );
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['icon', 'color', 'is_default', 'user_id']);
        });
    }
};