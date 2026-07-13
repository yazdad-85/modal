<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionModel extends Model
{
    public const JENIS_SETOR               = 'setor_modal';
    public const JENIS_PENGEMBALIAN_MODAL  = 'pengembalian_modal';
    public const JENIS_PENGEMBALIAN_PROFIT = 'pengembalian_profit';

    public const JENIS_LIST = [
        self::JENIS_SETOR,
        self::JENIS_PENGEMBALIAN_MODAL,
        self::JENIS_PENGEMBALIAN_PROFIT,
    ];

    protected $table         = 'transactions';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'project_id', 'investor_id', 'jenis', 'jumlah',
        'tanggal', 'catatan', 'created_by',
    ];
    protected $useTimestamps = true;

    public function getByProject(int $projectId): array
    {
        return $this->where('project_id', $projectId)
            ->orderBy('tanggal', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    public function countByProject(int $projectId): int
    {
        return $this->where('project_id', $projectId)->countAllResults();
    }

    /**
     * @return array<int, array<string, int>> investor_id => [jenis => sum]
     */
    public function sumsGroupedByInvestor(int $projectId): array
    {
        $rows = $this->select('investor_id, jenis, SUM(jumlah) AS total')
            ->where('project_id', $projectId)
            ->groupBy('investor_id, jenis')
            ->findAll();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['investor_id']][$row['jenis']] = (int) $row['total'];
        }

        return $out;
    }
}
