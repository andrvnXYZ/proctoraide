<?php

namespace App\Http\Controllers\Proctor\Concerns;

use App\Models\Examination;
use Illuminate\Http\Request;

trait AuthorizesExamination
{
    /**
     * Abort unless the authenticated proctor owns the examination session.
     */
    protected function authorizeExamination(Request $request, Examination $examination): void
    {
        abort_unless(
            (string) $examination->proctor_id === (string) $request->user()?->getKey(),
            403,
            'You are not authorized to manage this examination.',
        );
    }
}
