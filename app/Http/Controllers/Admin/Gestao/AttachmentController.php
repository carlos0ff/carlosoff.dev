<?php

namespace App\Http\Controllers\Admin\Gestao;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Charge;
use App\Models\Client;
use App\Models\ClientProject;
use App\Models\Contract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /** @var array<string, class-string> */
    public const TYPES = ['client' => Client::class, 'project' => ClientProject::class, 'contract' => Contract::class, 'charge' => Charge::class];

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'attachable_type' => ['required', 'in:'.implode(',', array_keys(self::TYPES))],
            'attachable_id' => ['required', 'integer'],
            'label' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,png,jpg,jpeg,doc,docx,xls,xlsx,zip,txt'],
            'visible_to_client' => ['boolean'],
        ]);

        $model = self::TYPES[$data['attachable_type']]::findOrFail($data['attachable_id']);
        $file = $request->file('file');
        $path = $file->store("attachments/{$data['attachable_type']}/{$model->getKey()}", 'local');

        $attachment = $model->attachments()->create([
            'label' => $data['label'],
            'path' => $path,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'sha256' => hash_file('sha256', $file->getRealPath()),
            'visible_to_client' => $request->boolean('visible_to_client'),
            'uploaded_by' => $request->user()->id,
        ]);

        AuditLog::record(AuditAction::Create, 'Anexo', $attachment->id, $attachment->label, $data['attachable_type'], array_filter([
            'client_id' => $model instanceof Client ? $model->id : ($model->client_id ?? null),
            'client_project_id' => $model instanceof ClientProject ? $model->id : ($model->client_project_id ?? null),
        ]));

        return back()->with('status', 'Anexo enviado.');
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }
}
