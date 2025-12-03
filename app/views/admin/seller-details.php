<?php
$pageTitle = 'Detalhes do Seller';
require_once __DIR__ . '/../../models/SellerDocument.php';
require_once __DIR__ . '/../layouts/header.php';

$statusColors = [
    'active' => 'badge-success',
    'pending' => 'badge-warning',
    'inactive' => 'badge',
    'blocked' => 'badge-danger',
    'rejected' => 'badge-danger'
];
?>

<!-- Header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <a href="/admin/sellers" class="text-blue-400 hover:text-blue-300 text-sm mb-3 inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>Voltar
        </a>
        <h2 class="text-3xl font-bold text-white mt-2 flex items-center">
            <?= htmlspecialchars($seller['name']) ?>
            <span class="badge <?= $statusColors[$seller['status']] ?? 'badge' ?> ml-3 text-sm">
                <?= ucfirst($seller['status']) ?>
            </span>
        </h2>
        <p class="text-slate-400 mt-1"><?= htmlspecialchars($seller['email']) ?></p>
    </div>
    <?php if ($seller['status'] === 'pending'): ?>
    <div class="flex space-x-3">
        <form method="POST" action="/admin/sellers/<?= $seller['id'] ?>/approve" class="inline">
            <button type="submit" class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                <i class="fas fa-check mr-2"></i>Aprovar
            </button>
        </form>
        <button onclick="openRejectModal()" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
            <i class="fas fa-times mr-2"></i>Rejeitar
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="stat-icon w-12 h-12 rounded-xl flex items-center justify-center">
                <i class="fas fa-wallet text-white text-xl"></i>
            </div>
        </div>
        <p class="text-slate-400 text-sm mb-1">Saldo Disponível</p>
        <p class="text-3xl font-bold text-white">R$ <?= number_format($seller['balance'], 2, ',', '.') ?></p>
    </div>

    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-green-500 to-green-600">
                <i class="fas fa-arrow-down text-white text-xl"></i>
            </div>
        </div>
        <p class="text-slate-400 text-sm mb-1">Total Cash-in</p>
        <p class="text-3xl font-bold text-white">R$ <?= number_format($cashinStats['total'] ?? 0, 2, ',', '.') ?></p>
    </div>

    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-red-500 to-red-600">
                <i class="fas fa-arrow-up text-white text-xl"></i>
            </div>
        </div>
        <p class="text-slate-400 text-sm mb-1">Total Cash-out</p>
        <p class="text-3xl font-bold text-white">R$ <?= number_format($cashoutStats['total'] ?? 0, 2, ',', '.') ?></p>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column - 2/3 width -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Information Card -->
        <div class="card p-6">
            <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                Informações do Seller
            </h3>
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="text-sm font-medium text-slate-400">ID</label>
                    <p class="text-white mt-1 font-semibold">#<?= $seller['id'] ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-400">Tipo de Pessoa</label>
                    <p class="text-white mt-1"><?= $seller['person_type'] === 'individual' ? 'Pessoa Física' : 'Pessoa Jurídica' ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-400">Documento</label>
                    <p class="text-white mt-1 font-mono"><?= htmlspecialchars($seller['document']) ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-400">Telefone</label>
                    <p class="text-white mt-1"><?= htmlspecialchars($seller['phone'] ?? 'Não informado') ?></p>
                </div>
                <?php if ($seller['person_type'] === 'business'): ?>
                <div class="col-span-2">
                    <label class="text-sm font-medium text-slate-400">Razão Social</label>
                    <p class="text-white mt-1"><?= htmlspecialchars($seller['company_name'] ?? 'Não informado') ?></p>
                </div>
                <?php if ($seller['trading_name']): ?>
                <div class="col-span-2">
                    <label class="text-sm font-medium text-slate-400">Nome Fantasia</label>
                    <p class="text-white mt-1"><?= htmlspecialchars($seller['trading_name']) ?></p>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                <div>
                    <label class="text-sm font-medium text-slate-400">Cadastro</label>
                    <p class="text-white mt-1"><?= date('d/m/Y H:i', strtotime($seller['created_at'])) ?></p>
                </div>
                <?php if ($seller['approved_at']): ?>
                <div>
                    <label class="text-sm font-medium text-slate-400">Aprovado em</label>
                    <p class="text-white mt-1"><?= date('d/m/Y H:i', strtotime($seller['approved_at'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Fees Configuration Card -->
        <div class="card p-6">
            <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                <i class="fas fa-percentage text-yellow-500 mr-2"></i>
                Configuração de Taxas
            </h3>
            <form method="POST" action="/admin/sellers/<?= $seller['id'] ?>/fees" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Cash-in Fees -->
                    <div class="p-4 bg-slate-800 bg-opacity-50 rounded-lg border border-slate-700">
                        <h4 class="text-lg font-semibold text-white mb-4 flex items-center">
                            <i class="fas fa-arrow-down text-green-500 mr-2"></i>
                            Taxas Cash-in
                        </h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    Taxa Percentual (%)
                                </label>
                                <div class="relative">
                                    <input type="number"
                                           name="fee_percentage_cashin"
                                           value="<?= $seller['fee_percentage_cashin'] * 100 ?>"
                                           step="0.01"
                                           min="0"
                                           max="15"
                                           class="w-full px-4 py-2.5 pr-8"
                                           required>
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">%</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Atual: <?= number_format($seller['fee_percentage_cashin'] * 100, 2) ?>%</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    Taxa Fixa (R$)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">R$</span>
                                    <input type="number"
                                           name="fee_fixed_cashin"
                                           value="<?= $seller['fee_fixed_cashin'] ?>"
                                           step="0.01"
                                           min="0"
                                           class="w-full px-4 py-2.5 pl-11"
                                           required>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Atual: R$ <?= number_format($seller['fee_fixed_cashin'], 2, ',', '.') ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Cash-out Fees -->
                    <div class="p-4 bg-slate-800 bg-opacity-50 rounded-lg border border-slate-700">
                        <h4 class="text-lg font-semibold text-white mb-4 flex items-center">
                            <i class="fas fa-arrow-up text-red-500 mr-2"></i>
                            Taxas Cash-out
                        </h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    Taxa Percentual (%)
                                </label>
                                <div class="relative">
                                    <input type="number"
                                           name="fee_percentage_cashout"
                                           value="<?= $seller['fee_percentage_cashout'] * 100 ?>"
                                           step="0.01"
                                           min="0"
                                           max="15"
                                           class="w-full px-4 py-2.5 pr-8"
                                           required>
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">%</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Atual: <?= number_format($seller['fee_percentage_cashout'] * 100, 2) ?>%</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    Taxa Fixa (R$)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">R$</span>
                                    <input type="number"
                                           name="fee_fixed_cashout"
                                           value="<?= $seller['fee_fixed_cashout'] ?>"
                                           step="0.01"
                                           min="0"
                                           class="w-full px-4 py-2.5 pl-11"
                                           required>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Atual: R$ <?= number_format($seller['fee_fixed_cashout'], 2, ',', '.') ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-slate-700">
                    <div class="text-sm text-slate-400">
                        <i class="fas fa-info-circle mr-1"></i>
                        As taxas são aplicadas automaticamente em todas as transações
                    </div>
                    <button type="submit" class="btn-primary px-6 py-2.5 rounded-lg font-medium">
                        <i class="fas fa-save mr-2"></i>Salvar Taxas
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Transactions -->
        <div class="card p-6">
            <h3 class="text-xl font-bold text-white mb-6 flex items-center justify-between">
                <span>
                    <i class="fas fa-history text-purple-500 mr-2"></i>
                    Transações Recentes
                </span>
                <a href="/admin/transactions?seller_id=<?= $seller['id'] ?>" class="text-blue-400 hover:text-blue-300 text-sm">
                    Ver todas <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </h3>
            <div class="space-y-3">
                <?php if (empty($recentTransactions)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-receipt text-5xl text-slate-600 mb-3"></i>
                        <p class="text-slate-400">Nenhuma transação ainda</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($recentTransactions, 0, 10) as $tx): ?>
                    <div class="p-4 bg-slate-800 bg-opacity-50 rounded-lg border border-slate-700 hover:border-blue-500 transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-white font-bold text-lg">R$ <?= number_format($tx['amount'], 2, ',', '.') ?></p>
                                <p class="text-sm text-slate-400"><?= $tx['transaction_id'] ?></p>
                            </div>
                            <div class="text-right">
                                <?php
                                $badgeClass = 'badge-info';
                                if (in_array($tx['status'] ?? '', ['paid', 'approved', 'completed', 'COMPLETED'])) {
                                    $badgeClass = 'badge-success';
                                } elseif (in_array($tx['status'] ?? '', ['waiting_payment', 'pending', 'PENDING_QUEUE'])) {
                                    $badgeClass = 'badge-warning';
                                } elseif (in_array($tx['status'] ?? '', ['failed', 'cancelled', 'refused'])) {
                                    $badgeClass = 'badge-danger';
                                }
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= ucfirst($tx['status'] ?? 'Unknown') ?></span>
                                <p class="text-xs text-slate-500 mt-1"><?= date('d/m H:i', strtotime($tx['created_at'])) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column - 1/3 width -->
    <div class="space-y-6">
        <!-- API Credentials -->
        <?php if ($seller['api_key']): ?>
        <div class="card p-6">
            <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                <i class="fas fa-key text-blue-500 mr-2"></i>
                Credenciais API
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-slate-400">API Key</label>
                    <div class="mt-2 flex items-center space-x-2">
                        <code class="flex-1 px-3 py-2 bg-slate-900 text-slate-300 rounded text-xs font-mono break-all"><?= htmlspecialchars($seller['api_key']) ?></code>
                        <button onclick="copyToClipboard('<?= $seller['api_key'] ?>', this)" class="px-3 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded transition">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Limits -->
        <div class="card p-6">
            <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                <i class="fas fa-chart-line text-green-500 mr-2"></i>
                Limites
            </h3>
            <div class="space-y-4">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-slate-400">Limite Diário</span>
                        <span class="text-sm font-bold text-white">R$ <?= number_format($seller['daily_limit'], 0, ',', '.') ?></span>
                    </div>
                    <div class="w-full bg-slate-700 rounded-full h-2">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full" style="width: <?= min(100, ($seller['daily_used'] / $seller['daily_limit']) * 100) ?>%"></div>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Utilizado: R$ <?= number_format($seller['daily_used'], 2, ',', '.') ?></p>
                </div>
            </div>
        </div>

        <!-- Documents -->
        <div class="card p-6">
            <h3 class="text-lg font-bold text-white mb-4 flex items-center justify-between">
                <span>
                    <i class="fas fa-file-alt text-purple-500 mr-2"></i>
                    Documentos
                </span>
                <span class="badge badge-<?= $seller['document_status'] === 'approved' ? 'success' : 'warning' ?>">
                    <?= ucfirst($seller['document_status']) ?>
                </span>
            </h3>
            <div class="space-y-3">
                <?php if (empty($documents)): ?>
                    <p class="text-slate-400 text-sm">Nenhum documento enviado</p>
                <?php else: ?>
                    <?php foreach ($documents as $doc): ?>
                    <div class="p-3 bg-slate-800 bg-opacity-50 rounded-lg border border-slate-700">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm text-white font-medium"><?= ucfirst(str_replace('_', ' ', $doc['document_type'])) ?></p>
                                <p class="text-xs text-slate-500"><?= date('d/m/Y', strtotime($doc['created_at'])) ?></p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <?php
                                $docBadgeClass = match($doc['status']) {
                                    'approved' => 'badge-success',
                                    'rejected' => 'badge-danger',
                                    'under_review' => 'badge-warning',
                                    default => 'badge-info'
                                };
                                ?>
                                <span class="badge <?= $docBadgeClass ?> text-xs"><?= ucfirst($doc['status']) ?></span>
                                <a href="/admin/documents/view/<?= $doc['id'] ?>" class="text-blue-400 hover:text-blue-300">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50">
    <div class="card p-8 max-w-md w-full mx-4">
        <h3 class="text-2xl font-bold text-white mb-4">Rejeitar Seller</h3>
        <form method="POST" action="/admin/sellers/<?= $seller['id'] ?>/reject">
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-300 mb-2">Motivo da Rejeição</label>
                <textarea name="reason" rows="4" class="w-full px-4 py-2.5" required placeholder="Digite o motivo da rejeição..."></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                    <i class="fas fa-times mr-2"></i>Rejeitar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectModal').classList.add('flex');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectModal').classList.remove('flex');
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
