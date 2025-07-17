document.addEventListener('DOMContentLoaded', () => {
    // --- API YARDIMCI FONKSİYONLARI ---
    const api = {
        get: (endpoint, params = {}) => fetch(new URL(endpoint, `${location.origin}/yemekhane-listesi-main/api/`) + '?' + new URLSearchParams(params)).then(res => res.json()),
        post: (endpoint, data) => fetch(new URL(endpoint, `${location.origin}/yemekhane-listesi-main/api/`), { method: 'POST', body: data }).then(res => res.json())
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

    // --- YARDIM MODALI YÖNETİMİ ---
    const helpModal = document.getElementById('help-modal');
    if (helpModal) {
        const helpBtn = document.getElementById('help-btn');
        const helpModalCloseBtn = helpModal.querySelector('.modal-close');
        const openHelpFromTab = document.getElementById('open-help-from-tab');

        const openHelpModal = () => helpModal.classList.remove('hidden');
        const closeHelpModal = () => helpModal.classList.add('hidden');

        helpBtn?.addEventListener('click', openHelpModal);
        openHelpFromTab?.addEventListener('click', openHelpModal);
        helpModalCloseBtn?.addEventListener('click', closeHelpModal);
        helpModal.addEventListener('click', (e) => {
            if (e.target === helpModal) closeHelpModal();
        });
    }

    // --- CSV YÜKLEME YÖNETİMİ (İKİ AŞAMALI) ---
    const csvUploadForm = document.getElementById('csv-upload-form');
    if (csvUploadForm) {
        const previewContainer = document.getElementById('csv-preview-container');
        const previewList = document.getElementById('csv-preview-list');
        const btnCommit = document.getElementById('btn-commit-csv');
        const btnCancel = document.getElementById('btn-cancel-csv');
        const fileInput = document.getElementById('csv-file');
        const submitButton = csvUploadForm.querySelector('button[type="submit"]');

        // Analiz Aşaması
        csvUploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!fileInput.files || fileInput.files.length === 0) {
                showToast('Lütfen bir dosya seçin.', 'error');
                return;
            }
            
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
                    csvUploadForm.classList.add('hidden');
                } else {
                    showToast(result.message || 'Dosya analizi başarısız.', 'error');
                }
            } catch (error) {
                showToast('Analiz sırasında bir hata oluştu.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Dosyayı Analiz Et';
            }
        });

        // Önizleme Oluşturma Fonksiyonu
        let renderCsvPreview = (data) => {
            previewList.innerHTML = data.map((day, index) => {
                let content;
                if (day.error) {
                    content = `<div class="preview-item error"><strong>Hata:</strong> ${day.error} (Orijinal Tarih: ${day.original_date})</div>`;
                } else if (day.is_special) {
                    const msg = day.meals[0]?.name || 'Mesaj Yok';
                    content = `<div class="preview-item special"><strong>${day.date} (Özel Gün):</strong> <span>${msg}</span></div>`;
                } else {
                    const mealsHtml = day.meals.map((meal, mealIndex) => `
                        <div class="form-group meal-input-group" style="margin-bottom: 5px;">
                            <input type="text" value="${meal.name}" list="meals-datalist" class="meal-autocomplete-input" data-day-index="${index}" data-meal-index="${mealIndex}">
                            ${meal.is_new ? `
                                <span class="new-meal-label">Yeni</span>
                                <button type="button" class="btn-add-details" data-day-index="${index}" data-meal-index="${mealIndex}">Detay Ekle</button>
                            ` : ''}
                        </div>
                    `).join('');
                    content = `<div class="preview-item"><strong>${day.date}:</strong><div class="meals-preview">${mealsHtml}</div></div>`;
                }
                return `<div class="day-preview-card" data-index="${index}">${content}</div>`;
            }).join('');
        };

        // Önizleme listesindeki olayları yönet (event delegation)
        previewList.addEventListener('input', (e) => {
            if (e.target.matches('.meal-autocomplete-input')) {
                const input = e.target;
                const value = input.value.toLowerCase();
                const options = mealsDatalist.querySelectorAll('option');
                let match = false;
                options.forEach(option => {
                    if (option.value.toLowerCase() === value) {
                        match = true;
                    }
                });

                const label = input.nextElementSibling;
                if (match && label && label.matches('.new-meal-label')) {
                    label.nextElementSibling?.remove(); // Detay ekle butonunu sil
                    label.remove(); // Yeni etiketini sil
                }
            }
        });

        previewList.addEventListener('click', (e) => {
            if (e.target.matches('.btn-add-details')) {
                const button = e.target;
                const input = button.previousElementSibling.previousElementSibling;
                const mealName = input.value;
                
                // Mevcut yemek modalını yeniden kullan
                const mealModal = document.getElementById('meal-modal');
                const mealForm = document.getElementById('meal-form');
                
                mealForm.reset();
                mealForm.querySelector('#meal-name').value = mealName;
                mealForm.querySelector('#meal-name').readOnly = true; // Adı değiştirmeyi engelle
                
                // Modalı CSV önizlemesi için özel bir moda sok
                mealModal.dataset.csvTargetInput = `input[data-day-index="${button.dataset.dayIndex}"][data-meal-index="${button.dataset.mealIndex}"]`;
                
                document.getElementById('modal-title-meal').textContent = `"${mealName}" için Detay Ekle`;
                mealModal.classList.remove('hidden');
            }
        });

        // Yemek modalının submit olayını ele al
        const mealForm = document.getElementById('meal-form');
        mealForm.addEventListener('submit', (e) => {
            const mealModal = document.getElementById('meal-modal');
            // Eğer modal CSV önizlemesi için açıldıysa...
            if (mealModal.dataset.csvTargetInput) {
                e.preventDefault(); // Normal form submit'ini engelle
                
                const targetInput = document.querySelector(mealModal.dataset.csvTargetInput);
                if (targetInput) {
                    // Formdaki verileri input'un dataset'ine kaydet
                    const formData = new FormData(mealForm);
                    targetInput.dataset.calories = formData.get('calories');
                    targetInput.dataset.ingredients = formData.get('ingredients');
                    targetInput.dataset.is_vegetarian = formData.get('is_vegetarian') ? 1 : 0;
                    targetInput.dataset.is_gluten_free = formData.get('is_gluten_free') ? 1 : 0;
                    targetInput.dataset.has_allergens = formData.get('has_allergens') ? 1 : 0;
                    
                    showToast(`"${formData.get('name')}" için detaylar geçici olarak kaydedildi.`, 'success');
                }
                
                // Modalı temizle ve kapat
                mealModal.classList.add('hidden');
                mealForm.querySelector('#meal-name').readOnly = false;
                delete mealModal.dataset.csvTargetInput;
            }
            // Eğer normal yemek yönetimi için a��ıldıysa, bu event listener'ın dışındaki
            // orijinal submit olayı (admin.js'in başka bir yerindeki) çalışmaya devam edecek.
        });


        // Onaylama ve Kaydetme Aşaması
        btnCommit.addEventListener('click', async () => {
            btnCommit.disabled = true;
            btnCommit.textContent = 'Kaydediliyor...';

            // Önizleme ekranından güncel veriyi topla
            const dataToCommit = [];
            document.querySelectorAll('.day-preview-card').forEach(card => {
                const index = card.dataset.index;
                const originalData = JSON.parse(JSON.stringify(previewContainer.originalData[index])); // Deep copy
                
                if (!originalData.error) {
                    const mealInputs = card.querySelectorAll(`input[data-day-index="${index}"]`);
                    if (mealInputs.length > 0) {
                        originalData.meals = [];
                        mealInputs.forEach(input => {
                            if (input.value) {
                                // Input'un dataset'inden detayları al
                                const mealDetails = { name: input.value };
                                if (input.dataset.calories) mealDetails.calories = input.dataset.calories;
                                if (input.dataset.ingredients) mealDetails.ingredients = input.dataset.ingredients;
                                if (input.dataset.is_vegetarian) mealDetails.is_vegetarian = input.dataset.is_vegetarian;
                                if (input.dataset.is_gluten_free) mealDetails.is_gluten_free = input.dataset.is_gluten_free;
                                if (input.dataset.has_allergens) mealDetails.has_allergens = input.dataset.has_allergens;
                                originalData.meals.push(mealDetails);
                            }
                        });
                    }
                }
                dataToCommit.push(originalData);
            });

            const formData = new FormData();
            formData.append('action', 'commit');
            formData.append('data', JSON.stringify(dataToCommit));

            try {
                const result = await api.post('upload_csv.php', formData);
                showToast(result.message, result.success ? 'success' : 'error');
                if (result.success) {
                    resetCsvForm();
                    renderWeekView(new Date()); // Takvimi yenile
                    refreshAllMealData(); // Yemek listesini yenile
                }
            } catch (error) {
                showToast('Kaydetme sırasında bir hata oluştu.', 'error');
            } finally {
                btnCommit.disabled = false;
                btnCommit.textContent = 'Onayla ve Menüleri Kaydet';
            }
        });

        // İptal Etme
        const resetCsvForm = () => {
            previewContainer.classList.add('hidden');
            previewList.innerHTML = '';
            csvUploadForm.classList.remove('hidden');
            csvUploadForm.reset();
        };
        btnCancel.addEventListener('click', resetCsvForm);
        
        // Orijinal veriyi saklamak için
        Object.defineProperty(previewContainer, 'originalData', { writable: true });
        const originalRender = renderCsvPreview;
        renderCsvPreview = (data) => {
            previewContainer.originalData = data;
            originalRender(data);
        };
    }

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
