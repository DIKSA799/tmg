const SKELETON_ROWS = 4;

function normalize(value) {
    return String(value ?? '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .trim();
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (character) => {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        }[character];
    });
}

/**
 * A lightweight, accessible, searchable combobox driven by remote data.
 */
export class SearchSelect {
    constructor(root, { onChange } = {}) {
        this.root = root;
        this.name = root.dataset.name;
        this.onChange = onChange ?? (() => {});
        this.trigger = root.querySelector('[data-combo-trigger]');
        this.valueEl = root.querySelector('[data-combo-value]');
        this.panel = root.querySelector('[data-combo-panel]');
        this.search = root.querySelector('[data-combo-search]');
        this.list = root.querySelector('[data-combo-list]');
        this.empty = root.querySelector('[data-combo-empty]');
        this.input = root.querySelector('[data-combo-input]');

        this.options = [];
        this.filtered = [];
        this.selected = null;
        this.activeIndex = -1;
        this.placeholder = this.valueEl.dataset.placeholder || 'Select an option';
        this.disabled = this.root.dataset.disabled === 'true';

        this.bind();
        this.applyDisabled();
        this.setMessage('Loading options…');
    }

    bind() {
        this.trigger.addEventListener('click', () => this.toggle());
        this.trigger.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                this.open();
            }
        });

        this.search.addEventListener('input', () => this.render());
        this.search.addEventListener('keydown', (event) => this.onSearchKey(event));

        this.list.addEventListener('click', (event) => {
            const option = event.target.closest('[data-value]');
            if (option) {
                this.select(option.dataset.value);
            }
        });

        this.list.addEventListener('mousemove', (event) => {
            const option = event.target.closest('[data-value]');
            if (option) {
                this.setActive(Number(option.dataset.index));
            }
        });

        document.addEventListener('click', (event) => {
            if (!this.root.contains(event.target)) {
                this.close();
            }
        });
    }

    get value() {
        return this.selected;
    }

    setLoading() {
        this.list.replaceChildren();
        this.empty.hidden = true;

        const fragment = document.createDocumentFragment();
        for (let index = 0; index < SKELETON_ROWS; index += 1) {
            const row = document.createElement('li');
            row.className = 'skeleton skeleton-row';
            fragment.appendChild(row);
        }
        this.list.appendChild(fragment);
    }

    setMessage(text) {
        this.options = [];
        this.filtered = [];
        this.list.replaceChildren();
        this.empty.textContent = text;
        this.empty.hidden = false;
    }

    setOptions(items, { autoSelect = true } = {}) {
        this.options = items;
        this.render();

        if (autoSelect && this.selected === null && items.length > 0) {
            this.select(String(items[0].value));
        }

        return this;
    }

    setDisabled(disabled) {
        this.disabled = Boolean(disabled);
        this.applyDisabled();
        if (this.disabled) {
            this.close();
        }
        return this;
    }

    applyDisabled() {
        this.root.dataset.disabled = this.disabled ? 'true' : 'false';
        this.trigger.disabled = this.disabled;
    }

    select(value, { notify = true } = {}) {
        const option = this.options.find((item) => String(item.value) === String(value));
        if (!option) {
            return;
        }

        this.selected = String(option.value);
        this.input.value = this.selected;
        this.valueEl.textContent = option.label;
        this.valueEl.dataset.empty = 'false';
        this.render();
        this.close();

        if (notify) {
            this.onChange(this.selected, option);
        }
    }

    clear({ disable = false } = {}) {
        this.selected = null;
        this.input.value = '';
        this.valueEl.textContent = this.placeholder;
        this.valueEl.dataset.empty = 'true';
        this.options = [];
        this.filtered = [];
        this.list.replaceChildren();
        if (disable) {
            this.setDisabled(true);
        }
        return this;
    }

    open() {
        if (this.disabled || !this.panel.hidden) {
            return;
        }

        this.panel.hidden = false;
        this.trigger.setAttribute('aria-expanded', 'true');
        this.search.value = '';
        this.render();
        requestAnimationFrame(() => this.search.focus());
    }

    close() {
        if (this.panel.hidden) {
            return;
        }

        this.panel.hidden = true;
        this.trigger.setAttribute('aria-expanded', 'false');
    }

    toggle() {
        if (this.panel.hidden) {
            this.open();
        } else {
            this.close();
        }
    }

    render() {
        const term = normalize(this.search.value);
        this.filtered = term === ''
            ? this.options
            : this.options.filter((item) => normalize(item.label).includes(term) || normalize(item.code).includes(term));

        this.list.replaceChildren();

        if (this.filtered.length === 0) {
            this.empty.textContent = this.options.length === 0 ? 'Nothing to choose from yet.' : 'No matches found.';
            this.empty.hidden = false;
            this.activeIndex = -1;
            return;
        }

        this.empty.hidden = true;
        const fragment = document.createDocumentFragment();

        this.filtered.forEach((item, index) => {
            const option = document.createElement('li');
            option.className = 'combo-option';
            option.setAttribute('role', 'option');
            option.dataset.value = String(item.value);
            option.dataset.index = String(index);
            option.setAttribute('aria-selected', String(this.selected === String(item.value)));
            option.innerHTML = `<span>${escapeHtml(item.label)}</span>${item.code ? `<small>${escapeHtml(item.code)}</small>` : ''}`;
            fragment.appendChild(option);
        });

        this.list.appendChild(fragment);

        const currentIndex = this.filtered.findIndex((item) => String(item.value) === this.selected);
        this.setActive(currentIndex === -1 ? 0 : currentIndex);
    }

    setActive(index) {
        if (this.filtered.length === 0) {
            this.activeIndex = -1;
            return;
        }

        this.activeIndex = ((index % this.filtered.length) + this.filtered.length) % this.filtered.length;

        Array.from(this.list.children).forEach((child, childIndex) => {
            if (childIndex === this.activeIndex) {
                child.dataset.active = 'true';
            } else {
                delete child.dataset.active;
            }
        });

        this.list.children[this.activeIndex]?.scrollIntoView({ block: 'nearest' });
    }

    onSearchKey(event) {
        const count = this.filtered.length;

        if (event.key === 'Escape') {
            this.close();
            this.trigger.focus();
            return;
        }

        if (count === 0) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            this.setActive(this.activeIndex + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            this.setActive(this.activeIndex - 1);
        } else if (event.key === 'Enter') {
            event.preventDefault();
            const option = this.filtered[this.activeIndex];
            if (option) {
                this.select(option.value);
            }
        }
    }
}
