document.addEventListener('DOMContentLoaded', () => {
    // --- GENEL YARDIMCILAR ---
    const api = {
        _fetch: async (url, options) => {
            const response = await fetch(url, options);
            if (response.ok) {
                const text = await response.text();
                try { return text ? JSON.parse(text) : {}; } 
                catch (e) { throw new Error("Sunucudan gelen yanıt JSON formatında değil."); }
            }
            const errorData = await response.json().catch(() => ({ message: `Sunucu hatası: ${response.status} ${response.statusText}` }));
            throw new Error(errorData.message || 'Bilinmeyen bir sunucu hatası oluştu.');
        },
        get: (endpoint, params = {}) => api._fetch(`api/${endpoint}?${new URLSearchParams(params)}`),
        post: (endpoint, data) => api._fetch(`api/${endpoint}`, { method: 'POST', body: data })
    };

    const toastContainer = document.getElementById('toast-container');
    const showToast = (message, type = 'success') => {
        if (!toastContainer) return;
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        toastContainer.appendChild(toast);
        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.5s forwards';
            toast.addEventListener('animationend', () => toast.remove());
        }, 4000);
    };

    // --- SEKME YÖNETİMİ ---
    const tabs = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(item => item.classList.remove('active'));
            tab.classList.add('active');
            tabContents.forEach(content => content.classList.remove('active'));
            const activeTabContent = document.getElementById(tab.dataset.tab);
            if (activeTabContent) {
                activeTabContent.classList.add('active');
                document.dispatchEvent(new CustomEvent('tabchanged', { detail: { tabId: tab.dataset.tab } }));
            }
        });
    });

    // --- ORTAK VERİ YÖNETİMİ ---
    const mealsDatalist = document.getElementById('meals-datalist');
    const refreshAllMealData = async () => {
        try {
            const meals = await api.get('manage_meal.php', { action: 'get_all' });
            if (mealsDatalist) {
                mealsDatalist.innerHTML = meals.map(m => `<option value="${m.name}"></option>`).join('');
            }
            document.dispatchEvent(new CustomEvent('mealsupdated', { detail: { meals } }));
        } catch (error) {
            showToast(error.message || 'Yemek verileri alınamadı.', 'error');
        }
    };

    // --- MODÜL: TARİH YÖNETİMİ ---
    const initDateManagement = () => {
        const tab = document.getElementById('tab-date-management');
        if (!tab) return;

        let currentWeekDate = new Date();
        const weekViewListEl = tab.querySelector('#week-view-list');
        const dateForm = tab.querySelector('#manage-date-form');
        const dateInput = tab.querySelector('#menu-date');
        const formTitle = tab.querySelector('#form-title');
        const isSpecialDayCheckbox = tab.querySelector('#is-special-day');
        const mealInputsContainer = tab.querySelector('#meal-inputs-container');
        const specialDayContainer = tab.querySelector('#special-day-container');
        const mealSelectList = tab.querySelector('#meal-select-list');
        const mealInputTemplate = document.getElementById('meal-input-template');

        const renderWeekView = async (date) => {
            const weekRangeEl = tab.querySelector('#week-range');
            if (!weekRangeEl || !weekViewListEl) return;
            try {
                const data = await api.get('get_week_overview.php', { year: date.getFullYear(), month: date.getMonth() + 1, day: date.getDate() });
                weekRangeEl.innerHTML = `${data.start_of_week_formatted}<br>${data.end_of_week_formatted}`;
                weekViewListEl.innerHTML = '';
                data.days.forEach(d => {
                    const dayCard = document.createElement('div');
                    dayCard.className = 'day-card';
                    dayCard.innerHTML = `
                        <div>
                            <div class="date-info"></div>
                            <span class="menu-summary"></span>
                        </div>
                        <button class="btn-edit-day" data-date="${d.date_sql}">Düzenle</button>
                    `;
                    dayCard.querySelector('.date-info').textContent = d.date_formatted;
                    const summarySpan = dayCard.querySelector('.menu-summary');
                    summarySpan.textContent = d.summary;
                    if (d.is_special) summarySpan.classList.add('special');
                    weekViewListEl.appendChild(dayCard);
                });
            } catch (error) {
                showToast(error.message || 'Haftalık görünüm alınamadı.', 'error');
            }
        };

        const toggleDateFormFields = () => {
            mealInputsContainer.classList.toggle('hidden', isSpecialDayCheckbox.checked);
            specialDayContainer.classList.toggle('hidden', !isSpecialDayCheckbox.checked);
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
            dateForm.dataset.hadMenu = 'false';
            toggleDateFormFields();
        };

        const loadDateIntoForm = async (dateStr) => {
            resetForm();
            dateInput.value = dateStr;
            formTitle.textContent = `Tarih Yükleniyor...`;
            try {
                const data = await api.get('get_menu_events.php', { date: dateStr });
                const dateObj = new Date(dateStr + 'T00:00:00');
                formTitle.textContent = `${dateObj.toLocaleDateString('tr-TR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })} Düzenleniyor`;
                isSpecialDayCheckbox.checked = data.is_special;
                if (data.is_special) {
                    dateForm.querySelector('[name="special_day_message"]').value = data.message;
                } else {
                    if (data.menu?.length > 0) data.menu.forEach(meal => addMealInput(meal.name));
                    else addMealInput();
                }
                dateForm.dataset.hadMenu = !data.is_special && data.menu && data.menu.length > 0;
                toggleDateFormFields();
            } catch (error) {
                showToast(error.message || 'Tarih detayı alınamadı.', 'error');
                formTitle.textContent = 'Tarih Seçin veya Oluşturun';
            }
        };

        tab.addEventListener('click', e => {
            if (e.target.matches('.btn-edit-day')) loadDateIntoForm(e.target.dataset.date);
            if (e.target.matches('#prev-week')) { currentWeekDate.setDate(currentWeekDate.getDate() - 7); renderWeekView(currentWeekDate); }
            if (e.target.matches('#next-week')) { currentWeekDate.setDate(currentWeekDate.getDate() + 7); renderWeekView(currentWeekDate); }
            if (e.target.matches('#btn-add-meal-to-menu')) addMealInput();
            if (e.target.matches('.btn-remove-meal')) e.target.closest('.meal-input-group').remove();
        });
        dateInput.addEventListener('change', () => dateInput.value && loadDateIntoForm(dateInput.value));
        isSpecialDayCheckbox.addEventListener('change', toggleDateFormFields);

        dateForm.addEventListener('submit', async e => {
            e.preventDefault();
            const mealInputs = mealSelectList.querySelectorAll('input[name="meal_names[]"]');
            const hasMeals = Array.from(mealInputs).some(input => input.value.trim() !== '');
            if (dateForm.dataset.hadMenu === 'true' && !hasMeals && !isSpecialDayCheckbox.checked) {
                if (!confirm('Bu tarihe ait tüm menüyü silmek istediğinizden emin misiniz?')) return;
            }
            const submitButton = dateForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Kaydediliyor...';
            try {
                const result = await api.post('manage_date.php', new FormData(dateForm));
                showToast(result.message, result.success ? 'success' : 'error');
                if (result.success) renderWeekView(new Date(dateInput.value + 'T00:00:00'));
            } catch (error) {
                showToast(error.message || 'Tarih kaydedilemedi.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Değişiklikleri Kaydet';
            }
        });

        renderWeekView(currentWeekDate);
        toggleDateFormFields();
    };

    // --- MODÜL: YEMEK YÖNETİMİ ---
    const initMealManagement = () => {
        const tab = document.getElementById('tab-meal-management');
        if (!tab) return;
        const mealsTableBody = tab.querySelector('#meals-table tbody');
        const mealModal = document.getElementById('meal-modal');
        const mealForm = document.getElementById('meal-form');
        const modalTitle = mealModal.querySelector('#modal-title-meal');
        const openModal = (title) => { modalTitle.textContent = title; mealModal.classList.remove('hidden'); };
        const closeModal = () => { mealModal.classList.add('hidden'); mealForm.reset(); mealForm.querySelector('[name="meal_id"]').value = ''; };

        tab.querySelector('#btn-add-new-meal').addEventListener('click', () => openModal('Yeni Yemek Ekle'));
        mealModal.querySelector('.modal-close').addEventListener('click', closeModal);

        mealsTableBody.addEventListener('click', async e => {
            const button = e.target.closest('button');
            if (!button) return;
            const id = button.dataset.id;
            if (button.matches('.btn-edit')) {
                const meal = await api.get('manage_meal.php', { action: 'get_single', id });
                Object.keys(meal).forEach(key => {
                    const input = mealForm.querySelector(`[name="${key}"]`);
                    if (input) {
                        if (input.type === 'checkbox') input.checked = !!parseInt(meal[key], 10);
                        else input.value = meal[key];
                    }
                });
                mealForm.querySelector('[name="meal_id"]').value = meal.id;
                openModal('Yemek Düzenle');
            }
            if (button.matches('.btn-delete')) {
                if (confirm('Bu yemeği silmek istediğinizden emin misiniz?')) {
                    const result = await api.post('manage_meal.php', new URLSearchParams({ action: 'delete', id }));
                    showToast(result.message, result.success ? 'success' : 'error');
                    if (result.success) refreshAllMealData();
                }
            }
        });

        mealForm.addEventListener('submit', async e => {
            e.preventDefault();
            const submitButton = mealForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Kaydediliyor...';
            const id = mealForm.querySelector('[name="meal_id"]').value;
            const formData = new FormData(mealForm);
            formData.append('action', id ? 'update' : 'create');
            try {
                const result = await api.post('manage_meal.php', formData);
                showToast(result.message, result.success ? 'success' : 'error');
                if (result.success) { closeModal(); refreshAllMealData(); }
            } catch (error) {
                showToast(error.message || 'Yemek kaydedilemedi.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Kaydet';
            }
        });

        document.addEventListener('mealsupdated', e => {
            const meals = e.detail.meals;
            if (mealsTableBody) {
                mealsTableBody.innerHTML = meals.map(m => `
                    <tr>
                        <td data-label="Yemek Adı">
                            ${m.name}
                            <div class="diet-icons">
                                ${m.is_vegetarian == 1 ? '<span title="Vejetaryen">🥬</span>' : ''}
                                ${m.is_gluten_free == 1 ? '<span title="Glütensiz">🚫🌾</span>' : ''}
                                ${m.has_allergens == 1 ? '<span title="Alerjen İçerir">⚠️</span>' : ''}
                            </div>
                        </td>
                        <td data-label="Kalori">${m.calories || 'N/A'}</td>
                        <td data-label="İşlemler" class="actions-cell">
                            <button class="btn-edit" data-id="${m.id}">Düzenle</button>
                            <button class="btn-delete" data-id="${m.id}">Sil</button>
                        </td>
                    </tr>
                `).join('');
            }
        });
    };

    // --- MODÜL: CSV YÜKLEME ---
    const initCsvUploader = () => {
        const tab = document.getElementById('tab-excel-import');
        if (!tab) return;
        const form = tab.querySelector('#csv-upload-form');
        const previewContainer = tab.querySelector('#csv-preview-container');
        const previewList = tab.querySelector('#csv-preview-list');
        const btnCommit = tab.querySelector('#btn-commit-csv');
        const btnCancel = tab.querySelector('#btn-cancel-csv');
        const fileInput = tab.querySelector('#csv-file');
        const submitButton = form.querySelector('button[type="submit"]');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!fileInput.files.length) { showToast('Lütfen bir dosya seçin.', 'error'); return; }
            submitButton.disabled = true;
            submitButton.textContent = 'Analiz Ediliyor...';
            const formData = new FormData();
            formData.append('csv_file', fileInput.files[0]);
            formData.append('action', 'analyze');
            try {
                const result = await api.post('upload_csv.php', formData);
                if (result.success) {
                    renderCsvPreview(result.data);
                    previewContainer.classList.remove('hidden');
                    form.classList.add('hidden');
                } else {
                    showToast(result.message || 'Dosya analizi başarısız.', 'error');
                }
            } catch (error) {
                showToast(error.message || 'Analiz sırasında hata oluştu.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Dosyayı Analiz Et';
            }
        });

        const renderCsvPreview = (data) => {
            previewContainer.originalData = data;
            let hasErrors = data.some(day => day.error);
            previewList.innerHTML = data.map((day, dayIndex) => {
                if (day.error) return `<div class="preview-item error"><strong>Hata:</strong> ${day.error} (Orijinal Satır: ${day.original_date})</div>`;
                const overwriteWarning = day.exists ? `<div class="overwrite-warning">⚠️ Bu tarihe ait mevcut menü üzerine yazılacak.</div>` : '';
                if (day.is_special) return `<div class="preview-item special"><strong>${day.date} (Özel Gün):</strong>${overwriteWarning} <span>${day.meals[0]?.name || 'Mesaj Yok'}</span></div>`;
                const mealsHtml = day.meals.map((meal, mealIndex) => `
                    <div class="form-group meal-input-group" style="margin-bottom: 5px;">
                        <input type="text" value="${meal.name}" list="meals-datalist" class="meal-autocomplete-input" data-day-index="${dayIndex}" data-meal-index="${mealIndex}">
                        ${meal.is_new ? `<span class="new-meal-label">Yeni</span><button type="button" class="btn-add-details" data-day-index="${dayIndex}" data-meal-index="${mealIndex}">Detay Ekle</button>` : ''}
                    </div>`).join('');
                return `<div class="preview-item"><strong>${day.date}:</strong>${overwriteWarning}<div class="meals-preview">${mealsHtml}</div></div>`;
            }).join('');
            btnCommit.disabled = hasErrors;
            btnCommit.textContent = hasErrors ? 'Hatalı Satırlar Var, Tekrar Yükleyin' : 'Onayla ve Menüleri Kaydet';
        };
        
        previewList.addEventListener('click', e => {
            if (e.target.matches('.btn-add-details')) {
                const { dayIndex, mealIndex } = e.target.dataset;
                const mealName = previewContainer.originalData[dayIndex].meals[mealIndex].name;
                const mealModal = document.getElementById('meal-modal');
                const mealForm = document.getElementById('meal-form');
                mealForm.reset();
                mealForm.querySelector('#meal-name').value = mealName;
                mealForm.querySelector('#meal-name').readOnly = true;
                mealModal.dataset.csvTarget = `[data-day-index="${dayIndex}"][data-meal-index="${mealIndex}"]`;
                document.getElementById('modal-title-meal').textContent = `"${mealName}" için Detay Ekle`;
                mealModal.classList.remove('hidden');
            }
        });

        btnCommit.addEventListener('click', async () => {
            btnCommit.disabled = true;
            btnCommit.textContent = 'Kaydediliyor...';
            const formData = new FormData();
            formData.append('action', 'commit');
            formData.append('data', JSON.stringify(previewContainer.originalData));
            try {
                const result = await api.post('upload_csv.php', formData);
                showToast(result.message, result.success ? 'success' : 'error');
                if (result.success) {
                    resetCsvForm();
                    refreshAllMealData();
                }
            } catch (error) {
                showToast(error.message || 'Kaydetme sırasında hata oluştu.', 'error');
            } finally {
                btnCommit.disabled = false;
                btnCommit.textContent = 'Onayla ve Menüleri Kaydet';
            }
        });

        const resetCsvForm = () => {
            previewContainer.classList.add('hidden');
            previewList.innerHTML = '';
            form.classList.remove('hidden');
            form.reset();
        };
        btnCancel.addEventListener('click', resetCsvForm);
    };

    // --- MODÜL: LOG GÖRÜNTÜLEYİCİ ---
    const initLogViewer = () => {
        const tab = document.getElementById('tab-logs');
        if (!tab) return;
        let logsLoaded = false;
        const logsTableBody = tab.querySelector('#logs-table tbody');
        const loadLogs = async () => {
            if (logsLoaded || !logsTableBody) return;
            logsTableBody.innerHTML = '<tr><td colspan="6">Yükleniyor...</td></tr>';
            try {
                const result = await api.get('get_logs.php');
                if (result.success) {
                    logsTableBody.innerHTML = result.data.map(log => `<tr><td data-label="Tarih">${log.created_at_formatted}</td><td data-label="Yönetici">${log.admin_username}</td><td data-label="IP Adresi">${log.ip_address}</td><td data-label="Eylem Türü"><span class="log-action-type">${log.action_type}</span></td><td data-label="Özet">${log.action_summary}</td><td data-label="Detaylar">${log.details}</td></tr>`).join('');
                } else {
                    logsTableBody.innerHTML = `<tr><td colspan="6">${result.message}</td></tr>`;
                }
                logsLoaded = true;
            } catch (error) {
                logsTableBody.innerHTML = `<tr><td colspan="6">${error.message || 'Kayıtlar yüklenemedi.'}</td></tr>`;
            }
        };
        document.addEventListener('tabchanged', e => { if (e.detail.tabId === 'tab-logs') loadLogs(); });
    };

    // --- MODÜL: RAPORLAMA ---
    const initReports = () => {
        const tab = document.getElementById('tab-reports');
        if (!tab) return;
        let topMealsChart = null;
        const loadReports = async () => {
            try {
                const result = await api.get('get_report_data.php', { type: 'top_meals' });
                if (result.success) {
                    const ctx = document.getElementById('top-meals-chart').getContext('2d');
                    if (topMealsChart) topMealsChart.destroy();
                    topMealsChart = new Chart(ctx, {
                        type: 'bar', data: result.data,
                        options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }, responsive: true, plugins: { legend: { display: false } } }
                    });
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast(error.message || 'Rapor verileri yüklenemedi.', 'error');
            }
        };
        document.addEventListener('tabchanged', e => { if (e.detail.tabId === 'tab-reports') loadReports(); });
    };
    
    // --- MODÜL: YARDIM ---
    const initHelpModal = () => {
        const helpModal = document.getElementById('help-modal');
        if (!helpModal) return;
        const openHelpModal = () => helpModal.classList.remove('hidden');
        const closeHelpModal = () => helpModal.classList.add('hidden');
        document.getElementById('help-btn')?.addEventListener('click', openHelpModal);
        document.getElementById('open-help-from-tab')?.addEventListener('click', openHelpModal);
        helpModal.querySelector('.modal-close')?.addEventListener('click', closeHelpModal);
        helpModal.addEventListener('click', e => { if (e.target === helpModal) closeHelpModal(); });
    };

    // --- UYGULAMAYI BAŞLAT ---
    initDateManagement();
    initMealManagement();
    initCsvUploader();
    initLogViewer();
    initReports();
    initHelpModal();
    refreshAllMealData();
});
