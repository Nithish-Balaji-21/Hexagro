<?php

namespace App\Services\Import;

readonly class ImportSheetResult
{
    /**
     * @param  list<string>  $messages
     */
    public function __construct(
        public string $sheet,
        public int $imported = 0,
        public int $created = 0,
        public int $skipped = 0,
        public int $errors = 0,
        public array $messages = [],
    ) {}

    public function withMessage(string $message): self
    {
        return new self(
            sheet: $this->sheet,
            imported: $this->imported,
            created: $this->created,
            skipped: $this->skipped,
            errors: $this->errors,
            messages: [...$this->messages, $message],
        );
    }
}
