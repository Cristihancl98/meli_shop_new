@props(['error'])

<div class="glass-card p-3 mt-2" style="border:1px solid var(--danger-fg);">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2" style="font-size:13px;">
        <strong style="color:var(--text-strong);"><i class="bi bi-bug me-1"></i>Detalle de Mercado Libre</strong>
        @if($error['status'])<span class="badge text-bg-danger">HTTP {{ $error['status'] }}</span>@endif
        @if($error['error'])<code>{{ $error['error'] }}</code>@endif
        @if($error['action'])<span style="color:var(--text-muted);">al {{ $error['action'] }}</span>@endif
    </div>

    @if($error['message'])
        <p class="mb-2" style="color:var(--text-dim);font-size:13px;">{{ $error['message'] }}</p>
    @endif

    @if($error['causes'])
        <ul class="list-unstyled mb-2" style="font-size:13px;">
            @foreach($error['causes'] as $cause)
                <li class="mb-2 d-flex gap-2">
                    <span class="badge {{ $cause['type'] === 'warning' ? 'text-bg-warning' : 'text-bg-danger' }} align-self-start">{{ $cause['type'] }}</span>
                    <div>
                        <div style="color:var(--text-strong);">{{ $cause['message'] }}</div>
                        @if($cause['code'])<code style="font-size:11px;">{{ $cause['code'] }}</code>@endif
                        @if($cause['references'])<small class="ms-2" style="color:var(--text-muted);">Campos: {{ $cause['references'] }}</small>@endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    <details>
        <summary style="color:var(--neon-blue);font-size:12px;cursor:pointer;">Ver respuesta completa (JSON)</summary>
        <div class="position-relative mt-2">
            <button type="button" class="btn-ghost position-absolute top-0 end-0 m-1" style="font-size:11px;padding:2px 8px;"
                    onclick="navigator.clipboard.writeText(this.nextElementSibling.textContent).then(() => this.textContent = 'Copiado')">Copiar</button>
            <pre class="mb-0 p-3 rounded-2" style="background:var(--hover-bg);color:var(--text-dim);font-size:12px;max-height:360px;overflow:auto;white-space:pre-wrap;">{{ $error['raw'] }}</pre>
        </div>
    </details>
</div>
