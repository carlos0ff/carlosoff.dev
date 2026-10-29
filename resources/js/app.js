/**
 * ELEMENTOS DOM
 */
const DOM = {
    emptyState: document.getElementById("empty-projects"),
    container: document.getElementById("projects"),
    projectsCount: document.getElementById("projects-count"),
    pagination: document.getElementById("pagination"),
    searchInput: document.getElementById("search-input"),
    progressBar: document.getElementById("progress-bar"),
    progressContainer: document.getElementById("progress-bar-container"),
    year: document.getElementById("current-year"),
    commentForm: document.getElementById("comment-form"),
};


/**
 * SELETOR DE IDIOMA (header)
 * Apenas a UI do dropdown (abrir/fechar, marcar seleção); tradução do
 * conteúdo da página está fora do escopo deste ajuste de layout.
 */
(function initLangSwitcher() {
    const root = document.getElementById('lang-switcher');
    if (!root) return;

    const btn = document.getElementById('lang-switcher-btn');
    const menu = document.getElementById('lang-switcher-menu');
    const label = document.getElementById('lang-switcher-label');
    const chevron = document.getElementById('lang-switcher-chevron');
    const options = Array.from(menu.querySelectorAll('.lang-option'));

    function closeMenu() {
        menu.classList.add('hidden');
        btn.setAttribute('aria-expanded', 'false');
        chevron.classList.remove('rotate-180');
    }

    function openMenu() {
        menu.classList.remove('hidden');
        btn.setAttribute('aria-expanded', 'true');
        chevron.classList.add('rotate-180');
    }

    btn.addEventListener('click', () => {
        const isOpen = btn.getAttribute('aria-expanded') === 'true';
        if (isOpen) closeMenu(); else openMenu();
    });

    options.forEach((option) => {
        option.addEventListener('click', () => {
            label.textContent = option.dataset.langLabel;
            try {
                localStorage.setItem('preferredLang', option.dataset.lang);
            } catch {
                // localStorage indisponível (modo privado, etc.) — não bloqueia a seleção
            }
            closeMenu();
        });
    });

    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) closeMenu();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeMenu();
    });

    try {
        const saved = localStorage.getItem('preferredLang');
        const match = options.find((o) => o.dataset.lang === saved);
        if (match) label.textContent = match.dataset.langLabel;
    } catch {
        // localStorage indisponível — mantém o padrão "PT"
    }
})();

/**
 * MODAL DE CERTIFICADO (página Sobre)
 * Um único <dialog> reaproveitado: cada botão [data-certificate-open] preenche título,
 * emissor/data e o arquivo (imagem ou PDF) antes de abrir.
 */
(function initCertificateModal() {
    const modal = document.getElementById('certificate-modal');
    if (!modal) return;

    const title = document.getElementById('certificate-modal-title');
    const meta = document.getElementById('certificate-modal-meta');
    const body = document.getElementById('certificate-modal-body');

    function open(trigger) {
        const { title: name, issuer, date, file, type } = trigger.dataset;

        title.textContent = name;
        meta.textContent = [issuer, date].filter(Boolean).join(' · ');
        body.replaceChildren();

        if (type === 'pdf') {
            const frame = document.createElement('iframe');
            frame.src = file;
            frame.title = name;
            frame.className = 'h-[70vh] w-full rounded-md bg-white';
            body.append(frame);
        } else {
            const img = document.createElement('img');
            img.src = file;
            img.alt = `Certificado: ${name}`;
            img.className = 'max-h-[70vh] max-w-full rounded-md object-contain';
            body.append(img);
        }

        modal.showModal();
    }

    document.querySelectorAll('[data-certificate-open]').forEach((button) => {
        button.addEventListener('click', () => open(button));
    });

    modal.querySelector('[data-certificate-close]').addEventListener('click', () => modal.close());

    // Clique no fundo escurecido (fora do conteúdo) fecha o modal.
    modal.addEventListener('click', (event) => {
        if (event.target === modal) modal.close();
    });

    modal.addEventListener('close', () => body.replaceChildren());
})();

/** LOADING BAR **/
function startLoading() {
    if (!DOM.progressContainer || !DOM.progressBar) return;

    DOM.progressContainer.classList.remove("hidden");
    DOM.progressBar.style.width = "0%";

    setTimeout(() => (DOM.progressBar.style.width = "30%"), 100);
    setTimeout(() => (DOM.progressBar.style.width = "70%"), 400);
    setTimeout(() => (DOM.progressBar.style.width = "100%"), 700);

    setTimeout(() => {
        DOM.progressContainer.classList.add("hidden");
    }, 1000);
}

function initScrollProgress() {
    if (!DOM.progressBar) return;

    window.addEventListener("scroll", () => {
        const winScroll = document.documentElement.scrollTop;
        const height =
            document.documentElement.scrollHeight -
            document.documentElement.clientHeight;

        const scrolled = (winScroll / height) * 100;
        DOM.progressBar.style.width = `${scrolled}%`;
    });
}

const searchInput   = document.getElementById('search-input');
const clearBtn      = document.getElementById('clear-search');
const shortcutHint  = document.getElementById('search-shortcut');
const countEl       = document.getElementById('projects-count');

let debounceTimer;

function toggleClearAndHint() {
    const hasValue = searchInput.value.length > 0;

    clearBtn.classList.toggle('opacity-0', !hasValue);
    clearBtn.classList.toggle('scale-90', !hasValue);
    clearBtn.classList.toggle('pointer-events-none', !hasValue);

    shortcutHint.classList.toggle('hidden', hasValue);
}

if (searchInput && clearBtn) {
    searchInput.addEventListener('input', () => {
        toggleClearAndHint();

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            runSearch(searchInput.value.trim());
        }, 300);
    });

    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && searchInput.value) {
            clearSearch();
        }
    });

    clearBtn.addEventListener('click', clearSearch);

    document.addEventListener('keydown', (e) => {
        const isShortcut = (e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k';
        if (isShortcut) {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
    });
}

function clearSearch() {
    searchInput.value = '';
    toggleClearAndHint();
    runSearch('');
    searchInput.focus();
}

function runSearch(query) {
    // filtra sua lista de projetos aqui e atualiza o DOM
    // ao final, atualize o contador:
    const results = filterProjects(query); // sua função
    countEl.textContent = `${results.length} projeto${results.length === 1 ? '' : 's'}`;
}

function renderSkeletons(count = SKELETON_COUNT) {
    if (!DOM.container) return;

    DOM.emptyState?.classList.add("hidden");
    DOM.pagination?.classList.add("hidden");
    DOM.container.classList.remove("hidden");

    const skeletonCard = `
        <div class="skeleton-card bg-base-post p-5 rounded-xl border border-base-border">
            <div class="skeleton-block rounded-lg mb-4 h-44 w-full"></div>
            <div class="skeleton-block h-5 w-3/4 rounded mb-3"></div>
            <div class="skeleton-block h-3 w-full rounded mb-2"></div>
            <div class="skeleton-block h-3 w-5/6 rounded mb-4"></div>
            <div class="flex gap-2 mb-4">
                <div class="skeleton-block h-5 w-12 rounded"></div>
                <div class="skeleton-block h-5 w-14 rounded"></div>
            </div>
            <div class="skeleton-block h-4 w-24 rounded"></div>
        </div>
    `;

    DOM.container.innerHTML = skeletonCard.repeat(count);
}

/**
 * CONTRIBUTION GRAPH
 * Troca de ano via fetch, sem navegação/alteração de URL.
 */
(function initContributionGraph() {
    const widget = document.getElementById('contribution-widget');
    if (!widget) return;

    const wrapper = document.getElementById('calendar-wrapper');
    const svg = document.getElementById('calendar-svg');
    const monthsGroup = document.getElementById('calendar-months');
    const squaresGroup = document.getElementById('calendar-squares');
    const countEl = document.getElementById('calendar-count');
    const yearButtons = Array.from(document.querySelectorAll('#year-buttons .year-btn'));
    const endpoint = widget.dataset.endpoint;

    // Largura alvo de cada coluna (célula + gap): define quantas colunas cabem no card
    // (≈35 no desktop). O gap é sempre uma fração fixa da célula (≈3,5px para 21,7px).
    const TARGET_PITCH = 25.2;
    const GAP_RATIO = 0.165;
    const MAX_CELL = 22;
    const MIN_COLUMNS = 20;

    /**
     * Escolhe quantas colunas visuais desenhar e o tamanho da célula/gap para que elas
     * ocupem exatamente a largura disponível do card (sem scroll). As semanas do GitHub
     * são agrupadas nessas colunas em `compressWeeks`.
     */
    function computeGrid(weekCount, width) {
        if (weekCount < 1 || width <= 0) return { columns: 0, cellSize: 0, gap: 0 };

        const columns = Math.min(weekCount, Math.max(MIN_COLUMNS, Math.round(width / TARGET_PITCH)));
        const cellSize = Math.min(MAX_CELL, width / (columns + (columns - 1) * GAP_RATIO));

        return { columns, cellSize, gap: cellSize * GAP_RATIO };
    }

    const columnOf = (week, weekCount, columns) => Math.floor((week * columns) / weekCount);

    /**
     * Agrupa semanas consecutivas em `columns` colunas. Cada célula junta os dias do mesmo dia
     * da semana dentro do grupo: `count` é a soma real dos dias (o total do gráfico não muda),
     * `level`/`color` são os do dia de maior nível do grupo (nenhum nível ou cor é inventado).
     */
    function compressWeeks(squares, weekCount, columns) {
        const cells = new Map();

        squares.forEach((s) => {
            const column = columnOf(s.week, weekCount, columns);
            const key = `${column}:${s.weekday}`;
            const cell = cells.get(key);

            if (!cell) {
                cells.set(key, { column, weekday: s.weekday, count: s.count, level: s.level, color: s.color, dates: [s.date] });
                return;
            }

            cell.count += s.count;
            cell.dates.push(s.date);
            if (s.level > cell.level) {
                cell.level = s.level;
                cell.color = s.color;
            }
        });

        return [...cells.values()];
    }

    function formatTooltipDate(dateStr) {
        const [year, month, day] = dateStr.split('-').map(Number);
        const date = new Date(Date.UTC(year, month - 1, day));
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', timeZone: 'UTC' });
    }

    function formatTooltipCount(count) {
        if (count === 0) return 'No contributions';
        if (count === 1) return '1 contribution';
        return `${count} contributions`;
    }

    const ACTIVE_CLASSES = ['dark:bg-secondary-color', 'bg-secondary-color', 'dark:text-white', 'text-white'];
    const INACTIVE_CLASSES = [
        'dark:bg-primary-bg',
        'bg-zinc-50',
        'dark:text-white',
        'text-zinc-800',
        'dark:hover:border-zinc-700',
        'hover:border-zinc-200',
    ];

    function setActiveButton(year) {
        yearButtons.forEach((btn) => {
            const isActive = btn.dataset.year === String(year);
            btn.dataset.active = isActive ? '1' : '0';
            btn.classList.remove(...ACTIVE_CLASSES, ...INACTIVE_CLASSES);
            btn.classList.add(...(isActive ? ACTIVE_CLASSES : INACTIVE_CLASSES));
        });
    }

    let currentData = null;

    function renderGraph(data) {
        currentData = data;

        const width = wrapper.clientWidth;
        const { columns, cellSize, gap } = computeGrid(data.weekCount, width);
        const pitch = cellSize + gap;
        const topOffset = data.topOffset;
        const height = topOffset + 7 * cellSize + 6 * gap;
        const radius = Math.min(4, Math.max(1.5, cellSize / 5));

        svg.setAttribute('viewBox', `0 0 ${width} ${height}`);

        // Meses distribuídos sobre as colunas visuais; a fonte encolhe com as células e labels
        // que colidiriam em telas estreitas são descartados em vez de sobrepostos.
        const monthFontSize = Math.max(6, Math.min(10, cellSize * 0.62));
        const charWidth = monthFontSize * 0.62;
        let lastLabelEnd = -Infinity;
        const visibleMonths = [];
        data.months.forEach((m) => {
            const x = columnOf(m.week, data.weekCount, columns) * pitch;
            if (x < lastLabelEnd) return;
            visibleMonths.push({ x, label: m.label });
            lastLabelEnd = x + m.label.length * charWidth + monthFontSize * 0.6;
        });

        monthsGroup.innerHTML = visibleMonths
            .map((m) => `<text x="${m.x}" dominant-baseline="hanging" style="font-size:${monthFontSize}px">${m.label}</text>`)
            .join('');

        squaresGroup.innerHTML = compressWeeks(data.squares, data.weekCount, columns)
            .map((c) => {
                const tooltip = `${formatTooltipCount(c.count)} on ${c.dates.map(formatTooltipDate).join(' and ')}`;
                return `<rect x="${c.column * pitch}" y="${topOffset + c.weekday * pitch}" width="${cellSize}" height="${cellSize}" rx="${radius}" ry="${radius}" fill="${c.color}" data-date="${c.dates[0]}" data-level="${c.level}" data-count="${c.count}"><title>${tooltip}</title></rect>`;
            })
            .join('');

        // O total vem diretamente de contributionCalendar.totalContributions (GitHub GraphQL),
        // nunca somado/calculado no frontend.
        const periodLabel = data.isCurrent ? 'the last year' : data.selectedYear;
        countEl.textContent = `${data.total} contributions in ${periodLabel}`;
    }

    let requestToken = 0;

    function loadYear(year) {
        const thisRequest = ++requestToken;

        svg.classList.add('opacity-0');

        fetch(`${endpoint}?year=${encodeURIComponent(year)}`, {
            headers: { Accept: 'application/json' },
        })
            .then((response) => response.json())
            .then((data) => {
                if (thisRequest !== requestToken) return;

                window.setTimeout(() => {
                    renderGraph(data);
                    setActiveButton(year);
                    svg.classList.remove('opacity-0');
                }, 200);
            })
            .catch(() => {
                svg.classList.remove('opacity-0');
            });
    }

    yearButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            if (btn.dataset.active === '1') return;
            loadYear(btn.dataset.year);
        });
    });

    // Recalcula o layout (tamanho de célula + gap) quando o card muda de largura,
    // mantendo as 53 semanas dentro do container, sem overflow.
    let resizeFrame = null;
    const resizeObserver = new ResizeObserver(() => {
        if (!currentData) return;
        if (resizeFrame) cancelAnimationFrame(resizeFrame);
        resizeFrame = requestAnimationFrame(() => renderGraph(currentData));
    });
    resizeObserver.observe(wrapper);

    const initialDataEl = document.getElementById('calendar-data');
    if (initialDataEl) {
        try {
            renderGraph(JSON.parse(initialDataEl.textContent));
        } catch {
            // mantém a marcação renderizada no servidor caso o JSON seja inválido
        }
    }
})();

/**
 * GALERIA DE SCREENSHOTS (lightbox com navegação)
 * Abre a imagem em um modal, navega entre as fotos (botões, teclado e swipe),
 * trava o scroll do body e devolve o foco ao abrir/fechar.
 */
(function initGalleries() {
    const galleries = document.querySelectorAll("[data-gallery]");
    if (!galleries.length) return;

    galleries.forEach((gallery) => {
        const dataEl = gallery.querySelector("[data-gallery-data]");
        const modal = gallery.querySelector("[data-gallery-modal]");
        if (!dataEl || !modal) return;

        let slides = [];
        try {
            slides = JSON.parse(dataEl.textContent);
        } catch {
            return;
        }
        if (!Array.isArray(slides) || slides.length === 0) return;

        const image = modal.querySelector("[data-gallery-image]");
        const caption = modal.querySelector("[data-gallery-caption]");
        const counter = modal.querySelector("[data-gallery-counter]");
        const btnPrev = modal.querySelector("[data-gallery-prev]");
        const btnNext = modal.querySelector("[data-gallery-next]");
        const btnClose = modal.querySelector("[data-gallery-close]");
        const backdrop = modal.querySelector("[data-gallery-backdrop]");
        const single = slides.length < 2;

        let current = 0;
        let lastFocused = null;

        function render(direction = 0) {
            const slide = slides[current];
            if (!slide) return;

            // pequena transição de slide
            image.style.opacity = "0";
            if (direction !== 0) {
                image.style.transform = `translateX(${direction > 0 ? "24px" : "-24px"})`;
            }

            const swap = () => {
                image.src = slide.src;
                image.alt = slide.alt || "";
                requestAnimationFrame(() => {
                    image.style.transform = "translateX(0)";
                    image.style.opacity = "1";
                });
            };

            if (image.src) {
                setTimeout(swap, 120);
            } else {
                swap();
            }

            caption.textContent = slide.caption || "";
            counter.textContent = `${current + 1} / ${slides.length}`;
        }

        function go(step) {
            if (single) return;
            current = (current + step + slides.length) % slides.length;
            render(step);
        }

        function open(index) {
            current = Number.isInteger(index) ? index : 0;
            lastFocused = document.activeElement;
            modal.classList.remove("hidden");
            modal.classList.add("flex");
            document.body.style.overflow = "hidden";
            render(0);
            btnClose?.focus();
            document.addEventListener("keydown", onKeydown);
        }

        function close() {
            modal.classList.add("hidden");
            modal.classList.remove("flex");
            document.body.style.overflow = "";
            document.removeEventListener("keydown", onKeydown);
            if (lastFocused && typeof lastFocused.focus === "function") {
                lastFocused.focus();
            }
        }

        function onKeydown(event) {
            if (event.key === "Escape") close();
            else if (event.key === "ArrowRight") go(1);
            else if (event.key === "ArrowLeft") go(-1);
        }

        gallery.querySelectorAll("[data-gallery-open]").forEach((trigger) => {
            trigger.addEventListener("click", () => open(Number(trigger.dataset.index)));
        });

        btnPrev?.addEventListener("click", () => go(-1));
        btnNext?.addEventListener("click", () => go(1));
        btnClose?.addEventListener("click", close);
        backdrop?.addEventListener("click", close);

        if (single) {
            btnPrev?.classList.add("hidden");
            btnNext?.classList.add("hidden");
        }

        // Swipe no touch
        let touchStartX = null;
        image.addEventListener("touchstart", (event) => {
            touchStartX = event.changedTouches[0].clientX;
        }, { passive: true });
        image.addEventListener("touchend", (event) => {
            if (touchStartX === null) return;
            const delta = event.changedTouches[0].clientX - touchStartX;
            if (Math.abs(delta) > 40) go(delta < 0 ? 1 : -1);
            touchStartX = null;
        }, { passive: true });
    });
})();
