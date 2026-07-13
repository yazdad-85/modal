(function () {
    'use strict';

    function parseRupiah(value) {
        if (typeof value === 'number') {
            return value;
        }
        const cleaned = String(value || '').replace(/[^\d]/g, '');
        return cleaned === '' ? 0 : parseInt(cleaned, 10);
    }

    function formatDigits(amount) {
        const num = parseRupiah(amount);
        if (num <= 0) {
            return '';
        }
        return num.toLocaleString('id-ID');
    }

    function formatRupiah(amount) {
        const num = parseRupiah(amount);
        return 'Rp ' + num.toLocaleString('id-ID');
    }

    function formatMoneyField(input) {
        const digitsOnly = String(input.value).replace(/[^\d]/g, '');
        const cursorPos = input.selectionStart ?? digitsOnly.length;
        const digitsBeforeCursor = String(input.value)
            .slice(0, cursorPos)
            .replace(/[^\d]/g, '').length;

        if (digitsOnly === '') {
            input.value = '';
            input.dataset.rawValue = '0';
            return;
        }

        const raw = parseInt(digitsOnly, 10);
        const formatted = raw.toLocaleString('id-ID');
        input.value = formatted;
        input.dataset.rawValue = String(raw);

        let newPos = formatted.length;
        if (digitsBeforeCursor > 0) {
            let seen = 0;
            for (let i = 0; i < formatted.length; i++) {
                if (/\d/.test(formatted[i])) {
                    seen++;
                    if (seen === digitsBeforeCursor) {
                        newPos = i + 1;
                        break;
                    }
                }
            }
        } else {
            newPos = 0;
        }

        input.setSelectionRange(newPos, newPos);
    }

    function parseDecimalId(value) {
        if (typeof value === 'number') {
            return value;
        }

        const s = String(value || '').trim();
        if (s === '') {
            return 0;
        }

        // Indonesian: 1.234,56
        if (s.includes(',')) {
            const normalized = s.replace(/\./g, '').replace(',', '.');
            const n = parseFloat(normalized);
            return Number.isNaN(n) ? 0 : n;
        }

        // Dot as decimal (from PHP/DB): 55.00 — not thousands
        if (s.includes('.')) {
            const parts = s.split('.');
            if (parts.length === 2 && parts[1].length > 0 && parts[1].length <= 2) {
                const n = parseFloat(s);
                return Number.isNaN(n) ? 0 : n;
            }
            const n = parseInt(s.replace(/\./g, ''), 10);
            return Number.isNaN(n) ? 0 : n;
        }

        const n = parseFloat(s);
        return Number.isNaN(n) ? 0 : n;
    }

    function formatPercentDisplay(value) {
        const raw = parseDecimalId(value);
        if (raw <= 0) {
            return '';
        }
        const rounded = Math.round(raw * 100) / 100;
        const parts = String(rounded).split('.');
        const intFormatted = parseInt(parts[0], 10).toLocaleString('id-ID');
        return parts[1] ? intFormatted + ',' + parts[1] : intFormatted;
    }

    function formatDecimalField(input) {
        let v = String(input.value).replace(/[^\d,]/g, '');
        const cursorPos = input.selectionStart ?? v.length;
        const digitsBeforeCursor = String(input.value)
            .slice(0, cursorPos)
            .replace(/[^\d]/g, '').length;

        const commaIdx = v.indexOf(',');
        let intPart = commaIdx >= 0 ? v.slice(0, commaIdx) : v;
        let decPart = commaIdx >= 0 ? v.slice(commaIdx + 1).replace(/,/g, '').slice(0, 2) : '';
        const trailingComma = v.endsWith(',');

        intPart = intPart.replace(/\D/g, '');

        if (intPart === '' && decPart === '' && !trailingComma) {
            input.value = '';
            input.dataset.rawValue = '';
            return;
        }

        const intNum = intPart === '' ? 0 : parseInt(intPart, 10);
        const formattedInt = intPart === '' ? '' : intNum.toLocaleString('id-ID');
        const formatted = trailingComma || decPart !== ''
            ? formattedInt + ',' + decPart
            : formattedInt;

        input.value = formatted;
        input.dataset.rawValue = String(parseDecimalId(formatted));

        let newPos = formatted.length;
        if (digitsBeforeCursor > 0) {
            let seen = 0;
            for (let i = 0; i < formatted.length; i++) {
                if (/\d/.test(formatted[i])) {
                    seen++;
                    if (seen === digitsBeforeCursor) {
                        newPos = i + 1;
                        break;
                    }
                }
            }
        } else {
            newPos = 0;
        }
        input.setSelectionRange(newPos, newPos);
    }

    function initMoneyInputDisplay() {
        document.querySelectorAll('.number-input').forEach(function (input) {
            const raw = parseRupiah(input.dataset.rawValue || input.value);
            input.dataset.rawValue = String(raw);
            if (raw > 0) {
                input.value = raw.toLocaleString('id-ID');
            }
        });

        document.querySelectorAll('.decimal-input').forEach(function (input) {
            const raw = parseDecimalId(input.dataset.rawValue || input.value);
            input.dataset.rawValue = String(raw);
            if (raw > 0) {
                input.value = formatPercentDisplay(raw);
            }
        });
    }

    function getActiveMode() {
        const checked = document.querySelector('input[name="mode_input"]:checked');
        return checked ? checked.value : 'direct';
    }

    function toggleModeFields() {
        const mode = getActiveMode();
        document.querySelectorAll('.mode-fields').forEach(function (el) {
            el.classList.toggle('active', el.dataset.mode === mode);
        });
    }

    function getTotalModalTarget() {
        const mode = getActiveMode();
        if (mode === 'unit') {
            const units = parseRupiah(document.getElementById('jumlah_unit')?.value);
            const buy = parseRupiah(document.getElementById('harga_beli')?.value);
            return units * buy;
        }
        return parseRupiah(document.getElementById('total_modal')?.value);
    }

    function getTotalHasilJual() {
        const mode = getActiveMode();
        if (mode === 'unit') {
            const units = parseRupiah(document.getElementById('jumlah_unit')?.value);
            const sell = parseRupiah(document.getElementById('harga_jual')?.value);
            return units * sell;
        }
        return parseRupiah(document.getElementById('total_hasil_jual')?.value);
    }

    function updateUnitTotals() {
        const mode = getActiveMode();
        if (mode !== 'unit') {
            return;
        }

        const units = parseRupiah(document.getElementById('jumlah_unit')?.value);
        const buy = parseRupiah(document.getElementById('harga_beli')?.value);
        const sell = parseRupiah(document.getElementById('harga_jual')?.value);

        const totalModal = units * buy;
        const totalJual = units * sell;

        const modalDisplay = document.getElementById('unitTotalModal');
        const jualDisplay = document.getElementById('unitTotalJual');

        if (modalDisplay) {
            modalDisplay.textContent = formatRupiah(totalModal);
        }
        if (jualDisplay) {
            jualDisplay.textContent = formatRupiah(totalJual);
        }

        updateModalProgress();
        updateReviewSummary();
    }

    function getInvestorRows() {
        return Array.from(document.querySelectorAll('#investorRows .investor-row'));
    }

    function getInvestorTotal() {
        let total = 0;
        getInvestorRows().forEach(function (row) {
            const input = row.querySelector('.investor-modal-input');
            if (input) {
                total += parseRupiah(input.value);
            }
        });
        return total;
    }

    function updateModalProgress() {
        const target = getTotalModalTarget();
        const collected = getInvestorTotal();
        const progressBar = document.getElementById('modalProgressBar');
        const progressText = document.getElementById('modalProgressText');
        const progressPercent = document.getElementById('modalProgressPercent');

        if (!progressBar || !progressText) {
            return;
        }

        let percent = 0;
        if (target > 0) {
            percent = Math.min(100, Math.round((collected / target) * 100));
        }

        progressBar.style.width = percent + '%';
        progressBar.setAttribute('aria-valuenow', String(percent));
        progressBar.classList.remove('bg-success', 'bg-warning', 'bg-danger');

        if (target > 0 && collected === target) {
            progressBar.classList.add('bg-success');
        } else if (collected > target && target > 0) {
            progressBar.classList.add('bg-danger');
        } else {
            progressBar.classList.add('bg-warning');
        }

        progressText.textContent = formatRupiah(collected) + ' / ' + formatRupiah(target);
        if (progressPercent) {
            progressPercent.textContent = percent + '%';
        }
    }

    function createInvestorRow(name, modal) {
        const row = document.createElement('div');
        row.className = 'investor-row row g-2 align-items-end mb-3';
        row.innerHTML =
            '<div class="col-12 col-sm-5">' +
                '<label class="form-label">Nama Pemodal</label>' +
                '<input type="text" class="form-control investor-name-input" name="investor_nama[]" ' +
                    'value="' + (name || '').replace(/"/g, '&quot;') + '" maxlength="100" required>' +
            '</div>' +
            '<div class="col-10 col-sm-6">' +
                '<label class="form-label">Modal</label>' +
                '<input type="text" class="form-control investor-modal-input number-input" name="investor_modal[]" ' +
                    'value="' + (modal ? formatDigits(modal) : '') + '" ' +
                    'inputmode="decimal" data-raw-value="' + (modal || 0) + '" required>' +
            '</div>' +
            '<div class="col-2 col-sm-1">' +
                '<button type="button" class="btn btn-outline-danger btn-remove-investor w-100" ' +
                    'aria-label="Hapus pemodal">&times;</button>' +
            '</div>';

        const modalInput = row.querySelector('.investor-modal-input');
        bindNumberInput(modalInput);
        row.querySelector('.btn-remove-investor').addEventListener('click', function () {
            removeInvestorRow(row);
        });

        return row;
    }

    function addInvestorRow(name, modal) {
        const container = document.getElementById('investorRows');
        if (!container) {
            return;
        }
        container.appendChild(createInvestorRow(name, modal));
        updateModalProgress();
        updateReviewSummary();
    }

    function removeInvestorRow(row) {
        const rows = getInvestorRows();
        if (rows.length <= 1) {
            return;
        }
        row.remove();
        updateModalProgress();
        updateReviewSummary();
    }

    function getOpsRows() {
        return Array.from(document.querySelectorAll('#opsRows .ops-row'));
    }

    function getOperationalCostsFromForm() {
        const costs = [];
        getOpsRows().forEach(function (row) {
            const keterangan = row.querySelector('.ops-label-input')?.value.trim() || '';
            const jumlah = parseRupiah(row.querySelector('.ops-amount-input')?.value);
            if (keterangan || jumlah > 0) {
                costs.push({ keterangan: keterangan || '-', jumlah: jumlah });
            }
        });
        return costs;
    }

    function getOperationalTotal() {
        return getOperationalCostsFromForm().reduce(function (sum, cost) {
            return sum + cost.jumlah;
        }, 0);
    }

    function updateOpsTotal() {
        const badge = document.getElementById('opsTotalBadge');
        if (!badge) {
            return;
        }
        badge.textContent = formatRupiah(getOperationalTotal());
    }

    function createOpsRow(keterangan, jumlah) {
        const row = document.createElement('div');
        row.className = 'ops-row row g-2 align-items-end mb-3';
        row.innerHTML =
            '<div class="col-12 col-sm-6">' +
                '<label class="form-label">Keterangan</label>' +
                '<input type="text" class="form-control ops-label-input" name="ops_keterangan[]" ' +
                    'value="' + (keterangan || '').replace(/"/g, '&quot;') + '" maxlength="200" ' +
                    'placeholder="Mis. Transport, Gaji karyawan">' +
            '</div>' +
            '<div class="col-10 col-sm-5">' +
                '<label class="form-label">Jumlah</label>' +
                '<input type="text" class="form-control ops-amount-input number-input" name="ops_jumlah[]" ' +
                    'value="' + (jumlah ? formatDigits(jumlah) : '') + '" ' +
                    'inputmode="decimal" data-raw-value="' + (jumlah || 0) + '" placeholder="0">' +
            '</div>' +
            '<div class="col-2 col-sm-1">' +
                '<button type="button" class="btn btn-outline-danger btn-remove-ops w-100" ' +
                    'aria-label="Hapus biaya operasional">&times;</button>' +
            '</div>';

        const amountInput = row.querySelector('.ops-amount-input');
        bindNumberInput(amountInput);
        row.querySelector('.ops-label-input').addEventListener('input', updateReviewSummary);
        row.querySelector('.btn-remove-ops').addEventListener('click', function () {
            removeOpsRow(row);
        });

        return row;
    }

    function addOpsRow(keterangan, jumlah) {
        const container = document.getElementById('opsRows');
        if (!container) {
            return;
        }
        container.appendChild(createOpsRow(keterangan, jumlah));
        updateOpsTotal();
        updateReviewSummary();
    }

    function removeOpsRow(row) {
        row.remove();
        updateOpsTotal();
        updateReviewSummary();
    }

    function bindNumberInput(input) {
        if (!input || input.dataset.bound === '1') {
            return;
        }
        input.dataset.bound = '1';

        input.addEventListener('input', function () {
            formatMoneyField(input);
            updateUnitTotals();
            updateModalProgress();
            updateOpsTotal();
            updateReviewSummary();
        });
    }

    function bindDecimalInput(input) {
        if (!input || input.dataset.bound === '1') {
            return;
        }
        input.dataset.bound = '1';

        input.addEventListener('input', function () {
            formatDecimalField(input);
            updateReviewSummary();
        });
    }

    function bindAllNumberInputs() {
        document.querySelectorAll('.number-input').forEach(bindNumberInput);
    }

    function bindAllDecimalInputs() {
        document.querySelectorAll('.decimal-input').forEach(bindDecimalInput);
    }

    function getNumericFieldValue(id) {
        const el = document.getElementById(id);
        if (!el) {
            return 0;
        }
        if (el.classList.contains('decimal-input')) {
            return parseDecimalId(el.dataset.rawValue || el.value);
        }
        return parseRupiah(el.dataset.rawValue || el.value);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function calculateProfit(totalHasilJual, totalModal, persenPemodal, persenOperator, investors, operationalCosts) {
        if (Math.round((persenPemodal + persenOperator) * 100) / 100 !== 100) {
            return null;
        }
        if (totalModal <= 0 || investors.length === 0) {
            return null;
        }

        const investorTotal = investors.reduce(function (sum, inv) {
            return sum + inv.modal;
        }, 0);
        if (investorTotal !== totalModal) {
            return null;
        }

        const costs = (operationalCosts || []).filter(function (cost) {
            return cost.keterangan || cost.jumlah > 0;
        });
        const totalBiayaOperasional = costs.reduce(function (sum, cost) {
            return sum + cost.jumlah;
        }, 0);

        const keuntunganKotor = totalHasilJual - totalModal;
        const rugi = keuntunganKotor < 0;
        const keuntunganBersih = keuntunganKotor - totalBiayaOperasional;
        const canSplitProfit = !rugi && keuntunganBersih > 0;
        const poolPemodal = canSplitProfit ? Math.round(keuntunganBersih * (persenPemodal / 100)) : 0;
        const poolOperator = canSplitProfit ? Math.round(keuntunganBersih * (persenOperator / 100)) : 0;

        const results = investors.map(function (inv) {
            const share = inv.modal / totalModal;
            const pengembalian = Math.round(totalModal * share);
            const profit = canSplitProfit ? Math.round(poolPemodal * share) : 0;
            return {
                nama: inv.nama,
                modal: inv.modal,
                pengembalian_modal: pengembalian,
                profit: profit,
                total: pengembalian + profit,
            };
        });

        return {
            keuntungan_kotor: keuntunganKotor,
            total_biaya_operasional: totalBiayaOperasional,
            biaya_operasional: costs,
            keuntungan_bersih: keuntunganBersih,
            rugi: rugi,
            profit_dapat_dibagikan: canSplitProfit,
            pool_pemodal: poolPemodal,
            pool_operator: poolOperator,
            investors: results,
        };
    }

    function getFormValue(id) {
        const el = document.getElementById(id);
        return el ? el.value : '';
    }

    function getInitials(name) {
        return String(name || '?')
            .trim()
            .split(/\s+/)
            .map(function (w) { return w[0]; })
            .join('')
            .slice(0, 2)
            .toUpperCase();
    }

    function buildReviewDashboard(data) {
        const kotorClass = data.keuntunganKotor >= 0 ? 'text-profit' : 'text-loss';
        const kotorLabel = data.keuntunganKotor >= 0 ? 'Profit Kotor' : 'Rugi';
        const kotorKpiClass = data.keuntunganKotor >= 0 ? 'review-kpi--profit' : 'review-kpi--loss';
        const bersihClass = data.keuntunganBersih >= 0 ? 'text-profit' : 'text-loss';
        const bersihKpiClass = data.keuntunganBersih >= 0 ? 'review-kpi--profit' : 'review-kpi--loss';
        const modeBadge = data.mode === 'unit' ? 'Per Unit' : 'Langsung';

        let modeDetailsHtml = '';
        if (data.mode === 'unit') {
            modeDetailsHtml =
                '<div class="col-6 col-md-3"><span class="text-muted small d-block">Jumlah Unit</span><strong>' + data.units.toLocaleString('id-ID') + ' pcs</strong></div>' +
                '<div class="col-6 col-md-3"><span class="text-muted small d-block">Harga Beli / pcs</span><strong class="money">' + formatRupiah(data.buy) + '</strong></div>' +
                '<div class="col-6 col-md-3"><span class="text-muted small d-block">Harga Jual / pcs</span><strong class="money">' + formatRupiah(data.sell) + '</strong></div>';
        }

        let investorChipsHtml = '';
        data.investors.forEach(function (inv, index) {
            const share = data.totalModal > 0 ? Math.round((inv.modal / data.totalModal) * 1000) / 10 : 0;
            investorChipsHtml +=
                '<div class="review-investor-chip">' +
                    '<div>' +
                        '<div class="chip-name">' + escapeHtml(inv.nama) + '</div>' +
                        '<div class="chip-meta">Pemodal ' + (index + 1) + ' · ' + share + '% kontribusi</div>' +
                    '</div>' +
                    '<div class="money fw-semibold text-modal">' + formatRupiah(inv.modal) + '</div>' +
                '</div>';
        });

        let calcSectionsHtml = '';
        if (data.calc && data.calc.rugi) {
            calcSectionsHtml =
                '<div class="alert alert-warning d-flex align-items-center gap-2 mb-0" role="alert">' +
                    '<span class="fs-5">⚠️</span>' +
                    '<div><strong>Proyek mengalami rugi</strong><br><span class="small">Tidak ada profit yang dibagikan ke pemodal maupun operator.</span></div>' +
                '</div>';
        } else if (data.calc && !data.calc.profit_dapat_dibagikan) {
            calcSectionsHtml =
                '<div class="alert alert-warning d-flex align-items-center gap-2 mb-0" role="alert">' +
                    '<span class="fs-5">⚠️</span>' +
                    '<div><strong>Biaya operasional melebihi profit kotor</strong><br><span class="small">Tidak ada profit yang dibagikan ke pemodal maupun operator.</span></div>' +
                '</div>';
        } else if (data.calc) {
            let pengembalianRows = '';
            let profitRows = '';
            let totalCards = '';

            data.calc.investors.forEach(function (inv) {
                pengembalianRows +=
                    '<tr><td>' + escapeHtml(inv.nama) + '</td>' +
                    '<td class="text-end money">' + formatRupiah(inv.modal) + '</td>' +
                    '<td class="text-end money text-modal fw-semibold">' + formatRupiah(inv.pengembalian_modal) + '</td></tr>';

                profitRows +=
                    '<tr><td>' + escapeHtml(inv.nama) + '</td>' +
                    '<td class="text-end money text-profit fw-semibold">' + formatRupiah(inv.profit) + '</td></tr>';

                totalCards +=
                    '<div class="review-total-card">' +
                        '<div class="total-header">' + escapeHtml(inv.nama) + '</div>' +
                        '<div class="total-row"><span class="text-muted">Pengembalian Modal</span><span class="money">' + formatRupiah(inv.pengembalian_modal) + '</span></div>' +
                        '<div class="total-row"><span class="text-muted">Profit</span><span class="money text-profit">' + formatRupiah(inv.profit) + '</span></div>' +
                        '<div class="total-row total-row--grand"><span>Total Diterima</span><span class="money">' + formatRupiah(inv.total) + '</span></div>' +
                    '</div>';
            });

            profitRows +=
                '<tr class="review-operator-row">' +
                    '<td><span class="badge bg-primary bg-opacity-10 text-primary me-1">Operator</span> ' + escapeHtml(data.namaOperator || '-') + '</td>' +
                    '<td class="text-end money text-modal fw-semibold">' + formatRupiah(data.calc.pool_operator) + '</td>' +
                '</tr>';

            calcSectionsHtml =
                '<div class="row g-3 mb-3">' +
                    '<div class="col-12 col-md-6">' +
                        '<div class="card review-pool-card review-pool-card--pemodal h-100">' +
                            '<div class="card-body d-flex align-items-center gap-3">' +
                                '<div class="pool-avatar">P</div>' +
                                '<div class="flex-grow-1">' +
                                    '<div class="small text-muted">Pool Pemodal · ' + data.persenPemodal + '%</div>' +
                                    '<div class="h5 money text-profit mb-0">' + formatRupiah(data.calc.pool_pemodal) + '</div>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="col-12 col-md-6">' +
                        '<div class="card review-pool-card review-pool-card--operator h-100">' +
                            '<div class="card-body d-flex align-items-center gap-3">' +
                                '<div class="pool-avatar">' + escapeHtml(getInitials(data.namaOperator)) + '</div>' +
                                '<div class="flex-grow-1">' +
                                    '<div class="small text-muted">Operator · ' + escapeHtml(data.namaOperator || '-') + ' · ' + data.persenOperator + '%</div>' +
                                    '<div class="h5 money text-modal mb-0">' + formatRupiah(data.calc.pool_operator) + '</div>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +

                '<div class="card review-section-card">' +
                    '<div class="card-header"><span class="section-icon bg-success bg-opacity-10 text-success">↩</span>Pengembalian Modal</div>' +
                    '<div class="card-body p-0">' +
                        '<div class="table-responsive">' +
                            '<table class="table review-table table-hover mb-0">' +
                                '<thead class="table-light"><tr><th>Pemodal</th><th class="text-end">Modal</th><th class="text-end">Dikembalikan</th></tr></thead>' +
                                '<tbody>' + pengembalianRows + '</tbody>' +
                            '</table>' +
                        '</div>' +
                    '</div>' +
                '</div>' +

                '<div class="card review-section-card">' +
                    '<div class="card-header"><span class="section-icon bg-success bg-opacity-10 text-success">%</span>Bagi Hasil Profit</div>' +
                    '<div class="card-body p-0">' +
                        '<div class="table-responsive">' +
                            '<table class="table review-table table-hover mb-0">' +
                                '<thead class="table-light"><tr><th>Penerima</th><th class="text-end">Jumlah</th></tr></thead>' +
                                '<tbody>' + profitRows + '</tbody>' +
                            '</table>' +
                        '</div>' +
                    '</div>' +
                '</div>' +

                '<div class="card review-section-card">' +
                    '<div class="card-header"><span class="section-icon bg-primary bg-opacity-10 text-primary">∑</span>Total per Pemodal</div>' +
                    '<div class="card-body">' + totalCards + '</div>' +
                '</div>';
        } else if (data.investors.length > 0) {
            calcSectionsHtml =
                '<div class="alert alert-info small mb-0">Lengkapi data modal pemodal agar simulasi perhitungan dapat ditampilkan.</div>';
        }

        const opsHtml = data.operationalCosts.length > 0
            ? '<div class="card review-section-card"><div class="card-header"><span class="section-icon bg-warning bg-opacity-10 text-warning">−</span>Biaya Operasional</div><div class="card-body p-0"><div class="table-responsive"><table class="table review-table table-hover mb-0"><thead class="table-light"><tr><th>Keterangan</th><th class="text-end">Jumlah</th></tr></thead><tbody>' +
                data.operationalCosts.map(function (cost) {
                    return '<tr><td>' + escapeHtml(cost.keterangan) + '</td><td class="text-end money text-warning fw-semibold">' + formatRupiah(cost.jumlah) + '</td></tr>';
                }).join('') +
                '<tr class="table-light"><td class="fw-semibold">Total Biaya Operasional</td><td class="text-end money fw-bold text-warning">' + formatRupiah(data.totalBiayaOperasional) + '</td></tr>' +
                '</tbody></table></div></div></div>'
            : '';

        const catatanHtml = data.catatan
            ? '<div class="card review-section-card"><div class="card-header"><span class="section-icon bg-secondary bg-opacity-10 text-secondary">📝</span>Catatan</div><div class="card-body"><div class="review-catatan">' + escapeHtml(data.catatan) + '</div></div></div>'
            : '';

        return (
            '<div class="row g-3 mb-4 review-kpi-row">' +
                '<div class="col-6 col-md-4 col-lg"><div class="card review-kpi review-kpi--modal"><div class="card-body"><div class="review-kpi-label">Total Modal</div><div class="review-kpi-value money text-modal">' + formatRupiah(data.totalModal) + '</div></div></div></div>' +
                '<div class="col-6 col-md-4 col-lg"><div class="card review-kpi review-kpi--jual"><div class="card-body"><div class="review-kpi-label">Hasil Jual</div><div class="review-kpi-value money">' + formatRupiah(data.totalJual) + '</div></div></div></div>' +
                '<div class="col-6 col-md-4 col-lg"><div class="card review-kpi ' + kotorKpiClass + '"><div class="card-body"><div class="review-kpi-label">' + kotorLabel + '</div><div class="review-kpi-value money ' + kotorClass + '">' + formatRupiah(Math.abs(data.keuntunganKotor)) + '</div></div></div></div>' +
                '<div class="col-6 col-md-4 col-lg"><div class="card review-kpi review-kpi--ops"><div class="card-body"><div class="review-kpi-label">Biaya Operasional</div><div class="review-kpi-value money text-warning">' + formatRupiah(data.totalBiayaOperasional) + '</div></div></div></div>' +
                '<div class="col-6 col-md-4 col-lg"><div class="card review-kpi ' + bersihKpiClass + '"><div class="card-body"><div class="review-kpi-label">Profit Bersih</div><div class="review-kpi-value money ' + bersihClass + '">' + formatRupiah(Math.abs(data.keuntunganBersih)) + '</div></div></div></div>' +
                '<div class="col-12 col-md-4 col-lg"><div class="card review-kpi review-kpi--split"><div class="card-body"><div class="review-kpi-label">Bagi Hasil</div><div class="review-kpi-value" style="font-size:0.95rem">' + data.persenPemodal + '% <span class="text-muted">/</span> ' + data.persenOperator + '%</div><div class="review-split-bar mt-2"><div class="review-split-bar__pemodal" style="width:' + data.persenPemodal + '%"></div><div class="review-split-bar__operator" style="width:' + data.persenOperator + '%"></div></div></div></div></div>' +
            '</div>' +

            '<div class="card review-project-card mb-3">' +
                '<div class="card-body">' +
                    '<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">' +
                        '<div>' +
                            '<div class="project-title">' + escapeHtml(data.namaProyek || 'Proyek Baru') + '</div>' +
                            '<div class="text-muted small mt-1">Operator: <strong class="text-dark">' + escapeHtml(data.namaOperator || '-') + '</strong></div>' +
                        '</div>' +
                        '<span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2">' + modeBadge + '</span>' +
                    '</div>' +
                    (modeDetailsHtml ? '<div class="row g-3">' + modeDetailsHtml + '</div>' : '') +
                '</div>' +
            '</div>' +

            '<div class="card review-section-card">' +
                '<div class="card-header"><span class="section-icon bg-primary bg-opacity-10 text-primary">👥</span>Kontribusi Pemodal</div>' +
                '<div class="card-body">' + (investorChipsHtml || '<p class="text-muted small mb-0">Belum ada pemodal.</p>') + '</div>' +
            '</div>' +

            calcSectionsHtml +
            opsHtml +
            catatanHtml
        );
    }

    function updateReviewSummary() {
        const review = document.getElementById('reviewSummary');
        if (!review) {
            return;
        }

        const mode = getActiveMode();
        const investors = [];
        getInvestorRows().forEach(function (row) {
            const name = row.querySelector('.investor-name-input')?.value || '';
            const modal = parseRupiah(row.querySelector('.investor-modal-input')?.value);
            if (name || modal > 0) {
                investors.push({ nama: name || '-', modal: modal });
            }
        });

        const totalModal = getTotalModalTarget();
        const totalJual = getTotalHasilJual();
        const persenPemodal = getNumericFieldValue('persen_pemodal');
        const persenOperator = getNumericFieldValue('persen_operator');
        const operationalCosts = getOperationalCostsFromForm();
        const totalBiayaOperasional = getOperationalTotal();
        const keuntunganKotor = totalJual - totalModal;
        const keuntunganBersih = keuntunganKotor - totalBiayaOperasional;

        review.innerHTML = buildReviewDashboard({
            mode: mode,
            namaProyek: getFormValue('nama_proyek'),
            namaOperator: getFormValue('nama_operator'),
            catatan: getFormValue('catatan'),
            units: getNumericFieldValue('jumlah_unit'),
            buy: parseRupiah(getFormValue('harga_beli')),
            sell: parseRupiah(getFormValue('harga_jual')),
            totalModal: totalModal,
            totalJual: totalJual,
            keuntunganKotor: keuntunganKotor,
            totalBiayaOperasional: totalBiayaOperasional,
            keuntunganBersih: keuntunganBersih,
            operationalCosts: operationalCosts,
            persenPemodal: persenPemodal,
            persenOperator: persenOperator,
            investors: investors,
            calc: calculateProfit(totalJual, totalModal, persenPemodal, persenOperator, investors, operationalCosts),
        });
    }

    function showWizardStep(step) {
        const panes = document.querySelectorAll('.wizard-pane');
        const tabs = document.querySelectorAll('.wizard-steps .nav-link');

        panes.forEach(function (pane) {
            pane.classList.toggle('active', parseInt(pane.dataset.step, 10) === step);
        });

        tabs.forEach(function (tab) {
            tab.classList.toggle('active', parseInt(tab.dataset.step, 10) === step);
        });

        if (step === 3) {
            updateReviewSummary();
        }

        const wizard = document.getElementById('projectWizard');
        if (wizard) {
            wizard.dataset.currentStep = String(step);
        }
    }

    function validateStep(step) {
        const pane = document.querySelector('.wizard-pane[data-step="' + step + '"]');
        if (!pane) {
            return true;
        }

        const inputs = pane.querySelectorAll('input, textarea, select');
        let valid = true;

        inputs.forEach(function (input) {
            if (input.offsetParent === null && !input.closest('.mode-fields.active')) {
                return;
            }
            if (!input.checkValidity()) {
                valid = false;
                input.reportValidity();
            }
        });

        if (step === 2) {
            const target = getTotalModalTarget();
            const collected = getInvestorTotal();
            if (target > 0 && collected !== target) {
                valid = false;
                window.showToasts([{
                    type: 'danger',
                    text: 'Total modal pemodal harus sama dengan total modal proyek (' +
                        formatRupiah(target) + ').'
                }]);
            }
        }

        if (step === 1) {
            const mode = getActiveMode();
            if (mode === 'unit') {
                const units = getNumericFieldValue('jumlah_unit');
                if (units < 1) {
                    valid = false;
                    window.showToasts([{
                        type: 'danger',
                        text: 'Jumlah unit minimal 1 pcs.'
                    }]);
                }
            }

            const pemodal = getNumericFieldValue('persen_pemodal');
            const operator = getNumericFieldValue('persen_operator');
            if (Math.round((pemodal + operator) * 100) / 100 !== 100) {
                valid = false;
                window.showToasts([{
                    type: 'danger',
                    text: 'Persentase pemodal dan operator harus berjumlah 100%.'
                }]);
            }
        }

        return valid;
    }

    function initWizard() {
        const wizard = document.getElementById('projectWizard');
        if (!wizard) {
            return;
        }

        showWizardStep(1);

        document.querySelectorAll('[data-wizard-next]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const current = parseInt(wizard.dataset.currentStep || '1', 10);
                if (!validateStep(current)) {
                    return;
                }
                showWizardStep(Math.min(3, current + 1));
            });
        });

        document.querySelectorAll('[data-wizard-prev]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const current = parseInt(wizard.dataset.currentStep || '1', 10);
                showWizardStep(Math.max(1, current - 1));
            });
        });

        document.querySelectorAll('.wizard-steps .nav-link').forEach(function (tab) {
            tab.addEventListener('click', function (event) {
                event.preventDefault();
                const targetStep = parseInt(tab.dataset.step, 10);
                const current = parseInt(wizard.dataset.currentStep || '1', 10);

                if (targetStep > current) {
                    for (let s = current; s < targetStep; s++) {
                        if (!validateStep(s)) {
                            return;
                        }
                    }
                }

                showWizardStep(targetStep);
            });
        });
    }

    function initModeToggle() {
        document.querySelectorAll('input[name="mode_input"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                toggleModeFields();
                updateUnitTotals();
                updateModalProgress();
            });
        });
        toggleModeFields();
    }

    function initUnitInputs() {
        ['jumlah_unit', 'harga_beli', 'harga_jual', 'total_modal', 'total_hasil_jual'].forEach(function (id) {
            const el = document.getElementById(id);
            if (!el) {
                return;
            }
            el.addEventListener('input', function () {
                updateUnitTotals();
                updateModalProgress();
                updateReviewSummary();
            });
        });

        ['persen_pemodal', 'persen_operator', 'nama_proyek', 'nama_operator', 'catatan'].forEach(function (id) {
            const el = document.getElementById(id);
            if (!el) {
                return;
            }
            el.addEventListener('input', updateReviewSummary);
        });
    }

    function initOpsRows() {
        const addBtn = document.getElementById('btnAddOps');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                addOpsRow('', 0);
            });
        }

        document.querySelectorAll('#opsRows .btn-remove-ops').forEach(function (btn) {
            btn.addEventListener('click', function () {
                removeOpsRow(btn.closest('.ops-row'));
            });
        });

        document.querySelectorAll('#opsRows .ops-label-input').forEach(function (input) {
            input.addEventListener('input', updateReviewSummary);
        });

        updateOpsTotal();
    }

    function initInvestorRows() {
        const addBtn = document.getElementById('btnAddInvestor');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                addInvestorRow('', 0);
            });
        }

        document.querySelectorAll('#investorRows .btn-remove-investor').forEach(function (btn) {
            btn.addEventListener('click', function () {
                removeInvestorRow(btn.closest('.investor-row'));
            });
        });

        document.querySelectorAll('#investorRows .investor-name-input').forEach(function (input) {
            input.addEventListener('input', updateReviewSummary);
        });
    }

    function prepareFormSubmit() {
        document.querySelectorAll('form').forEach(function (form) {
            if (!form.querySelector('.number-input, .decimal-input')) {
                return;
            }

            form.addEventListener('submit', function () {
                form.querySelectorAll('.number-input').forEach(function (input) {
                    input.value = String(parseRupiah(input.dataset.rawValue || input.value));
                });
                form.querySelectorAll('.decimal-input').forEach(function (input) {
                    const raw = input.dataset.rawValue || String(parseDecimalId(input.value));
                    input.value = raw === '' ? '' : String(raw);
                });
            });
        });
    }

    window.showToasts = function (messages) {
        const container = document.getElementById('toastContainer');
        if (!container || !messages || messages.length === 0) {
            return;
        }

        messages.forEach(function (msg) {
            const toastEl = document.createElement('div');
            toastEl.className = 'toast align-items-center text-bg-' + (msg.type || 'primary') + ' border-0';
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');
            toastEl.innerHTML =
                '<div class="d-flex">' +
                    '<div class="toast-body">' + msg.text + '</div>' +
                    '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
                '</div>';

            container.appendChild(toastEl);
            const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', function () {
                toastEl.remove();
            });
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        initMoneyInputDisplay();
        bindAllNumberInputs();
        bindAllDecimalInputs();
        initModeToggle();
        initUnitInputs();
        initOpsRows();
        initInvestorRows();
        initWizard();
        updateUnitTotals();
        updateModalProgress();
        prepareFormSubmit();
    });
})();
