<?php
$pageTitle = 'Detalhes do Seller';
require_once __DIR__ . '/../../models/SellerDocument.php';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <a href="/admin/sellers" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                <i class="fas fa-arrow-left mr-1"></i>Voltar
            </a>
            <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($seller['name']) ?></h1>
            <p class="text-gray-600 mt-2"><?= htmlspecialchars($seller['email']) ?></p>
        </div>
        <div>
            <span class="px-4 py-2 rounded-lg text-sm font-medium
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
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <p class="text-gray-600 text-sm font-medium">Saldo</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">R$ <?= number_format($seller['balance'], 2, ',', '.') ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <p class="text-gray-600 text-sm font-medium">Total Recebido</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">R$ <?= number_format($cashinStats['total'] ?? 0, 2, ',', '.') ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <p class="text-gray-600 text-sm font-medium">Total Sacado</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">R$ <?= number_format($cashoutStats['total'] ?? 0, 2, ',', '.') ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Informações</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">ID</label>
                        <p class="text-gray-900 mt-1">#<?= $seller['id'] ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Tipo de Pessoa</label>
                        <p class="text-gray-900 mt-1"><?= $seller['person_type'] === 'individual' ? 'Pessoa Física' : 'Pessoa Jurídica' ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Documento</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($seller['document']) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Telefone</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($seller['phone'] ?? 'Não informado') ?></p>
                    </div>
                    <?php if ($seller['person_type'] === 'business'): ?>
                    <div class="col-span-2">
                        <label class="text-sm font-medium text-gray-600">Razão Social</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($seller['company_name'] ?? 'Não informado') ?></p>
                    </div>
                    <?php if ($seller['trading_name']): ?>
                    <div class="col-span-2">
                        <label class="text-sm font-medium text-gray-600">Nome Fantasia</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($seller['trading_name']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Cadastro</label>
                        <p class="text-gray-900 mt-1"><?= date('d/m/Y H:i', strtotime($seller['created_at'])) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Última Atualização</label>
                        <p class="text-gray-900 mt-1"><?= date('d/m/Y H:i', strtotime($seller['updated_at'])) ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Documentos Obrigatórios</h2>

                <?php
                $documentModel = new SellerDocument();
                $requiredDocs = $documentModel->getRequiredDocumentTypes($seller['person_type']);
                $documentsByType = [];
                foreach ($documents as $doc) {
                    $documentsByType[$doc['document_type']] = $doc;
                }

                $docLabels = [
                    'rg_front' => 'RG Frente',
                    'rg_back' => 'RG Verso',
                    'cpf' => 'CPF',
                    'selfie' => 'Selfie com Documento',
                    'proof_address' => 'Comprovante de Endereço',
                    'social_contract' => 'Contrato Social',
                    'cnpj' => 'Cartão CNPJ',
                    'partner_docs' => 'Documentos dos Sócios'
                ];

                $allApproved = true;
                $hasPending = false;
                foreach ($requiredDocs as $docType) {
                    if (!isset($documentsByType[$docType]) || $documentsByType[$docType]['status'] !== 'approved') {
                        $allApproved = false;
                    }
                    if (isset($documentsByType[$docType]) && $documentsByType[$docType]['status'] === 'pending') {
                        $hasPending = true;
                    }
                }
                ?>

                <div class="space-y-3 mb-6">
                    <?php foreach ($requiredDocs as $docType): ?>
                    <?php
                        $hasDoc = isset($documentsByType[$docType]);
                        $doc = $hasDoc ? $documentsByType[$docType] : null;
                        $status = $hasDoc ? $doc['status'] : 'missing';
                    ?>
                    <div class="flex items-center justify-between p-3 rounded-lg border
                        <?php
                        echo match($status) {
                            'approved' => 'border-green-200 bg-green-50',
                            'pending' => 'border-yellow-200 bg-yellow-50',
                            'under_review' => 'border-blue-200 bg-blue-50',
                            'rejected' => 'border-red-200 bg-red-50',
                            'missing' => 'border-gray-200 bg-gray-50'
                        };
                        ?>">
                        <div class="flex items-center space-x-3">
                            <i class="fas
                                <?php
                                echo match($status) {
                                    'approved' => 'fa-check-circle text-green-600',
                                    'pending' => 'fa-clock text-yellow-600',
                                    'under_review' => 'fa-eye text-blue-600',
                                    'rejected' => 'fa-times-circle text-red-600',
                                    'missing' => 'fa-exclamation-circle text-gray-400'
                                };
                                ?>"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-900"><?= $docLabels[$docType] ?? ucfirst(str_replace('_', ' ', $docType)) ?></p>
                                <?php if ($hasDoc): ?>
                                    <p class="text-xs text-gray-600"><?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?></p>
                                <?php else: ?>
                                    <p class="text-xs text-gray-500">Não enviado</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php if ($hasDoc): ?>
                                <button onclick="viewDocument(<?= $doc['id'] ?>)" class="px-3 py-1 text-xs font-medium text-blue-600 hover:bg-blue-100 rounded-lg transition">
                                    <i class="fas fa-eye mr-1"></i>Ver
                                </button>
                                <?php if ($status === 'pending' || $status === 'under_review'): ?>
                                <button onclick="approveDocument(<?= $doc['id'] ?>)" class="px-3 py-1 text-xs font-medium text-green-600 hover:bg-green-100 rounded-lg transition">
                                    <i class="fas fa-check mr-1"></i>Aprovar
                                </button>
                                <button onclick="rejectDocument(<?= $doc['id'] ?>)" class="px-3 py-1 text-xs font-medium text-red-600 hover:bg-red-100 rounded-lg transition">
                                    <i class="fas fa-times mr-1"></i>Rejeitar
                                </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($allApproved): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 mr-3"></i>
                        <div>
                            <p class="text-sm font-medium text-green-900">Todos os documentos aprovados</p>
                            <p class="text-xs text-green-700 mt-1">Este seller está pronto para ser aprovado</p>
                        </div>
                    </div>
                </div>
                <?php elseif ($hasPending): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-clock text-yellow-600 mr-3"></i>
                        <div>
                            <p class="text-sm font-medium text-yellow-900">Documentos pendentes de análise</p>
                            <p class="text-xs text-yellow-700 mt-1">Analise todos os documentos antes de aprovar o seller</p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle text-gray-600 mr-3"></i>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Aguardando documentos</p>
                            <p class="text-xs text-gray-700 mt-1">O seller ainda precisa enviar alguns documentos</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($recentTransactions)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Transações Recentes</h2>
                <div class="space-y-3">
                    <?php foreach ($recentTransactions as $tx): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900">R$ <?= number_format($tx['amount'], 2, ',', '.') ?></p>
                            <p class="text-xs text-gray-600"><?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?></p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <?= ucfirst($tx['status']) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="space-y-6">
            <?php if ($seller['status'] === 'pending'): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">Ações</h3>
                <div class="space-y-3">
                    <form method="POST" action="/admin/sellers/<?= $seller['id'] ?>/approve" onsubmit="return confirm('Tem certeza que deseja aprovar este seller?')">
                        <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg font-medium hover:bg-green-700 transition">
                            <i class="fas fa-check mr-2"></i>Aprovar Seller
                        </button>
                    </form>
                    <button onclick="showRejectModal()" class="w-full bg-red-600 text-white py-3 rounded-lg font-medium hover:bg-red-700 transition">
                        <i class="fas fa-times mr-2"></i>Rejeitar Seller
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">Taxas</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Cash-in</span>
                        <span class="text-sm font-medium text-gray-900"><?= number_format($seller['fee_percentage_cashin'] * 100, 2) ?>%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Cash-out</span>
                        <span class="text-sm font-medium text-gray-900"><?= number_format($seller['fee_percentage_cashout'] * 100, 2) ?>%</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">Limites</h3>
                <div class="space-y-3">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-600">Limite Diário</span>
                            <span class="text-sm font-medium text-gray-900">R$ <?= number_format($seller['daily_limit'], 2, ',', '.') ?></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <?php $percentage = ($seller['daily_used'] / $seller['daily_limit']) * 100; ?>
                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?= min($percentage, 100) ?>%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Usado: R$ <?= number_format($seller['daily_used'], 2, ',', '.') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Rejeitar Seller</h3>
        <form method="POST" action="/admin/sellers/<?= $seller['id'] ?>/reject">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Motivo da Rejeição</label>
                <textarea name="reason" required rows="4"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                          placeholder="Explique o motivo da rejeição..."></textarea>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" onclick="hideRejectModal()" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-medium hover:bg-gray-300 transition">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 bg-red-600 text-white py-3 rounded-lg font-medium hover:bg-red-700 transition">
                    Rejeitar
                </button>
            </div>
        </form>
    </div>
</div>

<div id="documentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl max-w-5xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 p-4 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900" id="documentModalTitle">Documento</h3>
            <button onclick="hideDocumentModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6" id="documentModalContent">
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-gray-400 text-4xl"></i>
                <p class="text-gray-600 mt-4">Carregando...</p>
            </div>
        </div>
    </div>
</div>

<div id="rejectDocModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Rejeitar Documento</h3>
        <form id="rejectDocForm" method="POST">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Motivo da Rejeição</label>
                <textarea name="reason" required rows="4"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                          placeholder="Ex: Foto desfocada, documento ilegível, etc."></textarea>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" onclick="hideRejectDocModal()" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-medium hover:bg-gray-300 transition">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 bg-red-600 text-white py-3 rounded-lg font-medium hover:bg-red-700 transition">
                    Rejeitar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentDocumentId = null;

const documentData = <?= json_encode(array_values($documentsByType ?? [])) ?>;

function showRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectModal').classList.add('flex');
}

function hideRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectModal').classList.remove('flex');
}

function viewDocument(documentId) {
    currentDocumentId = documentId;
    const modal = document.getElementById('documentModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    fetch(`/admin/documents/view/${documentId}`)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const content = doc.querySelector('.max-w-4xl');

            if (content) {
                const title = doc.querySelector('h1');
                document.getElementById('documentModalTitle').innerHTML = title ? title.textContent : 'Documento';
                document.getElementById('documentModalContent').innerHTML = content.innerHTML;
            }
        })
        .catch(error => {
            console.error('Error loading document:', error);
            document.getElementById('documentModalContent').innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-exclamation-triangle text-red-400 text-4xl"></i>
                    <p class="text-red-600 mt-4">Erro ao carregar documento</p>
                </div>
            `;
        });
}

function hideDocumentModal() {
    document.getElementById('documentModal').classList.add('hidden');
    document.getElementById('documentModal').classList.remove('flex');
}

function approveDocument(documentId) {
    if (confirm('Tem certeza que deseja aprovar este documento?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/documents/${documentId}/approve`;
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectDocument(documentId) {
    currentDocumentId = documentId;
    document.getElementById('rejectDocForm').action = `/admin/documents/${documentId}/reject`;
    document.getElementById('rejectDocModal').classList.remove('hidden');
    document.getElementById('rejectDocModal').classList.add('flex');
}

function hideRejectDocModal() {
    document.getElementById('rejectDocModal').classList.add('hidden');
    document.getElementById('rejectDocModal').classList.remove('flex');
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
