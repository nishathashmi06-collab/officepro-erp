<?php

namespace App\Notifications;

use App\Models\Document;

class DocumentExpiring extends AppNotification
{
    public function __construct(public Document $document)
    {
    }

    public function type(): string
    {
        return 'document_expiring';
    }

    public function title(): string
    {
        return $this->document->expiryState() === 'expired' ? 'Document expired' : 'Document expiring soon';
    }

    public function message(): string
    {
        return sprintf(
            '"%s"%s expires on %s.',
            $this->document->title,
            $this->document->employee ? ' ('.$this->document->employee->full_name.')' : '',
            $this->document->expiry_date->format('M d, Y')
        );
    }

    public function url(): string
    {
        return route('documents.index', ['expiry' => 'expiring']);
    }

    public function icon(): string
    {
        return 'bi-file-earmark-excel';
    }

    public function color(): string
    {
        return 'warning';
    }
}
