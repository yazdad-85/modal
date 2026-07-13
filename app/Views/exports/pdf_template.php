<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan — <?= esc($project['nama_proyek']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #212529;
            line-height: 1.4;
            margin: 0;
            padding: 24px;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 4px;
        }
        h2 {
            font-size: 13px;
            margin: 20px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #dee2e6;
        }
        .meta {
            color: #6c757d;
            margin-bottom: 16px;
        }
        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .summary td {
            padding: 6px 8px;
            border: 1px solid #dee2e6;
        }
        .summary td:first-child {
            width: 40%;
            background: #f8f9fa;
            font-weight: bold;
        }
        .summary td:last-child {
            text-align: right;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        table.data th,
        table.data td {
            border: 1px solid #dee2e6;
            padding: 6px 8px;
        }
        table.data th {
            background: #e9ecef;
            text-align: left;
        }
        table.data td.number {
            text-align: right;
        }
        .profit { color: #198754; }
        .loss { color: #dc3545; }
        .modal-color { color: #0d6efd; }
        .note {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 8px 10px;
            margin-top: 8px;
        }
        .footer {
            margin-top: 24px;
            font-size: 9px;
            color: #6c757d;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1><?= esc($project['nama_proyek']) ?></h1>
    <div class="meta">
        Operator: <?= esc($project['nama_operator']) ?>
        &nbsp;|&nbsp;
        Mode: <?= $project['mode_input'] === 'unit' ? 'Per Unit' : 'Langsung' ?>
        &nbsp;|&nbsp;
        Tanggal: <?= esc(date('d/m/Y')) ?>
    </div>

    <h2>Ringkasan</h2>
    <table class="summary">
        <tr>
            <td>Total Modal</td>
            <td class="modal-color"><?= esc(format_rupiah((int) $result['total_modal'])) ?></td>
        </tr>
        <tr>
            <td>Total Hasil Jual</td>
            <td><?= esc(format_rupiah((int) $result['total_hasil_jual'])) ?></td>
        </tr>
        <tr>
            <td><?= $result['rugi'] ? 'Rugi' : 'Profit Kotor' ?></td>
            <td class="<?= $result['rugi'] ? 'loss' : 'profit' ?>">
                <?= esc(format_rupiah(abs((int) $result['keuntungan_kotor']))) ?>
            </td>
        </tr>
        <tr>
            <td>Total Biaya Operasional</td>
            <td><?= esc(format_rupiah((int) $result['total_biaya_operasional'])) ?></td>
        </tr>
        <tr>
            <td>Profit Bersih</td>
            <td class="<?= (int) $result['keuntungan_bersih'] >= 0 ? 'profit' : 'loss' ?>">
                <?= esc(format_rupiah(abs((int) $result['keuntungan_bersih']))) ?>
            </td>
        </tr>
        <tr>
            <td>Bagi Hasil</td>
            <td><?= esc($project['persen_pemodal']) ?>% Pemodal / <?= esc($project['persen_operator']) ?>% Operator</td>
        </tr>
        <?php if ($project['mode_input'] === 'unit'): ?>
            <tr>
                <td>Jumlah Unit</td>
                <td><?= esc($project['jumlah_unit']) ?> pcs</td>
            </tr>
            <tr>
                <td>Harga Beli / pcs</td>
                <td><?= esc(format_rupiah((int) $project['harga_beli'])) ?></td>
            </tr>
            <tr>
                <td>Harga Jual / pcs</td>
                <td><?= esc(format_rupiah((int) $project['harga_jual'])) ?></td>
            </tr>
        <?php endif; ?>
    </table>

    <?php if (! empty($project['catatan'])): ?>
        <div class="note">
            <strong>Catatan:</strong><br>
            <?= esc($project['catatan']) ?>
        </div>
    <?php endif; ?>

    <?php if ((int) $result['total_biaya_operasional'] > 0): ?>
        <h2>Biaya Operasional</h2>
        <table class="data">
            <thead>
                <tr>
                    <th>Keterangan</th>
                    <th class="number">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result['biaya_operasional'] as $cost): ?>
                    <tr>
                        <td><?= esc($cost['keterangan']) ?></td>
                        <td class="number"><?= esc(format_rupiah((int) $cost['jumlah'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>Pengembalian Modal</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Pemodal</th>
                <th class="number">Modal</th>
                <th class="number">Pengembalian</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($result['investors'] as $investor): ?>
                <tr>
                    <td><?= esc($investor['nama']) ?></td>
                    <td class="number"><?= esc(format_rupiah((int) $investor['modal'])) ?></td>
                    <td class="number modal-color"><?= esc(format_rupiah((int) $investor['pengembalian_modal'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Bagi Hasil Profit</h2>
    <?php if ($result['rugi']): ?>
        <p>Proyek mengalami rugi. Tidak ada profit yang dibagikan.</p>
    <?php elseif (! $result['profit_dapat_dibagikan']): ?>
        <p>Biaya operasional melebihi profit kotor. Tidak ada profit yang dibagikan.</p>
    <?php else: ?>
        <table class="summary">
            <tr>
                <td>Pool Pemodal (<?= esc($project['persen_pemodal']) ?>%)</td>
                <td class="profit"><?= esc(format_rupiah((int) $result['pool_pemodal'])) ?></td>
            </tr>
            <tr>
                <td>Pool Operator (<?= esc($project['persen_operator']) ?>%) — <?= esc($project['nama_operator']) ?></td>
                <td class="modal-color"><?= esc(format_rupiah((int) $result['pool_operator'])) ?></td>
            </tr>
        </table>

        <table class="data">
            <thead>
                <tr>
                    <th>Pemodal</th>
                    <th class="number">Profit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result['investors'] as $investor): ?>
                    <tr>
                        <td><?= esc($investor['nama']) ?></td>
                        <td class="number profit"><?= esc(format_rupiah((int) $investor['profit'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>Total per Pemodal</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Pemodal</th>
                <th class="number">Pengembalian</th>
                <th class="number">Profit</th>
                <th class="number">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($result['investors'] as $investor): ?>
                <tr>
                    <td><?= esc($investor['nama']) ?></td>
                    <td class="number"><?= esc(format_rupiah((int) $investor['pengembalian_modal'])) ?></td>
                    <td class="number profit"><?= esc(format_rupiah((int) $investor['profit'])) ?></td>
                    <td class="number"><strong><?= esc(format_rupiah((int) $investor['total'])) ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        ModalCalc — Laporan Bagi Hasil Investasi — Dicetak <?= esc(date('d/m/Y H:i')) ?>
    </div>
</body>
</html>
