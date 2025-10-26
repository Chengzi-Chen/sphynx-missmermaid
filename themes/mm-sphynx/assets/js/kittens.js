/* global mmSphynxKittens */
(function () {
    if (typeof mmSphynxKittens === 'undefined') {
        return;
    }

    const dataMap = new Map();
    (mmSphynxKittens.kittens || []).forEach((kitten) => {
        dataMap.set(String(kitten.id), kitten);
    });

    const modal = document.getElementById('mm-kitten-modal');
    const modalContent = modal ? modal.querySelector('.mm-kitten-modal__content') : null;
    const galleryContainer = modal ? modal.querySelector('[data-modal-gallery]') : null;
    const videosContainer = modal ? modal.querySelector('[data-modal-videos]') : null;
    const personalityContainer = modal ? modal.querySelector('[data-modal-personality]') : null;
    const fieldsContainer = modal ? modal.querySelector('[data-modal-fields]') : null;
    const titleNode = modal ? modal.querySelector('[data-modal-title]') : null;
    const metaNode = modal ? modal.querySelector('[data-modal-meta]') : null;
    const priceNode = modal ? modal.querySelector('[data-modal-price]') : null;
    const applyBtn = modal ? modal.querySelector('[data-modal-apply]') : null;
    const waitlistBtn = modal ? modal.querySelector('[data-modal-waitlist]') : null;

    const focusableSelectors = [
        'a[href]',
        'button:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ];

    let lastFocusedElement = null;

    function formatPrice(value) {
        if (value === null || value === undefined || value === '') {
            return window.wp && wp.i18n ? wp.i18n.__('Contact for pricing', 'mm-sphynx') : 'Contact for pricing';
        }
        return '$' + Number(value).toLocaleString();
    }

    function clearNode(node) {
        if (!node) {
            return;
        }
        while (node.firstChild) {
            node.removeChild(node.firstChild);
        }
    }

    function renderGallery(kitten) {
        clearNode(galleryContainer);
        if (!galleryContainer || !kitten.gallery || kitten.gallery.length === 0) {
            return;
        }
        kitten.gallery.forEach((src) => {
            const img = document.createElement('img');
            img.src = src;
            img.alt = '';
            img.loading = 'lazy';
            galleryContainer.appendChild(img);
        });
    }

    function renderVideos(kitten) {
        clearNode(videosContainer);
        if (!videosContainer || !kitten.videos || kitten.videos.length === 0) {
            return;
        }
        kitten.videos.forEach((url) => {
            const iframe = document.createElement('iframe');
            iframe.src = url;
            iframe.setAttribute('allowfullscreen', 'allowfullscreen');
            iframe.setAttribute('loading', 'lazy');
            videosContainer.appendChild(iframe);
        });
    }

    function renderPersonality(kitten) {
        clearNode(personalityContainer);
        if (!personalityContainer || !kitten.personality || kitten.personality.length === 0) {
            return;
        }
        kitten.personality.forEach((tag) => {
            const span = document.createElement('span');
            span.textContent = tag.replace(/-/g, ' ');
            personalityContainer.appendChild(span);
        });
    }

    function renderFields(kitten) {
        clearNode(fieldsContainer);
        if (!fieldsContainer) {
            return;
        }
        const rows = [
            { label: 'Kitten ID', value: kitten.kitten_id || '' },
            { label: 'Status', value: kitten.status_label || '' },
            { label: 'Birthdate', value: kitten.birthdate || '' },
            { label: 'Age', value: kitten.age || '' },
            {
                label: 'Color',
                value: (kitten.color || [])
                    .map((color) => color.replace(/-/g, ' '))
                    .join(', '),
            },
        ];
        rows.forEach((row) => {
            if (!row.value) {
                return;
            }
            const wrapper = document.createElement('div');
            const label = document.createElement('span');
            label.textContent = row.label;
            const value = document.createElement('span');
            value.textContent = row.value;
            wrapper.appendChild(label);
            wrapper.appendChild(value);
            fieldsContainer.appendChild(wrapper);
        });
    }

    function trapFocus(event) {
        if (!modal || modal.hasAttribute('hidden')) {
            return;
        }
        const focusableElements = modalContent
            ? Array.from(modalContent.querySelectorAll(focusableSelectors.join(',')))
            : [];
        if (focusableElements.length === 0) {
            return;
        }
        const first = focusableElements[0];
        const last = focusableElements[focusableElements.length - 1];
        if (event.key === 'Tab') {
            if (event.shiftKey && document.activeElement === first) {
                last.focus();
                event.preventDefault();
            } else if (!event.shiftKey && document.activeElement === last) {
                first.focus();
                event.preventDefault();
            }
        } else if (event.key === 'Escape') {
            closeModal();
        }
    }

    function closeModal() {
        if (!modal) {
            return;
        }
        modal.setAttribute('hidden', 'hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('mm-modal-open');
        document.removeEventListener('keydown', trapFocus);
        if (lastFocusedElement) {
            lastFocusedElement.focus();
        }
    }

    function buildFormUrl(formId, kittenId) {
        if (!formId) {
            return '#';
        }
        const base = new URL(window.location.origin + window.location.pathname);
        base.hash = 'form-' + formId;
        if (kittenId) {
            base.searchParams.set('kitten', kittenId);
        }
        return base.toString();
    }

    function openModal(kittenId) {
        if (!modal || !dataMap.has(kittenId)) {
            return;
        }
        const kitten = dataMap.get(kittenId);
        if (titleNode) {
            titleNode.textContent = kitten.kitten_id || kitten.title || '';
        }
        if (metaNode) {
            const metaBits = [];
            if (kitten.sex) {
                metaBits.push(kitten.sex.replace(/(^|\s)\S/g, (s) => s.toUpperCase()));
            }
            if (kitten.color && kitten.color.length) {
                metaBits.push(kitten.color.join(', '));
            }
            if (kitten.age) {
                metaBits.push(kitten.age);
            }
            metaNode.textContent = metaBits.join(' • ');
        }
        if (priceNode) {
            priceNode.textContent = formatPrice(kitten.price);
        }

        renderGallery(kitten);
        renderVideos(kitten);
        renderPersonality(kitten);
        renderFields(kitten);

        if (applyBtn) {
            applyBtn.dataset.kittenId = kitten.kitten_id || '';
            applyBtn.dataset.targetKitten = kittenId;
        }
        if (waitlistBtn) {
            waitlistBtn.dataset.kittenId = kitten.kitten_id || '';
        }

        modal.removeAttribute('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('mm-modal-open');
        document.addEventListener('keydown', trapFocus);

        const focusable = modalContent
            ? modalContent.querySelector(focusableSelectors.join(','))
            : null;
        if (focusable) {
            focusable.focus();
        }
    }

    function handleCardClick(event) {
        const card = event.currentTarget;
        const kittenId = card ? card.getAttribute('data-kitten-id') : null;
        if (!kittenId) {
            return;
        }
        lastFocusedElement = card.querySelector('[data-kitten-open]') || card;
        openModal(String(kittenId));
    }

    document.querySelectorAll('[data-kitten-card]').forEach((card) => {
        const trigger = card.querySelector('[data-kitten-open]');
        if (trigger) {
            trigger.addEventListener('click', handleCardClick.bind(trigger, { currentTarget: card }));
        }
        card.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                handleCardClick({ currentTarget: card });
            }
        });
        card.setAttribute('tabindex', '0');
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    document.querySelectorAll('.mm-kittens-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.getAttribute('data-tab-target');
            if (!target) {
                return;
            }
            document.querySelectorAll('.mm-kittens-tab').forEach((btn) => {
                btn.classList.toggle('is-active', btn === tab);
                btn.setAttribute('aria-selected', btn === tab ? 'true' : 'false');
            });
            document.querySelectorAll('.mm-kittens-grid').forEach((panel) => {
                const matches = panel.id === 'mm-tab-' + target;
                if (matches) {
                    panel.classList.add('is-active');
                    panel.removeAttribute('hidden');
                } else {
                    panel.classList.remove('is-active');
                    panel.setAttribute('hidden', 'hidden');
                }
            });
        });
    });

    function redirectToForm(formId, kittenId) {
        if (!formId) {
            return;
        }
        const url = buildFormUrl(formId, kittenId);
        window.location.href = url;
    }

    if (applyBtn) {
        applyBtn.addEventListener('click', () => {
            const formId = mmSphynxKittens.forms ? mmSphynxKittens.forms.apply : null;
            const kittenId = applyBtn.dataset.kittenId || '';
            redirectToForm(formId, kittenId);
        });
    }

    if (waitlistBtn) {
        waitlistBtn.addEventListener('click', () => {
            const formId = mmSphynxKittens.forms ? mmSphynxKittens.forms.waitlist : null;
            redirectToForm(formId, waitlistBtn.dataset.kittenId || '');
        });
    }

    document.querySelectorAll('[data-waitlist-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const formId = mmSphynxKittens.forms ? mmSphynxKittens.forms.waitlist : null;
            redirectToForm(formId, '');
        });
    });
})();
