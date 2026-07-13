<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProjectsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT', 'unsigned' => true],
            'nama_proyek'      => ['type' => 'VARCHAR', 'constraint' => 200],
            'mode_input'       => ['type' => 'ENUM', 'constraint' => ['unit', 'direct'], 'default' => 'direct'],
            'jumlah_unit'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'harga_beli'       => ['type' => 'BIGINT', 'null' => true],
            'harga_jual'       => ['type' => 'BIGINT', 'null' => true],
            'total_modal'      => ['type' => 'BIGINT'],
            'total_hasil_jual' => ['type' => 'BIGINT'],
            'persen_pemodal'   => ['type' => 'DECIMAL', 'constraint' => '5,2'],
            'persen_operator'  => ['type' => 'DECIMAL', 'constraint' => '5,2'],
            'nama_operator'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'catatan'          => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('projects');
    }

    public function down(): void
    {
        $this->forge->dropTable('projects');
    }
}
