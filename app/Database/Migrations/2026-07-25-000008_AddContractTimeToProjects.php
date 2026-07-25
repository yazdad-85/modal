<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContractTimeToProjects extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('projects', [
            'waktu_kontrak' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'nama_operator',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('projects', 'waktu_kontrak');
    }
}
