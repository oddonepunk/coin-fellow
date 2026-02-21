<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('group_roles')->insert([
            [
                'name' => 'owner',
                'display_name' => 'Владелец',
                'description' => 'Полный доступ к группе',
                'level' => 100,
                'permissions' => json_encode(['*']),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'admin',
                'display_name' => 'Администратор',
                'description' => 'Управление участниками и расходами',
                'level' => 50,
                'permissions' => json_encode([
                    'invite_users',
                    'remove_users',
                    'manage_expenses',
                    'edit_group_info'
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
             [
                'name' => 'member',
                'display_name' => 'Участник',
                'description' => 'Добавление расходов',
                'level' => 10,
                'permissions' => json_encode([
                    'add_expenses',
                    'view_expenses'
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        Schema::table('group_user', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('user_id');
            $table->foreign('role_id')
                ->references('id')
                ->on('group_roles')
                ->onDelete('set null');
        });

        //миграция существующих ролей выше
        DB::statement("
            UPDATE group_user 
            SET role_id = (SELECT id FROM group_roles WHERE name = group_user.role)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_user', function(Blueprint $table){
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
