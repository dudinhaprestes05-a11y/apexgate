<?php
$pageTitle = 'Sellers';
require_once __DIR__ . '/../layouts/header.php';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-6 md:mb-8">
        <h1 class="text-2xl md:text-3xl font-bold text-slate-100">Sellers</h1>
        <p class="text-sm md:text-base text-slate-400 mt-2">Gerencie todos os sellers do sistema</p>
    </div>

    <div class="card p-4 md:p-6 mb-4 md:mb-6">
        <form method="GET" action="/admin/sellers">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Buscar</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                           placeholder="Nome, email ou documento..."
                           class="w-full px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 text-slate-100 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Status</label>
                    <select name="status" class="w-full px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 text-slate-100 text-sm">
                        <option value="">Todos</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pendente</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                        <option value="blocked" <?= $status === 'blocked' ? 'selected' : '' ?>>Bloqueado</option>
                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejeitado</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="w-full md:w-auto px-4 md:px-6 py-2 btn-primary text-white rounded-lg transition text-sm">
                    <i class="fas fa-search mr-2"></i>Buscar
                </button>
            </div>
        </form>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
        <table class="min-w-full table-dark">
            <thead>
                <tr>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase whitespace-nowrap">ID</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase whitespace-nowrap">Nome</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase whitespace-nowrap">Email</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase whitespace-nowrap">Documento</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase whitespace-nowrap">Status</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase whitespace-nowrap">Saldo</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase whitespace-nowrap hidden lg:table-cell">Cadastro</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase whitespace-nowrap">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sellers)): ?>
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                        <i class="fas fa-users-slash text-4xl mb-3 block"></i>
                        Nenhum seller encontrado
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($sellers as $seller): ?>
                <tr>
                    <td class="px-3 md:px-6 py-3 md:py-4 whitespace-nowrap text-xs md:text-sm font-medium text-slate-100">
                        #<?= $seller['id'] ?>
                    </td>
                    <td class="px-3 md:px-6 py-3 md:py-4 whitespace-nowrap">
                        <div class="text-xs md:text-sm font-medium text-slate-100"><?= htmlspecialchars($seller['name']) ?></div>
                        <?php if ($seller['person_type'] === 'business' && $seller['company_name']): ?>
                        <div class="text-xs text-slate-400"><?= htmlspecialchars($seller['company_name']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 md:px-6 py-3 md:py-4 whitespace-nowrap text-xs md:text-sm text-slate-300">
                        <?= htmlspecialchars($seller['email']) ?>
                    </td>
                    <td class="px-3 md:px-6 py-3 md:py-4 whitespace-nowrap text-xs md:text-sm font-mono text-slate-300">
                        <span class="hidden md:inline"><?= htmlspecialchars($seller['document']) ?></span>
                        <span class="md:hidden"><?= htmlspecialchars(substr($seller['document'], 0, 8)) ?>...</span>
                    </td>
                    <td class="px-3 md:px-6 py-3 md:py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full
                            <?php
                            echo match($seller['status']) {
                                'active' => 'bg-green-100 text-green-800',
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'inactive' => 'bg-gray-100 text-gray-800',
                                'blocked' => 'bg-red-100 text-red-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            ?>">
                            <?= ucfirst($seller['status']) ?>
                        </span>
                    </td>
                    <td class="px-3 md:px-6 py-3 md:py-4 whitespace-nowrap text-xs md:text-sm font-medium text-slate-100">
                        R$ <?= number_format($seller['balance'], 2, ',', '.') ?>
                    </td>
                    <td class="px-3 md:px-6 py-3 md:py-4 whitespace-nowrap text-xs md:text-sm text-slate-300 hidden lg:table-cell">
                        <?= date('d/m/Y', strtotime($seller['created_at'])) ?>
                    </td>
                    <td class="px-3 md:px-6 py-3 md:py-4 whitespace-nowrap text-xs md:text-sm">
                        <a href="/admin/sellers/view/<?= $seller['id'] ?>" class="text-blue-400 hover:text-blue-300 font-medium">
                            <span class="hidden md:inline">Ver detalhes</span>
                            <i class="fas fa-eye md:hidden"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
