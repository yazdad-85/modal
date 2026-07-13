<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInvestorsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'project_id' => ['type' => 'INT', 'unsigned' => true],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'modal'      => ['type' => 'BIGINT'],
            'urutan'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('project_id');
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('investors');
    }

    public function down(): void
    {
        $this->forge->dropTable('investors');
    }
}
