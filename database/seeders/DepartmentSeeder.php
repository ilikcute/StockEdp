<?php

namespace Database\Seeders;

use App\Features\Department\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Seed master departments into database.
     */
    public function run(): void
    {
        $departments = [
            [
                'code' => 'IT',
                'name' => 'EDP & IT',
                'description' => 'Electronic Data Processing & Information Technology',
            ],
            [
                'code' => 'EDP REG',
                'name' => 'EDP Regional',
                'description' => 'EDP Regional Kantor Cabang / Wilayah',
            ],
            [
                'code' => 'FAD',
                'name' => 'Finance, Accounting, Tax',
                'description' => 'Finance, Accounting & Taxation',
            ],
            [
                'code' => 'GA',
                'name' => 'General Affairs',
                'description' => 'General Affairs & Sarana Kantor',
            ],
            [
                'code' => 'HRD',
                'name' => 'HRD & Personalia',
                'description' => 'Human Resources Department & Personalia',
            ],
            [
                'code' => 'OPR',
                'name' => 'Operasional',
                'description' => 'Departemen Operasional Toko / Lapangan',
            ],
            [
                'code' => 'DC',
                'name' => 'Distribution Center (Logistik & Gudang)',
                'description' => 'Distribution Center, Logistik & Pergudangan',
            ],
            [
                'code' => 'MKT',
                'name' => 'Marketing & Promosi',
                'description' => 'Marketing, Promosi & Merchandising',
            ],
            [
                'code' => 'DEV',
                'name' => 'Departement Development',
                'description' => 'Department Development & Research',
            ],
            [
                'code' => 'PRJ',
                'name' => 'Project',
                'description' => 'Departemen Project & Eksekusi Lapangan',
            ],
            [
                'code' => 'ACL',
                'name' => 'Maintenance',
                'description' => 'Departemen Maintenance & Pemeliharaan Sarana',
            ],
            [
                'code' => 'LIC',
                'name' => 'Dept License',
                'description' => 'Departemen Perizinan & Legalitas',
            ],
            [
                'code' => 'LOC',
                'name' => 'Dept Location',
                'description' => 'Departemen Survey Lokasi & Pengembangan Area',
            ],
            [
                'code' => 'BIC',
                'name' => 'Team Audit Internal',
                'description' => 'Badan Pemeriksa Internal / Internal Audit',
            ],
            [
                'code' => 'BMT',
                'name' => 'Building Maintenance',
                'description' => 'Building Maintenance & Pemeliharaan Fisik Gedung',
            ],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['code' => $dept['code']],
                [
                    'name' => $dept['name'],
                    'description' => $dept['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
