<?php

namespace App\Libraries;

class ProfitCalculator
{
    public function computeUnitTotals(int $jumlahUnit, int $hargaBeli, int $hargaJual): array
    {
        return [
            'total_modal'      => $jumlahUnit * $hargaBeli,
            'total_hasil_jual' => $jumlahUnit * $hargaJual,
        ];
    }

    /**
     * @param list<array{nama: string, modal: int}> $investors
     * @param list<array{keterangan: string, jumlah: int}> $operationalCosts
     */
    public function calculate(
        int $totalHasilJual,
        int $totalModal,
        float $persenPemodal,
        float $persenOperator,
        array $investors,
        array $operationalCosts = []
    ): array {
        if (round($persenPemodal + $persenOperator, 2) !== 100.0) {
            throw new \InvalidArgumentException('Persentase pemodal dan operator harus berjumlah 100%');
        }

        if ($totalModal <= 0) {
            throw new \InvalidArgumentException('Total modal proyek harus lebih dari 0');
        }

        if ($investors === []) {
            throw new \InvalidArgumentException('Minimal satu pemodal diperlukan');
        }

        $investorTotal = array_sum(array_column($investors, 'modal'));
        if ($investorTotal !== $totalModal) {
            throw new \InvalidArgumentException('Total modal pemodal tidak sama dengan total modal proyek');
        }

        $normalizedCosts = $this->normalizeOperationalCosts($operationalCosts);
        $totalBiayaOperasional = array_sum(array_column($normalizedCosts, 'jumlah'));

        $keuntunganKotor = $totalHasilJual - $totalModal;
        $rugi = $keuntunganKotor < 0;
        $keuntunganBersih = $keuntunganKotor - $totalBiayaOperasional;
        $canSplitProfit = ! $rugi && $keuntunganBersih > 0;

        $poolPemodal  = $canSplitProfit ? (int) round($keuntunganBersih * ($persenPemodal / 100)) : 0;
        $poolOperator = $canSplitProfit ? (int) round($keuntunganBersih * ($persenOperator / 100)) : 0;

        $results = [];
        foreach ($investors as $investor) {
            $share = $investor['modal'] / $totalModal;
            $pengembalian = (int) round($totalModal * $share);
            $profit = $canSplitProfit ? (int) round($poolPemodal * $share) : 0;

            $results[] = [
                'nama'               => $investor['nama'],
                'modal'              => $investor['modal'],
                'pengembalian_modal' => $pengembalian,
                'profit'             => $profit,
                'total'              => $pengembalian + $profit,
            ];
        }

        return [
            'total_hasil_jual'        => $totalHasilJual,
            'total_modal'             => $totalModal,
            'keuntungan_kotor'        => $keuntunganKotor,
            'total_biaya_operasional' => $totalBiayaOperasional,
            'biaya_operasional'       => $normalizedCosts,
            'keuntungan_bersih'       => $keuntunganBersih,
            'rugi'                    => $rugi,
            'profit_dapat_dibagikan'  => $canSplitProfit,
            'pool_pemodal'            => $poolPemodal,
            'pool_operator'           => $poolOperator,
            'investors'               => $results,
        ];
    }

    /**
     * @param list<array{keterangan?: string, jumlah?: int}> $operationalCosts
     *
     * @return list<array{keterangan: string, jumlah: int}>
     */
    private function normalizeOperationalCosts(array $operationalCosts): array
    {
        $normalized = [];

        foreach ($operationalCosts as $cost) {
            $keterangan = trim((string) ($cost['keterangan'] ?? ''));
            $jumlah = (int) ($cost['jumlah'] ?? 0);

            if ($keterangan === '' && $jumlah === 0) {
                continue;
            }

            if ($keterangan === '' || $jumlah <= 0) {
                throw new \InvalidArgumentException('Setiap biaya operasional wajib memiliki keterangan dan jumlah lebih dari 0.');
            }

            $normalized[] = [
                'keterangan' => $keterangan,
                'jumlah'     => $jumlah,
            ];
        }

        return $normalized;
    }
}
