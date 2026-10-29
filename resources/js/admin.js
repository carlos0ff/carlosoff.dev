/**
 * Comportamentos do painel administrativo (sem dependências):
 * sidebar responsiva, confirmação de exclusão, toasts, estado de carregamento,
 * pré-visualização de imagens, listas repetíveis e slug automático.
 */

/* ── Sidebar (mobile) ─────────────────────────────────────────────────── */
(function initSidebar() {
    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.querySelector('[data-sidebar-overlay]');
    if (!sidebar) return;

    const setOpen = (open) => {
        sidebar.classList.toggle('-translate-x-full', !open);
        overlay?.classList.toggle('hidden', !open);
        document.body.classList.toggle('overflow-hidden', open && window.innerWidth < 1024);
    };

    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.addEventListener('click', () => setOpen(sidebar.classList.contains('-translate-x-full')));
    });
    overlay?.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (event) => event.key === 'Escape' && setOpen(false));
})();

/* ── Confirmação (<form data-confirm="mensagem" data-confirm-title data-confirm-button>) ── */
(function initConfirm() {
    const dialog = document.getElementById('confirm-dialog');
    if (!dialog) return;

    const title = dialog.querySelector('#confirm-title');
    const message = dialog.querySelector('#confirm-message');
    const accept = dialog.querySelector('[data-confirm-accept]');
    let pending = null;

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm') || form.dataset.confirmed === '1') return;

        event.preventDefault();
        event.stopImmediatePropagation();
        pending = form;
        title.textContent = form.dataset.confirmTitle || 'Excluir item?';
        message.textContent = form.dataset.confirm || 'Esta ação não poderá ser desfeita.';
        accept.textContent = form.dataset.confirmButton || 'Excluir';
        dialog.showModal();
    }, true);

    dialog.querySelector('[data-confirm-cancel]').addEventListener('click', () => {
        pending = null;
        dialog.close();
    });

    accept.addEventListener('click', () => {
        const form = pending;
        pending = null;
        dialog.close();
        if (form) {
            form.dataset.confirmed = '1';
            form.requestSubmit();
        }
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
})();

/* ── Toasts ───────────────────────────────────────────────────────────── */
document.querySelectorAll('[data-toast]').forEach((toast) => {
    const dismiss = () => toast.remove();
    toast.querySelector('[data-toast-close]')?.addEventListener('click', dismiss);
    // Erros ficam na tela até serem fechados; sucessos somem sozinhos.
    if (toast.getAttribute('role') !== 'alert') setTimeout(dismiss, 6000);
});

/* ── Estado de carregamento ao enviar formulários ─────────────────────── */
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;

    // Depois do envio: desabilitar antes travaria o próprio envio em alguns navegadores.
    setTimeout(() => {
        form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
            button.setAttribute('data-loading', '');
            button.disabled = true;
        });
    }, 0);
});

// Voltar pelo histórico (bfcache) não pode deixar botões presos em "carregando".
window.addEventListener('pageshow', (event) => {
    if (!event.persisted) return;
    document.querySelectorAll('button[data-loading]').forEach((button) => {
        button.removeAttribute('data-loading');
        button.disabled = false;
    });
});

/* ── Pré-visualização de imagens escolhidas ───────────────────────────── */
document.querySelectorAll('[data-file-input]').forEach((input) => {
    input.addEventListener('change', () => {
        const preview = input.closest('div.flex')?.querySelector('[data-file-preview]');
        const file = input.files?.[0];
        if (!preview) return;

        if (file && file.type.startsWith('image/')) {
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
        } else {
            preview.hidden = true;
        }
    });
});

/* ── Listas repetíveis (desafios do projeto, links do Sobre) ──────────── */
document.querySelectorAll('[data-repeater]').forEach((repeater) => {
    const list = repeater.querySelector('[data-repeater-list]');
    const template = repeater.querySelector('template[data-repeater-template]');
    let next = list.children.length;

    repeater.querySelector('[data-repeater-add]')?.addEventListener('click', () => {
        const html = template.innerHTML.replaceAll('__INDEX__', String(next++));
        list.insertAdjacentHTML('beforeend', html);
        list.lastElementChild?.querySelector('input, textarea')?.focus();
    });

    repeater.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-repeater-remove]');
        if (remove) remove.closest('[data-repeater-item]')?.remove();
    });
});

/* ── Slug automático a partir do título (só enquanto o slug não foi editado à mão) ── */
document.querySelectorAll('[data-slug-source]').forEach((source) => {
    const target = document.querySelector(source.dataset.slugSource);
    if (!target || target.value) return;

    let touched = false;
    target.addEventListener('input', () => (touched = true));
    source.addEventListener('input', () => {
        if (touched) return;
        target.value = source.value
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    });
});

/* ── Menus ⋮ (<details data-dropdown>): fecham ao clicar fora ou com Esc ── */
document.addEventListener('click', (event) => {
    document.querySelectorAll('details[data-dropdown][open]').forEach((menu) => {
        if (!menu.contains(event.target)) menu.removeAttribute('open');
    });
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') document.querySelectorAll('details[data-dropdown][open]').forEach((menu) => menu.removeAttribute('open'));
});
