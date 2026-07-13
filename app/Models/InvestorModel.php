<?php

namespace App\Models;

use CodeIgniter\Model;

class InvestorModel extends Model
{
    protected $table            = 'investors';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['project_id', 'nama', 'modal', 'urutan'];
    protected $useTimestamps    = false;

    public function getByProject(int $projectId): array
    {
        return $this->where('project_id', $projectId)
            ->orderBy('urutan', 'ASC')
            ->findAll();
    }

    public function syncForProject(int $projectId, array $investors): void
    {
        $this->where('project_id', $projectId)->delete();

        foreach ($investors as $i => $investor) {
            $this->insert([
                'project_id' => $projectId,
                'nama'       => $investor['nama'],
                'modal'      => $investor['modal'],
                'urutan'     => $i,
            ]);
        }
    }
}
