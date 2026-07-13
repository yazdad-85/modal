<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTransactionsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'project_id'  => ['type' => 'INT', 'unsigned' => true],
            'investor_id' => ['type' => 'INT', 'unsigned' => true],
            'jenis'       => ['type' => 'VARCHAR', 'constraint' => 32],
            'jumlah'      => ['type' => 'BIGINT'],
            'tanggal'     => ['type' => 'DATE'],
            'catatan'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'  => ['type' => 'INT', 'unsigned' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('project_id');
        $this->forge->addKey('investor_id');
        $this->forge->addKey(['project_id', 'jenis']);
        $this->forge->addKey('tanggal');
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('investor_id', 'investors', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('transactions');
    }

    public function down(): void
    {
        $this->forge->dropTable('transactions');
    }
}
