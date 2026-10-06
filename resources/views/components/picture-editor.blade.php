@props(['pictures' => [], 'name' => 'pictures', 'max' => 10, 'preview' => null])

<div class="picture-editor" data-name="{{ $name }}" data-max="{{ $max }}" @if($preview) data-preview="{{ $preview }}" @endif>
    <div class="picture-grid" role="list">
        @foreach($pictures as $url)
            <div class="picture-item" role="listitem">
                <span class="picture-order"></span>
                <span class="picture-main">Principal</span>
                <img src="{{ $url }}" alt="" loading="lazy" draggable="false" onerror="this.closest('.picture-item').classList.add('is-broken')">
                <input type="hidden" name="{{ $name }}[]" value="{{ $url }}">
                <div class="picture-actions">
                    <button type="button" data-action="prev" title="Mover antes" aria-label="Mover antes"><i class="bi bi-chevron-left"></i></button>
                    <button type="button" data-action="next" title="Mover después" aria-label="Mover después"><i class="bi bi-chevron-right"></i></button>
                    <button type="button" data-action="remove" class="is-danger" title="Quitar" aria-label="Quitar imagen"><i class="bi bi-trash3"></i></button>
                </div>
            </div>
        @endforeach
    </div>

    <p class="picture-empty">No hay imágenes. Agrega al menos una URL.</p>

    <div class="d-flex gap-2 mt-2">
        <input type="url" class="form-control picture-new" placeholder="https://... (pega la URL de una imagen)">
        <button type="button" class="btn-ghost text-nowrap" data-action="add"><i class="bi bi-plus-lg me-1"></i>Agregar</button>
    </div>
    <small class="picture-help">
        Arrastra o usa las flechas para ordenar. La primera es la portada en Mercado Libre. Máximo <span>{{ $max }}</span> imágenes.
    </small>

    <template class="picture-template">
        <div class="picture-item" role="listitem">
            <span class="picture-order"></span>
            <span class="picture-main">Principal</span>
            <img alt="" loading="lazy" draggable="false" onerror="this.closest('.picture-item').classList.add('is-broken')">
            <input type="hidden" name="{{ $name }}[]">
            <div class="picture-actions">
                <button type="button" data-action="prev" title="Mover antes" aria-label="Mover antes"><i class="bi bi-chevron-left"></i></button>
                <button type="button" data-action="next" title="Mover después" aria-label="Mover después"><i class="bi bi-chevron-right"></i></button>
                <button type="button" data-action="remove" class="is-danger" title="Quitar" aria-label="Quitar imagen"><i class="bi bi-trash3"></i></button>
            </div>
        </div>
    </template>
</div>

@once
@push('styles')
<style>
    .picture-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(118px, 1fr)); gap: 10px; }
    .picture-item {
        position: relative;
        border: 1px solid var(--border-card);
        border-radius: 12px;
        background: var(--overlay-soft);
        overflow: hidden;
        cursor: grab;
        transition: border-color .15s, opacity .15s, transform .15s;
    }
    .picture-item:first-child { border-color: var(--neon-blue); box-shadow: 0 0 0 1px var(--neon-blue) inset; }
    .picture-item.is-dragging { opacity: .4; }
    .picture-item.is-drop-target { transform: scale(1.03); border-color: var(--neon-purple); }
    .picture-item img { width: 100%; height: 104px; object-fit: contain; display: block; background: #fff; touch-action: none; user-select: none; }
    .picture-grid.is-sorting, .picture-grid.is-sorting * { cursor: grabbing; user-select: none; }
    .picture-item.is-broken img { visibility: hidden; }
    .picture-item.is-broken::after {
        content: 'No carga';
        position: absolute; top: 0; left: 0; right: 0; height: 104px;
        display: flex; align-items: center; justify-content: center; gap: 4px;
        color: var(--danger-fg); font-size: 12px; font-weight: 600;
        background: repeating-linear-gradient(45deg, var(--overlay-soft), var(--overlay-soft) 6px, transparent 6px, transparent 12px);
        pointer-events: none;
    }
    .picture-item.is-broken { border-color: var(--danger-fg); }
    .picture-order {
        position: absolute; top: 6px; left: 6px;
        min-width: 22px; height: 22px; padding: 0 6px;
        border-radius: 11px; background: rgba(15,23,42,.75); color: #fff;
        font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center;
    }
    .picture-main {
        position: absolute; top: 6px; right: 6px; display: none;
        padding: 2px 8px; border-radius: 10px;
        background: var(--neon-blue); color: #fff; font-size: 10px; font-weight: 700;
    }
    .picture-item:first-child .picture-main { display: block; }
    .picture-actions { display: flex; border-top: 1px solid var(--border-card); }
    .picture-actions button {
        flex: 1; border: 0; background: transparent; color: var(--text-dim);
        padding: 6px 0; font-size: 13px; cursor: pointer;
    }
    .picture-actions button:hover { background: var(--hover-bg); color: var(--neon-blue); }
    .picture-actions button.is-danger:hover { color: var(--danger-fg); }
    .picture-actions button:disabled { opacity: .3; cursor: default; background: transparent; }
    .picture-empty { display: none; color: var(--danger-fg); font-size: 13px; margin: 8px 0 0; }
    .picture-editor.is-empty .picture-empty { display: block; }
    .picture-help { color: var(--text-muted); font-size: 11px; display: block; margin-top: 6px; }
    .picture-editor.is-full .picture-help span { color: var(--danger-fg); font-weight: 700; }
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('.picture-editor').forEach((editor) => {
    const grid     = editor.querySelector('.picture-grid');
    const input    = editor.querySelector('.picture-new');
    const template = editor.querySelector('.picture-template');
    const max      = parseInt(editor.dataset.max, 10);
    const preview  = editor.dataset.preview ? document.querySelector(editor.dataset.preview) : null;
    const fallback = preview ? preview.getAttribute('src') : null;
    let dragged    = null;

    const items = () => [...grid.querySelectorAll('.picture-item')];

    const refresh = () => {
        const list = items();
        list.forEach((item, index) => {
            item.querySelector('.picture-order').textContent = index + 1;
            item.querySelector('[data-action="prev"]').disabled = index === 0;
            item.querySelector('[data-action="next"]').disabled = index === list.length - 1;
        });
        editor.classList.toggle('is-empty', list.length === 0);
        editor.classList.toggle('is-full', list.length >= max);
        editor.querySelector('[data-action="add"]').disabled = list.length >= max;
        if (preview) {
            preview.src = list.length ? list[0].querySelector('input').value : fallback;
        }
    };

    const addPicture = () => {
        const url = input.value.trim();
        if (!url) return;
        if (!/^https?:\/\//i.test(url)) { input.setCustomValidity('Ingresa una URL que empiece por http:// o https://'); input.reportValidity(); return; }
        if (items().some((item) => item.querySelector('input').value === url)) { input.setCustomValidity('Esa imagen ya está en la lista'); input.reportValidity(); return; }
        if (items().length >= max) return;
        const node = template.content.firstElementChild.cloneNode(true);
        node.querySelector('img').src = url;
        node.querySelector('input').value = url;
        grid.appendChild(node);
        input.value = '';
        refresh();
    };

    input.addEventListener('input', () => input.setCustomValidity(''));
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') { event.preventDefault(); addPicture(); }
    });

    editor.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-action]');
        if (!button) return;
        const item = button.closest('.picture-item');
        switch (button.dataset.action) {
            case 'add':    addPicture(); return;
            case 'remove': item.remove(); break;
            case 'prev':   item.previousElementSibling && grid.insertBefore(item, item.previousElementSibling); break;
            case 'next':   item.nextElementSibling && grid.insertBefore(item.nextElementSibling, item); break;
        }
        refresh();
    });

    const itemAt = (x, y) => {
        const element = document.elementFromPoint(x, y);
        const item    = element ? element.closest('.picture-item') : null;
        return item && grid.contains(item) ? item : null;
    };

    grid.addEventListener('pointerdown', (event) => {
        const item = event.target.closest('.picture-item');
        if (!item || event.target.closest('button') || event.button > 0) return;
        dragged = { item, startX: event.clientX, startY: event.clientY, active: false };
        item.setPointerCapture(event.pointerId);
    });

    grid.addEventListener('pointermove', (event) => {
        if (!dragged) return;
        if (!dragged.active) {
            if (Math.hypot(event.clientX - dragged.startX, event.clientY - dragged.startY) < 6) return;
            dragged.active = true;
            dragged.item.classList.add('is-dragging');
            grid.classList.add('is-sorting');
        }
        dragged.item.style.pointerEvents = 'none';
        const target = itemAt(event.clientX, event.clientY);
        dragged.item.style.pointerEvents = '';
        items().forEach((item) => item.classList.toggle('is-drop-target', item === target));
        if (!target || target === dragged.item) return;
        const rect   = target.getBoundingClientRect();
        const before = event.clientX < rect.left + rect.width / 2;
        grid.insertBefore(dragged.item, before ? target : target.nextElementSibling);
    });

    const endDrag = () => {
        if (!dragged) return;
        dragged.item.classList.remove('is-dragging');
        grid.classList.remove('is-sorting');
        items().forEach((item) => item.classList.remove('is-drop-target'));
        dragged = null;
        refresh();
    };

    grid.addEventListener('pointerup', endDrag);
    grid.addEventListener('pointercancel', endDrag);

    editor.closest('form')?.addEventListener('submit', (event) => {
        if (items().length === 0) {
            event.preventDefault();
            editor.classList.add('is-empty');
            editor.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    refresh();
});
</script>
@endpush
@endonce
