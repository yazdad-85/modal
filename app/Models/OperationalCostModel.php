<?php

namespace App\Models;

use CodeIgniter\Model;

class OperationalCostModel extends Model
{
    protected $table            = 'operational_costs';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['project_id', 'keterangan', 'jumlah', 'urutan'];
    protected $useTimestamps    = false;

    public function getByProject(int $projectId): array
    {
        return $this->where('project_id', $projectId)
            ->orderBy('urutan', 'ASC')
            ->findAll();
    }

    /**
     * @param list<array{keterangan: string, jumlah: int}> $costs
     */
    public function syncForProject(int $projectId, array $costs): void
    {
        $this->where('project_id', $projectId)->delete();

        foreach ($costs as $i => $cost) {
            $this->insert([
                'project_id' => $projectId,
                'keterangan' => $cost['keterangan'],
                'jumlah'     => $cost['jumlah'],
                'urutan'     => $i,
            ]);
        }
    }
}
