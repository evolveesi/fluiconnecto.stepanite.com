<?php

namespace Tests\Feature;

use App\Http\Controllers\Users\RoleController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ListingOrderTest extends TestCase
{
    public function test_role_listing_shows_newest_records_first_with_stable_ties(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        try {
            DB::table('roles')->insert([
                ['id' => 1, 'name' => 'Old', 'created_at' => '2024-01-01 00:00:00', 'updated_at' => '2024-01-01 00:00:00'],
                ['id' => 2, 'name' => 'New A', 'created_at' => '2025-01-01 00:00:00', 'updated_at' => '2025-01-01 00:00:00'],
                ['id' => 3, 'name' => 'New B', 'created_at' => '2025-01-01 00:00:00', 'updated_at' => '2025-01-01 00:00:00'],
            ]);

            $view = (new RoleController())->index();

            $this->assertSame([3, 2, 1], $view->getData()['roles']->pluck('id')->all());
        } finally {
            Schema::dropIfExists('roles');
        }
    }
}
