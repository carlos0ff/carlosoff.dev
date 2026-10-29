<x-admin.card title="Documentos associados">
    <ul class="mb-4 space-y-2 text-sm">
        @forelse ($attachments as $attachment)
            <li class="flex items-center justify-between gap-2">
                <a href="{{ route('admin.gestao.attachments.download', $attachment) }}" class="min-w-0 truncate hover:text-primary-hover">{{ $attachment->label }}</a>
                @if ($attachment->visible_to_client)<x-admin.badge tone="info">visível ao cliente</x-admin.badge>@else<x-admin.badge>interno</x-admin.badge>@endif
            </li>
        @empty
            <li class="text-foreground-muted">Nenhum arquivo.</li>
        @endforelse
    </ul>
    <form method="POST" action="{{ route('admin.gestao.attachments.store') }}" enctype="multipart/form-data" class="space-y-3 border-t border-border-subtle pt-4">
        @csrf
        <input type="hidden" name="attachable_type" value="{{ $type }}">
        <input type="hidden" name="attachable_id" value="{{ $model->id }}">
        <x-admin.input name="label" label="Descrição" required />
        <x-admin.field name="file" label="Arquivo" required><input id="f-file" type="file" name="file" required class="block w-full text-sm"></x-admin.field>
        <x-admin.toggle name="visible_to_client" label="Visível no portal do cliente" />
        <x-admin.button type="submit" size="sm">Enviar</x-admin.button>
    </form>
</x-admin.card>
