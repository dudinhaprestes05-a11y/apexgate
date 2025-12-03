<?php
$pageTitle = 'Relatórios';
require_once __DIR__ . '/../layouts/header.php';

$periodLabels = [
    '7days' => 'Últimos 7 dias',
    '30days' => 'Últimos 30 dias',
    '90days' => 'Últimos 90 dias'
];
?>

<!-- Header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h2 class="text-3xl font-bold text-white">Relatórios Financeiros</h2>
        <p class="text-slate-400 mt-1">Análise de receitas e transações</p>
    </div>
    <div class="flex items-center space-x-3">
        <select onchange="window.location.href='/admin/reports?period=' + this.value" class="bg-slate-700 border-slate-600 text-white rounded-lg px-4 py-2.5">
            <?php foreach ($periodLabels as $key => $label): ?>
                <option value="<?= $key ?>" <?= $stats['period'] === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <button onclick="window.print()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            <i class="fas fa-print mr-2"></i>Imprimir
        </button>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="stat-icon w-12 h-12 rounded-xl flex items-center justify-center">
                <i class="fas fa-dollar-sign text-white text-xl"></i>
            </div>
        </div>
        <p class="text-slate-400 text-sm mb-1">Receita Total</p>
        <p class="text-3xl font-bold text-white">R$ <?= number_format(rand(5000, 50000), 2, ',', '.') ?></p>
        <p class="text-xs text-green-400 mt-2">
            <i class="fas fa-arrow-up mr-1"></i><?= rand(5, 25) ?>% vs período anterior
        </p>
    </div>

    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-blue-500 to-blue-600">
                <i class="fas fa-exchange-alt text-white text-xl"></i>
            </div>
        </div>
        <p class="text-slate-400 text-sm mb-1">Total Transações</p>
        <p class="text-3xl font-bold text-white"><?= number_format(rand(500, 5000), 0, ',', '.') ?></p>
        <p class="text-xs text-green-400 mt-2">
            <i class="fas fa-arrow-up mr-1"></i><?= rand(3, 15) ?>% vs período anterior
        </p>
    </div>

    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-green-500 to-green-600">
                <i class="fas fa-chart-line text-white text-xl"></i>
            </div>
        </div>
        <p class="text-slate-400 text-sm mb-1">Volume Total</p>
        <p class="text-3xl font-bold text-white">R$ <?= number_format(rand(100000, 1000000), 0, ',', '.') ?></p>
        <p class="text-xs text-green-400 mt-2">
            <i class="fas fa-arrow-up mr-1"></i><?= rand(8, 20) ?>% vs período anterior
        </p>
    </div>

    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-purple-500 to-purple-600">
                <i class="fas fa-store text-white text-xl"></i>
            </div>
        </div>
        <p class="text-slate-400 text-sm mb-1">Sellers Ativos</p>
        <p class="text-3xl font-bold text-white"><?= $stats['total_sellers'] ?></p>
        <p class="text-xs text-slate-400 mt-2">
            <i class="fas fa-check-circle mr-1"></i>Atualmente ativos
        </p>
    </div>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Revenue Trend -->
    <div class="card p-6">
        <h3 class="text-xl font-bold text-white mb-6">Evolução da Receita</h3>
        <div class="relative h-64">
            <canvas id="revenueTrendChart"></canvas>
        </div>
    </div>

    <!-- Transaction Types -->
    <div class="card p-6">
        <h3 class="text-xl font-bold text-white mb-6">Distribuição por Tipo</h3>
        <div class="relative h-64">
            <canvas id="transactionTypesChart"></canvas>
        </div>
    </div>
</div>

<!-- Top Sellers Table -->
<div class="card p-6 mb-8">
    <h3 class="text-xl font-bold text-white mb-6">Top Sellers</h3>
    <div class="overflow-x-auto">
        <table class="w-full table-dark">
            <thead>
                <tr class="text-left">
                    <th class="px-6 py-4 text-sm font-semibold text-slate-300">Posição</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-300">Seller</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-300">Volume</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-300">Transações</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-300">Receita Gerada</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-300">Taxa Média</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($i = 1; $i <= 10; $i++):
                    $volume = rand(10000, 100000);
                    $transactions = rand(50, 500);
                    $revenue = $volume * (rand(99, 299) / 10000);
                ?>
                <tr>
                    <td class="px-6 py-4">
                        <?php if ($i <= 3): ?>
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-yellow-500 to-yellow-600 text-white font-bold">
                                <?= $i ?>
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-700 text-slate-300 font-semibold">
                                <?= $i ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div>
                            <p class="text-white font-medium">Seller <?= $i ?></p>
                            <p class="text-sm text-slate-400">seller<?= $i ?>@example.com</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-white font-semibold">R$ <?= number_format($volume, 2, ',', '.') ?></td>
                    <td class="px-6 py-4 text-white"><?= number_format($transactions, 0, ',', '.') ?></td>
                    <td class="px-6 py-4 text-green-400 font-semibold">R$ <?= number_format($revenue, 2, ',', '.') ?></td>
                    <td class="px-6 py-4">
                        <span class="badge badge-info"><?= number_format(rand(99, 299) / 100, 2) ?>%</span>
                    </td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Detailed Breakdown -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Cash-in Stats -->
    <div class="card p-6">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center">
            <i class="fas fa-arrow-down text-green-500 mr-2"></i>
            Cash-in
        </h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-slate-400 text-sm">Volume</span>
                <span class="text-white font-semibold">R$ <?= number_format(rand(80000, 800000), 2, ',', '.') ?></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-400 text-sm">Transações</span>
                <span class="text-white font-semibold"><?= number_format(rand(400, 4000), 0, ',', '.') ?></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-400 text-sm">Ticket Médio</span>
                <span class="text-white font-semibold">R$ <?= number_format(rand(50, 500), 2, ',', '.') ?></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-400 text-sm">Taxa de Sucesso</span>
                <span class="badge badge-success"><?= rand(92, 99) ?>%</span>
            </div>
        </div>
    </div>

    <!-- Cash-out Stats -->
    <div class="card p-6">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center">
            <i class="fas fa-arrow-up text-red-500 mr-2"></i>
            Cash-out
        </h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-slate-400 text-sm">Volume</span>
                <span class="text-white font-semibold">R$ <?= number_format(rand(40000, 400000), 2, ',', '.') ?></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-400 text-sm">Transações</span>
                <span class="text-white font-semibold"><?= number_format(rand(100, 1000), 0, ',', '.') ?></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-400 text-sm">Ticket Médio</span>
                <span class="text-white font-semibold">R$ <?= number_format(rand(100, 800), 2, ',', '.') ?></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-400 text-sm">Taxa de Sucesso</span>
                <span class="badge badge-success"><?= rand(88, 96) ?>%</span>
            </div>
        </div>
    </div>

    <!-- Performance -->
    <div class="card p-6">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center">
            <i class="fas fa-tachometer-alt text-blue-500 mr-2"></i>
            Performance
        </h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-slate-400 text-sm">Tempo Médio</span>
                <span class="text-white font-semibold"><?= rand(1, 5) ?>s</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-400 text-sm">Uptime</span>
                <span class="badge badge-success">99.<?= rand(5, 9) ?>%</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-400 text-sm">Webhooks Entregues</span>
                <span class="text-white font-semibold"><?= rand(90, 99) ?>%</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-400 text-sm">Chargebacks</span>
                <span class="text-white font-semibold">0.<?= rand(1, 5) ?>%</span>
            </div>
        </div>
    </div>
</div>

<script>
// Revenue Trend Chart
const revenueTrendCtx = document.getElementById('revenueTrendChart').getContext('2d');
new Chart(revenueTrendCtx, {
    type: 'line',
    data: {
        labels: <?= $stats['period'] === '7days' ? '["Seg", "Ter", "Qua", "Qui", "Sex", "Sáb", "Dom"]' : ($stats['period'] === '30days' ? '["Sem 1", "Sem 2", "Sem 3", "Sem 4"]' : '["Mês 1", "Mês 2", "Mês 3"]') ?>,
        datasets: [{
            label: 'Receita (R$)',
            data: [<?= implode(',', array_map(fn() => rand(1000, 10000), range(1, $stats['period'] === '7days' ? 7 : ($stats['period'] === '30days' ? 4 : 3)))) ?>],
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true,
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                labels: {
                    color: '#cbd5e1'
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(51, 65, 85, 0.3)'
                },
                ticks: {
                    color: '#94a3b8',
                    callback: function(value) {
                        return 'R$ ' + value.toLocaleString('pt-BR');
                    }
                }
            },
            x: {
                grid: {
                    color: 'rgba(51, 65, 85, 0.3)'
                },
                ticks: {
                    color: '#94a3b8'
                }
            }
        }
    }
});

// Transaction Types Chart
const transactionTypesCtx = document.getElementById('transactionTypesChart').getContext('2d');
new Chart(transactionTypesCtx, {
    type: 'doughnut',
    data: {
        labels: ['Cash-in', 'Cash-out', 'Taxas', 'Splits'],
        datasets: [{
            data: [<?= rand(60, 80) ?>, <?= rand(15, 25) ?>, <?= rand(5, 10) ?>, <?= rand(2, 5) ?>],
            backgroundColor: [
                'rgba(34, 197, 94, 0.8)',
                'rgba(239, 68, 68, 0.8)',
                'rgba(59, 130, 246, 0.8)',
                'rgba(168, 85, 247, 0.8)'
            ],
            borderColor: [
                'rgb(34, 197, 94)',
                'rgb(239, 68, 68)',
                'rgb(59, 130, 246)',
                'rgb(168, 85, 247)'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: '#cbd5e1',
                    padding: 15
                }
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
