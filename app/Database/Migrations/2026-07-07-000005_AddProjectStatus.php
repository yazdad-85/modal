<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProjectStatus extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('projects', [
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'completed'],
                'default'    => 'active',
                'after'      => 'catatan',
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'status',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('projects', ['status', 'completed_at']);
    }
}
