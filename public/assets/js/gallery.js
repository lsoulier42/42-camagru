/*
 * Camagru — galerie AJAX (JavaScript natif, aucune dépendance).
 * Like, commentaires et pagination sans rechargement de page.
 * Sans JavaScript, les formulaires et liens fonctionnent normalement
 * (soumission POST + redirections côté serveur).
 */
(() => {
    'use strict';

    const token = () => (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    const ajaxHeaders = { 'X-Requested-With': 'XMLHttpRequest' };

    /** @return {Promise<object>} réponse JSON ou { ok:false } en cas d'erreur réseau. */
    const postJson = async (url, form) => {
        const data = new FormData(form);
        data.set('csrf_token', token());
        const res = await fetch(url, { method: 'POST', body: data, headers: ajaxHeaders });
        return { status: res.status, json: await res.json().catch(() => ({ ok: false })) };
    };

    // --- Like et commentaires (délégation : fonctionne après pagination AJAX) ---
    document.addEventListener('submit', async (event) => {
        const form = event.target;

        if (form.classList.contains('js-like-form')) {
            event.preventDefault();
            const { status, json } = await postJson(form.action, form);
            if (status === 401 || !json.ok) {
                window.location.href = status === 401 ? '/login' : window.location.pathname + window.location.search;
                return;
            }
            const button = form.querySelector('button');
            button.classList.toggle('like-btn--active', !!json.liked);
            button.textContent = '♥ ' + json.count;
            button.setAttribute('aria-label', json.liked ? 'Retirer mon like' : 'J\'aime');
            return;
        }

        if (form.classList.contains('js-comment-form')) {
            event.preventDefault();
            const { status, json } = await postJson(form.action, form);
            if (status === 401 || !json.ok) {
                window.location.href = status === 401 ? '/login' : window.location.pathname + window.location.search;
                return;
            }
            const card = form.closest('.gallery-card');
            const list = card.querySelector('.gallery-comments');

            // textContent uniquement → le contenu n'est jamais interprété (anti-XSS).
            const paragraph = document.createElement('p');
            paragraph.className = 'comment';
            const author = document.createElement('strong');
            author.textContent = json.comment.author;
            paragraph.appendChild(author);
            paragraph.appendChild(document.createTextNode(' : ' + json.comment.content));
            list.appendChild(paragraph);

            card.querySelector('.gallery-comments-count').textContent = '💬 ' + json.commentsCount;
            form.reset();
        }
    });

    // --- Pagination infinie ---
    // Sans JavaScript, le bouton « Suivant » reste un lien classique (href)
    // et la pagination complète fonctionne par rechargements.
    const content = document.getElementById('gallery-content');
    const getNav = () => document.querySelector('.pagination');
    const getGrid = () => content.querySelector('.gallery-grid');

    let nextPage = null;
    let loading = false;
    let observer = null;

    /** Lit data-page/data-total-pages du bandeau de pagination courant. */
    const computeNextPage = () => {
        const nav = getNav();
        if (!nav) {
            return null;
        }
        const page = parseInt(nav.dataset.page || '1', 10);
        const total = parseInt(nav.dataset.totalPages || '1', 10);
        return page < total ? page + 1 : null;
    };

    /** Ajoute la page suivante au bas de la grille (sans rechargement). */
    const loadNext = async () => {
        if (loading || nextPage === null) {
            return null;
        }
        loading = true;
        const page = nextPage;

        try {
            const res = await fetch('/gallery?page=' + page, { headers: ajaxHeaders });
            const json = await res.json().catch(() => ({ ok: false }));
            if (!json.ok || !json.html) {
                window.location.href = '/gallery?page=' + page; // repli : navigation classique
                return null;
            }

            const doc = new DOMParser().parseFromString(json.html, 'text/html');
            const grid = getGrid() || content;
            let firstNewCard = null;
            doc.querySelectorAll('.gallery-card').forEach((card) => {
                const imported = document.importNode(card, true);
                grid.appendChild(imported);
                if (!firstNewCard) {
                    firstNewCard = imported;
                }
            });

            // Bandeau de pagination : remplacé par celui de la page chargée
            // (le lien « Précédent » porte data-full-nav → navigation classique).
            const newNav = doc.querySelector('.pagination');
            const oldNav = getNav();
            if (newNav && oldNav) {
                oldNav.replaceWith(document.importNode(newNav, true));
            }

            // Compteur « N images — page X / Y ».
            const newCount = doc.querySelector('#gallery-count');
            const countEl = document.getElementById('gallery-count');
            if (newCount && countEl) {
                countEl.textContent = newCount.textContent;
            }

            nextPage = computeNextPage();
            history.replaceState(null, '', '/gallery?page=' + page);
            if (nextPage === null && observer) {
                observer.disconnect(); // dernière page atteinte
            }
            return firstNewCard;
        } catch (err) {
            window.location.href = '/gallery?page=' + page; // repli : navigation classique
            return null;
        } finally {
            loading = false;
        }
    };

    // Bouton « Suivant » : même chargement, avec défilement vers le nouveau contenu.
    document.addEventListener('click', async (event) => {
        const link = event.target.closest('a[data-ajax-page]');
        if (!link) {
            return;
        }
        if (link.hasAttribute('data-full-nav')) {
            return; // « Précédent » : navigation classique (repli complet)
        }
        event.preventDefault();
        const firstNew = await loadNext();
        if (firstNew) {
            firstNew.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    // Défilement infini : charge la page suivante quand la sentinelle approche.
    const sentinel = document.getElementById('gallery-sentinel');
    if (sentinel && 'IntersectionObserver' in window) {
        observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                loadNext();
            }
        }, { rootMargin: '200px 0px' });
        observer.observe(sentinel);
    }

    nextPage = computeNextPage();
})();
