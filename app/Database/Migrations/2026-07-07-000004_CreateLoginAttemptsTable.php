<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLoginAttemptsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'ip_address'   => ['type' => 'VARCHAR', 'constraint' => 45],
            'email'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'attempted_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['ip_address', 'email', 'attempted_at']);
        $this->forge->createTable('login_attempts');
    }

    public function down(): void
    {
        $this->forge->dropTable('login_attempts');
    }
}
