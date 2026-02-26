<?php

namespace Database\Seeders;

use App\Models\FacebookPage;
use Illuminate\Database\Seeder;

class FacebookPageSeeder extends Seeder
{
    public function run(): void
    {
        FacebookPage::insert([
            [
                'page_id'      => '123456789',
                'page_name'    => 'Bracongo CI',
                'access_token' => 'mock_token_bracongo',
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'page_id'      => '987654321',
                'page_name'    => 'Orange CI',
                'access_token' => 'mock_token_orange',
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'page_id'      => '111222333',
                'page_name'    => 'Mercedes-Benz CI',
                'access_token' => 'mock_token_mercedes',
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);
    }
}