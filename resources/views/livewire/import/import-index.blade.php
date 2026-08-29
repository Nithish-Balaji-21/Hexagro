<div wire:init="$dispatch('open-import', { kind: 'workbook' })">
    <x-hex.page-head
        title="Import Data"
        subtitle="Upload the Hexagro Excel workbook exported from Zoho Books"
    />
    <p class="hint">The import dialog opens automatically. If it does not appear, use Import on the Debit or Credit pages or refresh this page.</p>
</div>
