<div>
    <x-hex.page-head
        title="Import Data"
        subtitle="Upload the Hexagro Excel workbook exported from Zoho Books"
    />

    <div class="card card-pad mb-4">
        <h3 class="text-sm font-semibold mb-2">Download import templates</h3>
        <p class="hint mb-3">Official templates match the column layout and validation rules used by the importer.</p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('import.template', 'debit') }}" class="hex-btn hex-btn-sm">Debit template</a>
            <a href="{{ route('import.template', 'credit') }}" class="hex-btn hex-btn-sm">Credit template</a>
            <a href="{{ route('import.template', 'outstanding') }}" class="hex-btn hex-btn-sm">Outstanding template</a>
        </div>
    </div>

    <div wire:init="$dispatch('open-import', { kind: 'workbook' })">
        <p class="hint">The import dialog opens automatically. If it does not appear, use Import on the Debit or Credit pages or refresh this page.</p>
    </div>
</div>
