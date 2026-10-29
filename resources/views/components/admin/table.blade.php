{{-- Tabela responsiva: no mobile cada linha vira um cartão (ver `.admin-table` em app.css; use data-label nas <td>). --}}
<div class="rounded-xl border border-border bg-card">
    <table {{ $attributes->class('admin-table w-full text-left text-sm') }}>
        {{ $slot }}
    </table>
</div>
