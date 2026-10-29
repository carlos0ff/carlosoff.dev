<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $document->title }}</title>
    <style>
        @page { margin: 2.2cm 2cm 2.4cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5pt; line-height: 1.5; color: #111; }
        h1 { font-size: 15pt; margin: 0 0 4pt; }
        h2 { font-size: 11.5pt; margin: 16pt 0 4pt; }
        p { margin: 0 0 7pt; text-align: justify; }
        table { width: 100%; border-collapse: collapse; margin: 6pt 0 10pt; }
        td, th { border: 1px solid #999; padding: 4pt 6pt; vertical-align: top; text-align: left; }
        .meta { font-size: 8.5pt; color: #555; border-bottom: 1px solid #ccc; padding-bottom: 6pt; margin-bottom: 14pt; }
        .draft { position: fixed; top: 38%; left: 0; right: 0; text-align: center; font-size: 46pt; color: rgba(200, 0, 0, 0.12); transform: rotate(-30deg); }
        .notice { border: 1px solid #c00; color: #900; padding: 6pt; font-size: 9pt; margin-bottom: 12pt; }
        mark.missing { background: #ffe08a; }
        footer { position: fixed; bottom: -1.6cm; left: 0; right: 0; font-size: 7.5pt; color: #666; text-align: center; }
        .pagenum:before { content: counter(page); }
    </style>
</head>
<body>
    @if ($draft)
        <div class="draft">MINUTA</div>
        <div class="notice">Minuta gerada a partir de modelo ainda não aprovado em revisão jurídica. Não utilizar para assinatura.</div>
    @endif

    <div class="meta">
        {{ $company['name'] }} · Documento {{ $document->uuid }} · versão {{ $document->version }} · gerado em {{ now()->format('d/m/Y H:i') }}
    </div>

    {!! $document->body_html !!}

    <footer>
        {{ $document->title }} — v{{ $document->version }} — página <span class="pagenum"></span>.
        A validade jurídica depende do método de assinatura, da identificação dos signatários e das circunstâncias do documento.
    </footer>
</body>
</html>
