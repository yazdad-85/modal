<?php

namespace App\Models;

use CodeIgniter\Model;

class ProjectModel extends Model
{
    protected $table            = 'projects';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'user_id', 'nama_proyek', 'mode_input', 'jumlah_unit',
        'harga_beli', 'harga_jual', 'total_modal', 'total_hasil_jual',
        'persen_pemodal', 'persen_operator', 'nama_operator', 'catatan',
        'status', 'completed_at',
    ];
    protected $useTimestamps = true;

    public function findForUser(int $id, int $userId): ?array
    {
        return $this->where('id', $id)->where('user_id', $userId)->first();
    }

    public function getByUser(int $userId): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('updated_at', 'DESC')
            ->findAll();
    }

    public function getByUserAndStatus(int $userId, string $status): array
    {
        $order = $status === 'completed' ? 'completed_at' : 'updated_at';

        return $this->where('user_id', $userId)
            ->where('status', $status)
            ->orderBy($order, 'DESC')
            ->findAll();
    }

    public function countByUserAndStatus(int $userId, string $status): int
    {
        return $this->where('user_id', $userId)
            ->where('status', $status)
            ->countAllResults();
    }

    public function isCompleted(array $project): bool
    {
        return ($project['status'] ?? 'active') === 'completed';
    }
}
