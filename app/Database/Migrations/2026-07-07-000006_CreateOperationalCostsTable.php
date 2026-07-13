<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOperationalCostsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'project_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'keterangan' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'jumlah' => [
                'type' => 'BIGINT',
            ],
            'urutan' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'default'    => 0,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('operational_costs');
    }

    public function down(): void
    {
        $this->forge->dropTable('operational_costs');
    }
}
