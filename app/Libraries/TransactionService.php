<?php

namespace App\Libraries;

use App\Models\TransactionModel;
use InvalidArgumentException;

class TransactionService
{
    public function remaining(int $target, int $sudah): int
    {
        return max(0, $target - $sudah);
    }

    public function percent(int $target, int $sudah): int
    {
        if ($target <= 0) {
            return 100;
        }

        return (int) round(($sudah / $target) * 100);
    }

    public function canRecord(int $target, int $sudah, int $jumlah): bool
    {
        return $jumlah > 0 && $jumlah <= $this->remaining($target, $sudah);
    }

    /**
     * @param list<array{id:int|string,nama:string,modal:int|string}> $investors
     * @param array<string,mixed> $calcResult
     * @param array<int, array<string,int>> $sumsByInvestor
     * @return array{project: array<string,array<string,int>>, investors: list<array<string,mixed>>, is_fully_settled: bool}
     */
    public function buildProgress(array $investors, array $calcResult, array $sumsByInvestor): array
    {
        $calcInvestors = $calcResult['investors'] ?? [];
        $rows = [];
        $allSettled = $investors !== [];

        $projSetor = ['target' => 0, 'sudah' => 0];
        $projModal = ['target' => 0, 'sudah' => 0];
        $projProfit = ['target' => 0, 'sudah' => 0];

        foreach ($investors as $i => $inv) {
            $id = (int) $inv['id'];
            $calcRow = $calcInvestors[$i] ?? null;
            if ($calcRow === null) {
                throw new InvalidArgumentException('Hasil kalkulasi tidak selaras dengan daftar pemodal.');
            }

            $sums = $sumsByInvestor[$id] ?? [];
            $setorSudah  = (int) ($sums[TransactionModel::JENIS_SETOR] ?? 0);
            $modalSudah  = (int) ($sums[TransactionModel::JENIS_PENGEMBALIAN_MODAL] ?? 0);
            $profitSudah = (int) ($sums[TransactionModel::JENIS_PENGEMBALIAN_PROFIT] ?? 0);

            $setorTarget  = (int) $inv['modal'];
            $modalTarget  = (int) $calcRow['pengembalian_modal'];
            $profitTarget = (int) $calcRow['profit'];

            $setor  = $this->metric($setorTarget, $setorSudah);
            $modal  = $this->metric($modalTarget, $modalSudah);
            $profit = $this->metric($profitTarget, $profitSudah);

            $settled = $setor['sisa'] === 0 && $modal['sisa'] === 0 && $profit['sisa'] === 0;
            if (! $settled) {
                $allSettled = false;
            }

            $rows[] = [
                'investor_id' => $id,
                'nama'        => $inv['nama'],
                'setor'       => $setor,
                'modal'       => $modal,
                'profit'      => $profit,
                'settled'     => $settled,
            ];

            $projSetor['target'] += $setorTarget;
            $projSetor['sudah']  += $setorSudah;
            $projModal['target'] += $modalTarget;
            $projModal['sudah']  += $modalSudah;
            $projProfit['target'] += $profitTarget;
            $projProfit['sudah']  += $profitSudah;
        }

        return [
            'project' => [
                'setor'  => $this->metric($projSetor['target'], $projSetor['sudah']),
                'modal'  => $this->metric($projModal['target'], $projModal['sudah']),
                'profit' => $this->metric($projProfit['target'], $projProfit['sudah']),
            ],
            'investors'        => $rows,
            'is_fully_settled' => $allSettled,
        ];
    }

    /** @return array{target:int,sudah:int,sisa:int,persen:int} */
    private function metric(int $target, int $sudah): array
    {
        return [
            'target' => $target,
            'sudah'  => $sudah,
            'sisa'   => $this->remaining($target, $sudah),
            'persen' => $this->percent($target, $sudah),
        ];
    }

    /** @param array<string,mixed> $progressInvestorRow */
    public function targetForJenis(array $progressInvestorRow, string $jenis): int
    {
        return match ($jenis) {
            TransactionModel::JENIS_SETOR => (int) $progressInvestorRow['setor']['target'],
            TransactionModel::JENIS_PENGEMBALIAN_MODAL => (int) $progressInvestorRow['modal']['target'],
            TransactionModel::JENIS_PENGEMBALIAN_PROFIT => (int) $progressInvestorRow['profit']['target'],
            default => throw new InvalidArgumentException('Jenis transaksi tidak valid.'),
        };
    }

    /** @param array<string,mixed> $progressInvestorRow */
    public function sudahForJenis(array $progressInvestorRow, string $jenis): int
    {
        return match ($jenis) {
            TransactionModel::JENIS_SETOR => (int) $progressInvestorRow['setor']['sudah'],
            TransactionModel::JENIS_PENGEMBALIAN_MODAL => (int) $progressInvestorRow['modal']['sudah'],
            TransactionModel::JENIS_PENGEMBALIAN_PROFIT => (int) $progressInvestorRow['profit']['sudah'],
            default => throw new InvalidArgumentException('Jenis transaksi tidak valid.'),
        };
    }

    /** @return array{status: string, completed_at: string|null} */
    public function resolveStatusPayload(bool $fullySettled, string $now): array
    {
        if ($fullySettled) {
            return ['status' => 'completed', 'completed_at' => $now];
        }

        return ['status' => 'active', 'completed_at' => null];
    }
}
