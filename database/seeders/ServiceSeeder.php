<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Seed the example services shown throughout the UI preview.
     */
    public function run(): void
    {
        $createdBy = User::query()->where('role', UserRole::SuperAdmin)->value('id');

        $services = [
            ['name' => 'GST Return Filing', 'description' => 'Monthly/quarterly GST return filing', 'default_price' => 2500, 'gst_percent' => 18, 'is_active' => true, 'created_by' => $createdBy],
            ['name' => 'Income Tax Return Filing', 'description' => 'Individual & business ITR filing', 'default_price' => 3000, 'gst_percent' => 18, 'is_active' => true, 'created_by' => $createdBy],
            ['name' => 'GST Registration', 'description' => 'New GSTIN registration', 'default_price' => 4000, 'gst_percent' => 18, 'price_includes_gst' => true, 'is_active' => true, 'created_by' => $createdBy],
            ['name' => 'TDS Return Filing', 'description' => 'Quarterly TDS return filing', 'default_price' => 2000, 'gst_percent' => 18, 'is_active' => true, 'created_by' => $createdBy],
            ['name' => 'ROC Annual Filing', 'description' => 'Annual ROC compliance filing', 'default_price' => 6500, 'gst_percent' => 18, 'is_active' => true, 'created_by' => $createdBy],
            ['name' => 'PAN Card Creation', 'description' => 'New PAN card application', 'default_price' => 500, 'gst_percent' => 18, 'is_active' => false, 'created_by' => $createdBy],
        ];

        foreach ($services as $data) {
            $service = Service::query()->updateOrCreate(['name' => $data['name']], $data);

            if ($service->wasRecentlyCreated && $service->name === 'GST Return Filing') {
                $service->documents()->createMany([
                    ['name' => 'Sales Register', 'instructions' => 'Monthly sales register export', 'allowed_formats' => 'PDF', 'max_file_size_kb' => 5120, 'is_mandatory' => true],
                    ['name' => 'Bank Statement', 'instructions' => 'Last 6 months statement', 'allowed_formats' => 'PDF', 'max_file_size_kb' => 5120, 'is_mandatory' => true],
                ]);
            }

            if ($service->wasRecentlyCreated && $service->name === 'GST Registration') {
                $service->documents()->createMany([
                    ['name' => 'PAN Card', 'instructions' => 'Clear scan of both sides', 'allowed_formats' => 'PDF, JPG, PNG', 'max_file_size_kb' => 5120, 'is_mandatory' => true],
                    ['name' => 'Aadhaar Card', 'instructions' => 'Front and back, clearly readable', 'allowed_formats' => 'PDF, JPG, PNG', 'max_file_size_kb' => 5120, 'is_mandatory' => true],
                    ['name' => 'Passport Size Photo', 'instructions' => 'Recent colour photo, white background', 'allowed_formats' => 'JPG, PNG', 'max_file_size_kb' => 2048, 'is_mandatory' => false],
                ]);
            }
        }
    }
}
