(function () {
    if (typeof window.mmSphynxKittens === 'undefined') {
        return;
    }

    const dataset = new Map();
    const rawKittens = Array.isArray(mmSphynxKittens.kittens) ? mmSphynxKittens.kittens : [];

    rawKittens.forEach((item) => {
        if (!item) {
            return;
        }
        if (item.kitten_id) {
            dataset.set(String(item.kitten_id).toLowerCase(), item);
        }
        if (item.id) {
            dataset.set(String(item.id), item);
        }
    });

    const modal = document.getElementById('mm-kitten-modal');
    if (!modal) {
        return;
    }

    const carousel = modal.querySelector('[data-modal-gallery] .mm-carousel');
    const placeholderImg = modal.querySelector('[data-modal-placeholder]');
    const titleNode = modal.querySelector('[data-modal-title]');
    const statusNode = modal.querySelector('[data-modal-status]');
    const summaryNode = modal.querySelector('[data-modal-summary]');
    const specsNode = modal.querySelector('[data-modal-specs]');
    const tagsNode = modal.querySelector('[data-modal-tags]');
    const notesNode = modal.querySelector('[data-modal-notes]');
    const primaryButton = modal.querySelector('[data-modal-primary]');

    const placeholder = mmSphynxKittens.placeholder || '';
    const waitlistUrl = mmSphynxKittens.waitlistUrl || '/waitlist/';

    let lastFocusedElement = null;

    function normaliseColors(value) {
        if (!value) {
            return [];
        }
        if (Array.isArray(value)) {
            return value;
        }
        return String(value).split(/[;,]/).map((item) => item.trim()).filter(Boolean);
    }

    function normaliseTags(value) {
        if (!value) {
            return [];
        }
        if (Array.isArray(value)) {
            return value;
        }
        return String(value).split(/[;,]/).map((item) => item.trim()).filter(Boolean);
    }

    function titleCase(value) {
        return String(value)
            .replace(/[-_]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .replace(/\b\w/g, (char) => char.toUpperCase());
    }

    function formatPrice(value) {
        if (value === null || value === undefined || value === '') {
            return modal.dataset.priceFallback || 'Contact for pricing';
        }
        const number = Number(value);
        if (Number.isNaN(number)) {
            return String(value);
        }
        return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(number);
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
        if (!carousel) {
            return;
        }

        clearNode(carousel);

        const gallery = Array.isArray(kitten.gallery) ? kitten.gallery : [];
        const cover = kitten.cover_image || kitten.thumbnail || placeholder;
        const sources = gallery.length ? gallery : [cover, placeholder].filter(Boolean);

        sources.forEach((src, index) => {
            const img = document.createElement('img');
            img.src = src || placeholder;
            img.alt = `${kitten.title || kitten.kitten_id || 'Kitten'} image ${index + 1}`;
            img.loading = 'lazy';
            img.decoding = 'async';
            img.className = 'skip-lazy';
            img.setAttribute('data-no-lazy', '1');
            carousel.appendChild(img);
        });
    }

    function renderSpecs(kitten) {
        if (!specsNode) {
            return;
        }
        clearNode(specsNode);

        const colors = normaliseColors(kitten.color).map(titleCase).join(', ');

        const rows = [
            { label: 'Kitten ID', value: kitten.kitten_id || kitten.title || '' },
            { label: 'Sex', value: kitten.sex ? titleCase(kitten.sex) : '' },
            { label: 'Age', value: kitten.age_hint || kitten.age || '' },
            { label: 'Color', value: colors },
            { label: 'Price', value: formatPrice(kitten.price) },
            { label: 'Status', value: titleCase(kitten.status_label || kitten.status || '') },
        ];

        rows.forEach((row) => {
            if (!row.value) {
                return;
            }
            const wrapper = document.createElement('div');
            const term = document.createElement('dt');
            term.textContent = row.label;
            const desc = document.createElement('dd');
            desc.textContent = row.value;
            wrapper.appendChild(term);
            wrapper.appendChild(desc);
            specsNode.appendChild(wrapper);
        });
    }

    function renderTags(kitten) {
        if (!tagsNode) {
            return;
        }
        clearNode(tagsNode);

        const tags = normaliseTags(kitten.temperament);
        if (!tags.length) {
            tagsNode.style.display = 'none';
            return;
        }

        tagsNode.style.display = '';
        tags.slice(0, 6).forEach((tag) => {
            const span = document.createElement('span');
            span.textContent = titleCase(tag);
            tagsNode.appendChild(span);
        });
    }

    function renderNotes(kitten) {
        if (!notesNode) {
            return;
        }

        const parts = [];
        if (kitten.health_notes) {
            parts.push(kitten.health_notes);
        }
        if (kitten.care_profile) {
            parts.push(kitten.care_profile);
        }

        if (!parts.length) {
            notesNode.style.display = 'none';
            notesNode.textContent = '';
            return;
        }

        notesNode.style.display = '';
        notesNode.textContent = parts.join(' ');
    }

    function setPrimaryAction(kitten) {
        if (!primaryButton) {
            return;
        }

        const status = (kitten.status || '').toLowerCase();
        const isAvailable = status === 'available';
        const applyUrl = kitten.apply_url || '#';
        primaryButton.textContent = isAvailable ? 'Apply' : 'Join Waitlist';
        primaryButton.href = isAvailable ? applyUrl : waitlistUrl;
    }

    function openModal(identifier) {
        if (!identifier) {
            return;
        }

        const key = String(identifier).toLowerCase();
        const kitten = dataset.get(key);
        if (!kitten) {
            return;
        }

        lastFocusedElement = document.activeElement;

        renderGallery(kitten);
        renderSpecs(kitten);
        renderTags(kitten);
        renderNotes(kitten);
        setPrimaryAction(kitten);

        if (titleNode) {
            titleNode.textContent = kitten.title || kitten.kitten_id || '';
        }
        if (statusNode) {
            statusNode.textContent = titleCase(kitten.status_label || kitten.status || '');
        }
        if (summaryNode) {
            summaryNode.textContent = kitten.short_description || '';
            summaryNode.style.display = summaryNode.textContent ? '' : 'none';
        }

        modal.removeAttribute('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('mm-modal-open');

        const focusable = modal.querySelectorAll('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])');
        if (focusable.length) {
            focusable[0].focus();
        }

        document.addEventListener('keydown', trapFocus);
    }

    function closeModal() {
        modal.setAttribute('hidden', 'hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('mm-modal-open');
        document.removeEventListener('keydown', trapFocus);

        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
            lastFocusedElement.focus();
        }
    }

    function trapFocus(event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal();
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const focusable = Array.from(modal.querySelectorAll('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'));
        if (!focusable.length) {
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    modal.addEventListener('click', (event) => {
        if (event.target.matches('[data-modal-close]') || event.target === modal.querySelector('.mm-kitten-modal__backdrop')) {
            closeModal();
        }
    });

    document.querySelectorAll('[data-kitten-open]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            const target = button.getAttribute('data-kitten-target') || button.getAttribute('data-kitten-id');
            openModal(target);
        });
    });
})();
