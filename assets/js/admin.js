document.addEventListener('DOMContentLoaded', () => {
    // --- API YARDIMCI FONKSİYONLARI ---
    const api = {
        get: (endpoint, params = {}) => fetch(new URL(endpoint, `${location.origin}/yemekhane-listesi/api/`) + '?' + new URLSearchParams(params)).then(res => res.json()),
        post: (endpoint, data) => fetch(new URL(endpoint, `${location.origin}/yemekhane-listesi/api/`), { method: 'POST', body: data }).then(res => res.json())
    };

    // --- BİLDİRİM (TOAST) FONKSİYONU ---
    const toastContainer = document.getElementById('toast-container');
    const showToast = (message, type = 'success') => {
        if (!toastContainer) return;
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        toastContainer.appendChild(toast);
        setTimeout(() => toast.remove(), 4500);
    };

    // --- SEKME YÖNETİMİ ---
    const tabs = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(item => item.classList.remove('active'));
            tab.classList.add('active');
            tabContents.forEach(content => content.classList.remove('active'));
            document.getElementById(tab.dataset.tab)?.classList.add('active');
        });
    });

    // --- ORTAK VERİ YÖNETİMİ ---
    const mealsDatalist = document.getElementById('meals-datalist');
    const mealsTableBody = document.querySelector('#meals-table tbody');
    const refreshAllMealData = async () => {
        const meals = await api.get('manage_meal.php', { action: 'get_all' });
        if (mealsTableBody) {
            mealsTableBody.innerHTML = meals.map(m => `<tr><td>${m.name}</td><td>${m.calories||'N/A'}</td><td><button class="btn-edit" data-id="${m.id}">Düzenle</button><button class="btn-delete" data-id="${m.id}">Sil</button></td></tr>`).join('');
        }
        if (mealsDatalist) {
            mealsDatalist.innerHTML = meals.map(m => `<option value="${m.name}"></option>`).join('');
        }
    };

    // --- TARİH YÖNETİMİ BÖLÜMÜ ---
    const dateManagementTab = document.getElementById('tab-date-management');
    if (dateManagementTab) {
        let currentWeekDate = new Date();
        const weekRangeEl = document.getElementById('week-range');
        const weekViewListEl = document.getElementById('week-view-list');
        const dateForm = document.getElementById('manage-date-form');
        const dateInput = document.getElementById('menu-date');
        const formTitle = document.getElementById('form-title');
        const isSpecialDayCheckbox = document.getElementById('is-special-day');
        const mealInputsContainer = document.getElementById('meal-inputs-container');
        const specialDayContainer = document.getElementById('special-day-container');
        const mealSelectList = document.getElementById('meal-select-list');
        const mealInputTemplate = document.getElementById('meal-input-template');

        const renderWeekView = async (date) => {
            const data = await api.get('get_week_overview.php', { year: date.getFullYear(), month: date.getMonth() + 1, day: date.getDate() });
            weekRangeEl.innerHTML = `${data.start_of_week_formatted}<br>${data.end_of_week_formatted}`;
            weekViewListEl.innerHTML = data.days.map(d => `<div class="day-card"><div><div class="date-info">${d.date_formatted}</div><span class="menu-summary ${d.is_special ? 'special' : ''}">${d.summary}</span></div><button class="btn-edit-day" data-date="${d.date_sql}">Düzenle</button></div>`).join('');
        };

        const toggleDateFormFields = () => {
            if (isSpecialDayCheckbox.checked) {
                mealInputsContainer.classList.add('hidden');
                specialDayContainer.classList.remove('hidden');
            } else {
                mealInputsContainer.classList.remove('hidden');
                specialDayContainer.classList.add('hidden');
            }
        };
        
        const addMealInput = (value = '') => {
            const clone = mealInputTemplate.content.cloneNode(true);
            clone.querySelector('input').value = value;
            mealSelectList.appendChild(clone);
        };

        const resetForm = () => {
            dateForm.reset();
            mealSelectList.innerHTML = '';
            formTitle.textContent = 'Tarih Seçin veya Oluşturun';
            toggleDateFormFields();
        };

        const loadDateIntoForm = async (dateStr) => {
            resetForm();
            dateInput.value = dateStr;
            const dateObj = new Date(dateStr + 'T00:00:00');
            formTitle.textContent = `${dateObj.toLocaleDateString('tr-TR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })} Düzenleniyor`;
            const data = await api.get('get_menu_events.php', { date: dateStr });
            isSpecialDayCheckbox.checked = data.is_special;
            if (data.is_special) {
                dateForm.querySelector('[name="special_day_message"]').value = data.message;
            } else {
                if (data.menu?.length > 0) data.menu.forEach(meal => addMealInput(meal.name)); else addMealInput();
            }
            toggleDateFormFields();
        };

        document.getElementById('prev-week').addEventListener('click', () => { currentWeekDate.setDate(currentWeekDate.getDate() - 7); renderWeekView(currentWeekDate); });
        document.getElementById('next-week').addEventListener('click', () => { currentWeekDate.setDate(currentWeekDate.getDate() + 7); renderWeekView(currentWeekDate); });
        weekViewListEl.addEventListener('click', e => e.target.matches('.btn-edit-day') && loadDateIntoForm(e.target.dataset.date));
        dateInput.addEventListener('change', () => dateInput.value && loadDateIntoForm(dateInput.value));
        isSpecialDayCheckbox.addEventListener('change', toggleDateFormFields);
        document.getElementById('btn-add-meal-to-menu').addEventListener('click', () => addMealInput());
        mealSelectList.addEventListener('click', e => e.target.matches('.btn-remove-meal') && e.target.closest('.meal-input-group').remove());
        
        dateForm.addEventListener('submit', async e => {
            e.preventDefault();
            const result = await api.post('manage_date.php', new FormData(dateForm));
            showToast(result.message, result.success ? 'success' : 'error');
            if (result.success) renderWeekView(new Date(dateInput.value + 'T00:00:00'));
        });

        renderWeekView(currentWeekDate);
        toggleDateFormFields();
    }

    // --- YEMEK YÖNETİMİ BÖLÜMÜ ---
    const mealManagementTab = document.getElementById('tab-meal-management');
    if (mealManagementTab) {
        const mealModal = document.getElementById('meal-modal');
        const mealForm = document.getElementById('meal-form');
        const modalTitle = document.getElementById('modal-title-meal');

        const openModal = (title) => { modalTitle.textContent = title; mealModal.classList.remove('hidden'); };
        const closeModal = () => { mealModal.classList.add('hidden'); mealForm.reset(); mealForm.querySelector('[name="meal_id"]').value = ''; };

        document.getElementById('btn-add-new-meal').addEventListener('click', () => openModal('Yeni Yemek Ekle'));
        mealModal.querySelector('.modal-close').addEventListener('click', closeModal);

        mealsTableBody.addEventListener('click', async e => {
            const id = e.target.dataset.id;
            if (e.target.matches('.btn-edit')) {
                const meal = await api.get('manage_meal.php', { action: 'get_single', id });
                // Form alanlarını doldur
                Object.keys(meal).forEach(key => {
                    const input = mealForm.querySelector(`[name="${key}"]`);
                    if (input) {
                        if (input.type === 'checkbox') {
                            // Gelen değer 1, '1', veya true ise işaretle
                            input.checked = !!parseInt(meal[key], 10);
                        } else {
                            input.value = meal[key];
                        }
                    }
                });
                mealForm.querySelector('[name="meal_id"]').value = meal.id;
                openModal('Yemek Düzenle');
            }
            if (e.target.matches('.btn-delete')) {
                if (confirm('Bu yemeği silmek istediğinizden emin misiniz?')) {
                    const result = await api.post('manage_meal.php', new URLSearchParams({ action: 'delete', id }));
                    showToast(result.message, result.success ? 'success' : 'error');
                    if (result.success) refreshAllMealData();
                }
            }
        });

        mealForm.addEventListener('submit', async e => {
            e.preventDefault();
            const id = mealForm.querySelector('[name="meal_id"]').value;
            const formData = new FormData(mealForm);
            formData.append('action', id ? 'update' : 'create');
            const result = await api.post('manage_meal.php', formData);
            showToast(result.message, result.success ? 'success' : 'error');
            if (result.success) {
                closeModal();
                refreshAllMealData();
            }
        });
    }

    refreshAllMealData();

    // --- İŞLEM KAYITLARI BÖLÜMÜ ---
    const logsTab = document.querySelector('.tab-link[data-tab="tab-logs"]');
    if (logsTab) {
        const logsTableBody = document.querySelector('#logs-table tbody');
        let logsLoaded = false;

        const loadLogs = async () => {
            if (!logsTableBody) return;
            logsTableBody.innerHTML = '<tr><td colspan="4">Yükleniyor...</td></tr>';
            const result = await api.get('get_logs.php');
            if (result.success) {
                logsTableBody.innerHTML = result.data.map(log => `
                    <tr>
                        <td>${log.created_at_formatted}</td>
                        <td>${log.admin_username}</td>
                        <td>${log.action}</td>
                        <td>${log.details}</td>
                    </tr>
                `).join('');
            } else {
                logsTableBody.innerHTML = `<tr><td colspan="4">${result.message}</td></tr>`;
            }
            logsLoaded = true;
        };

        logsTab.addEventListener('click', () => {
            if (!logsLoaded) {
                loadLogs();
            }
        });
    }
});
