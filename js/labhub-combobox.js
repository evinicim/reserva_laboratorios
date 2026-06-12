(function () {
    'use strict';

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function norm(str) {
        return String(str || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function getCatalog(type) {
        const catalog = window.LABHUB_CATALOG || {};
        return (catalog[type] || []).map(function (item) {
            if (typeof item === 'string') {
                return { id: item, nome: item, label: item };
            }
            return {
                id: item.id != null ? String(item.id) : String(item.nome),
                nome: item.nome,
                label: item.label || item.nome,
            };
        });
    }

    function pushCatalog(type, item) {
        window.LABHUB_CATALOG = window.LABHUB_CATALOG || {};
        if (!window.LABHUB_CATALOG[type]) {
            window.LABHUB_CATALOG[type] = [];
        }
        const exists = window.LABHUB_CATALOG[type].some(function (x) {
            const nome = typeof x === 'string' ? x : x.nome;
            return norm(nome) === norm(item.nome);
        });
        if (!exists) {
            window.LABHUB_CATALOG[type].push(item);
        }
    }

    async function createItem(type, nome) {
        const res = await fetch(window.LABHUB_API_CADASTROS || 'api_cadastros.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tipo: type, nome: nome }),
        });
        const data = await res.json();
        if (!data.ok) {
            throw new Error(data.error || 'Erro ao cadastrar');
        }
        pushCatalog(type, data.item);
        return data.item;
    }

    function buildOptionsFromSelect(select) {
        return Array.from(select.options)
            .filter(function (opt) { return opt.value !== ''; })
            .map(function (opt) {
                return { value: opt.value, label: opt.text, element: opt };
            });
    }

    function enhanceSelect(select) {
        if (select.dataset.lhEnhanced === '1') return;
        select.dataset.lhEnhanced = '1';

        const wrap = document.createElement('div');
        wrap.className = 'lh-combobox';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);
        select.classList.add('lh-combobox-native');
        select.setAttribute('tabindex', '-1');

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control lh-combobox-input' + (select.classList.contains('form-select-lg') ? ' form-control-lg' : '');
        input.autocomplete = 'off';
        input.placeholder = select.options[0]?.text || 'Digite para buscar...';
        wrap.insertBefore(input, select);

        const dropdown = document.createElement('div');
        dropdown.className = 'lh-combobox-dropdown d-none';
        wrap.appendChild(dropdown);

        const createType = select.dataset.lhCreate || '';
        const canCreate = window.LABHUB_CAN_CREATE !== false && createType !== '';
        let options = buildOptionsFromSelect(select);

        function syncInputFromSelect() {
            const opt = select.options[select.selectedIndex];
            input.value = opt && opt.value ? opt.text : '';
            input.classList.remove('is-invalid-lite');
        }

        function selectOption(value, label) {
            let found = false;
            Array.from(select.options).forEach(function (opt) {
                if (opt.value === value) {
                    opt.selected = true;
                    found = true;
                }
            });
            if (!found && value) {
                const opt = document.createElement('option');
                opt.value = value;
                opt.text = label;
                opt.selected = true;
                select.appendChild(opt);
                options.push({ value: value, label: label, element: opt });
            }
            input.value = label;
            dropdown.classList.add('d-none');
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function renderDropdown(query) {
            const nq = norm(query);
            dropdown.innerHTML = '';
            const matches = options.filter(function (o) {
                return !nq || norm(o.label).includes(nq);
            });

            matches.slice(0, 25).forEach(function (o) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'lh-combobox-item';
                btn.textContent = o.label;
                btn.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    selectOption(o.value, o.label);
                });
                dropdown.appendChild(btn);
            });

            if (canCreate && query.trim() && !matches.some(function (o) { return norm(o.label) === nq; })) {
                const addBtn = document.createElement('button');
                addBtn.type = 'button';
                addBtn.className = 'lh-combobox-item lh-combobox-add';
                addBtn.innerHTML = '<i class="bi bi-plus-circle me-1"></i> Adicionar "' + escapeHtml(query.trim()) + '"';
                addBtn.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    addBtn.disabled = true;
                    addBtn.textContent = 'Salvando...';
                    createItem(createType, query.trim())
                        .then(function (item) {
                            const usesNameAsValue = select.dataset.lhValue === 'nome';
                            const value = usesNameAsValue ? item.nome : String(item.id);
                            selectOption(value, item.nome);
                        })
                        .catch(function (err) {
                            alert(err.message || 'Não foi possível cadastrar.');
                            renderDropdown(query);
                        });
                });
                dropdown.appendChild(addBtn);
            }

            if (!dropdown.children.length) {
                dropdown.innerHTML = '<div class="lh-combobox-empty">Nenhum resultado encontrado</div>';
            }
            dropdown.classList.remove('d-none');
        }

        input.addEventListener('focus', function () { renderDropdown(input.value); });
        input.addEventListener('input', function () {
            select.value = '';
            renderDropdown(input.value);
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') dropdown.classList.add('d-none');
        });

        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) dropdown.classList.add('d-none');
        });

        select.form?.addEventListener('submit', function () {
            if (select.required && !select.value && input.value.trim()) {
                input.classList.add('is-invalid-lite');
            }
        });

        syncInputFromSelect();
    }

    function enhancePick(input) {
        if (input.dataset.lhEnhanced === '1') return;
        input.dataset.lhEnhanced = '1';

        const type = input.dataset.lhPick;
        const createType = input.dataset.lhCreate || type;
        const canCreate = window.LABHUB_CAN_CREATE !== false && input.hasAttribute('data-lh-create');

        const wrap = document.createElement('div');
        wrap.className = 'lh-combobox';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);
        input.classList.add('lh-combobox-input');

        const dropdown = document.createElement('div');
        dropdown.className = 'lh-combobox-dropdown d-none';
        wrap.appendChild(dropdown);

        function getItems() {
            return getCatalog(type);
        }

        function renderDropdown(query) {
            const nq = norm(query);
            dropdown.innerHTML = '';
            const items = getItems().filter(function (item) {
                return !nq || norm(item.nome).includes(nq);
            });

            items.slice(0, 25).forEach(function (item) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'lh-combobox-item';
                btn.textContent = item.nome;
                btn.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    input.value = item.nome;
                    dropdown.classList.add('d-none');
                });
                dropdown.appendChild(btn);
            });

            if (canCreate && query.trim() && !items.some(function (i) { return norm(i.nome) === nq; })) {
                const addBtn = document.createElement('button');
                addBtn.type = 'button';
                addBtn.className = 'lh-combobox-item lh-combobox-add';
                addBtn.innerHTML = '<i class="bi bi-plus-circle me-1"></i> Adicionar "' + escapeHtml(query.trim()) + '"';
                addBtn.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    addBtn.disabled = true;
                    createItem(createType, query.trim())
                        .then(function (item) {
                            input.value = item.nome;
                            dropdown.classList.add('d-none');
                        })
                        .catch(function (err) {
                            alert(err.message || 'Não foi possível cadastrar.');
                            renderDropdown(query);
                        });
                });
                dropdown.appendChild(addBtn);
            }

            if (!dropdown.children.length) {
                dropdown.innerHTML = '<div class="lh-combobox-empty">Digite ou selecione uma opção</div>';
            }
            dropdown.classList.remove('d-none');
        }

        input.addEventListener('focus', function () { renderDropdown(input.value); });
        input.addEventListener('input', function () { renderDropdown(input.value); });
        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) dropdown.classList.add('d-none');
        });
    }

    window.initLabhubComboboxes = function (root) {
        (root || document).querySelectorAll('select[data-lh-combobox]').forEach(enhanceSelect);
        (root || document).querySelectorAll('[data-lh-pick]').forEach(enhancePick);
    };

    document.addEventListener('DOMContentLoaded', function () {
        initLabhubComboboxes();
    });

    document.addEventListener('shown.bs.modal', function (e) {
        initLabhubComboboxes(e.target);
    });
})();
