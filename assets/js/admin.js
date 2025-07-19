/**
 * Admin Panel JavaScript v7.0 (Nihai Sürüm - Tam Fonksiyonel)
 * Description: Modüler yapıya tam uyumlu, sadece aktif olan sayfanın kodlarını çalıştıran,
 *              hatalara karşı dayanıklı ve tam işlevsel script.
 */
document.addEventListener('DOMContentLoaded', () => {
    // --- 1. GENEL YARDIMCILAR (API & TOAST) ---
    const api = {
        _fetch: async (url, options) => {
            const response = await fetch(url, options);
            const text = await response.text();
            try {
                const data = JSON.parse(text);
                if (!response.ok) throw new Error(data.message || `Sunucu Hatası: ${response.status}`);
                return data;
            } catch (e) {
                const errorSnippet = text.substring(0, 300).replace(/<[^>]+>/g, '');
                throw new Error(`Sunucudan geçersiz yanıt alındı. Hata başlangıcı: "${errorSnippet}..."`);
            }
        },
        get: (endpoint, params = {}) => api._fetch(`../api/${endpoint}?${new URLSearchParams(params)}`),
        post: (endpoint, formData) => api._fetch(`../api/${endpoint}`, { method: 'POST', body: formData })
    };

    const toastContainer = document.getElementById('toast-container');
    const showToast = (message, type = 'success') => {
        if (!toastContainer) return;
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-times-circle'}"></i> ${message}`;
        toastContainer.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    };

    // --- 2. PAYLAŞILAN FONKSİYONLAR ---
    const mealsDatalist = document.getElementById('meals-datalist');
    const refreshAllMealData = async () => {
        try {
            const meals = await api.get('manage_meal.php', { action: 'get_all' });
            if (mealsDatalist) {
                mealsDatalist.innerHTML = meals.map(m => `<option value="${m.name}"></option>`).join('');
            }
            document.dispatchEvent(new CustomEvent('mealsupdated', { detail: { meals } }));
        } catch (error) { showToast(error.message, 'error'); }
    };

    // --- 3. MODÜL BAŞLATICILARI ---

    const initMenuModule = () => {
        const container = document.querySelector('.tab-content[data-page="menu"]');
        if (!container) return;

        let currentWeekDate = new Date();
        const weekViewListEl = container.querySelector('#week-view-list');
        const dateForm = container.querySelector('#manage-date-form');
        const dateInput = container.querySelector('#menu-date');
        const formTitle = container.querySelector('#form-title');
        const isSpecialDayCheckbox = container.querySelector('#is-special-day');
        const mealInputsContainer = container.querySelector('#meal-inputs-container');
        const specialDayContainer = container.querySelector('#special-day-container');
        const mealSelectList = container.querySelector('#meal-select-list');
        const mealInputTemplate = document.getElementById('meal-input-template');
        const menuDetailsSection = container.querySelector('#menu-details-section');

        const renderWeekView = async (date) => {
            const weekRangeEl = container.querySelector('#week-range');
            if (!weekRangeEl || !weekViewListEl) return;
            weekViewListEl.innerHTML = '<p>Yükleniyor...</p>';
            try {
                const data = await api.get('get_week_overview.php', { date: date.toISOString().split('T')[0] });
                weekRangeEl.textContent = `${data.start_of_week_formatted} - ${data.end_of_week_formatted}`;
                weekViewListEl.innerHTML = data.days.map(d => `
                    <div class="day-card">
                        <div>
                            <div class="date-info">${d.date_formatted}</div>
                            <span class="menu-summary ${d.is_special ? 'special' : ''}">${d.summary}</span>
                        </div>
                        <button class="btn btn-secondary btn-sm btn-edit-day" data-date="${d.date_sql}"><i class="fas fa-pencil-alt"></i> Düzenle</button>
                    </div>`).join('');
            } catch (error) { showToast(error.message, 'error'); }
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
            formTitle.textContent = 'Menü Düzenle';
            dateForm.dataset.hadMenu = 'false';
            menuDetailsSection.classList.add('hidden');
            toggleDateFormFields();
        };

        const loadDateIntoForm = async (dateStr) => {
            resetForm();
            dateInput.value = dateStr;
            formTitle.textContent = `Tarih Yükleniyor...`;
            menuDetailsSection.classList.remove('hidden');
            try {
                const data = await api.get('get_menu_events.php', { date: dateStr });
                formTitle.textContent = `${new Date(dateStr + 'T00:00:00').toLocaleDateString('tr-TR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}`;
                isSpecialDayCheckbox.checked = data.is_special;
                if (data.is_special) {
                    dateForm.querySelector('[name="special_day_message"]').value = data.message;
                } else {
                    if (data.menu?.length > 0) data.menu.forEach(meal => addMealInput(meal.name));
                    else addMealInput();
                }
                dateForm.dataset.hadMenu = !data.is_special && data.menu && data.menu.length > 0;
                toggleDateFormFields();
            } catch (error) { showToast(error.message, 'error'); resetForm(); }
        };

        container.addEventListener('click', e => {
            const button = e.target.closest('button');
            if (!button) return;
            if (button.matches('.btn-edit-day')) loadDateIntoForm(button.dataset.date);
            if (button.id === 'prev-week') { currentWeekDate.setDate(currentWeekDate.getDate() - 7); renderWeekView(currentWeekDate); }
            if (button.id === 'next-week') { currentWeekDate.setDate(currentWeekDate.getDate() + 7); renderWeekView(currentWeekDate); }
            if (button.id === 'btn-add-meal-to-menu') addMealInput();
            if (button.matches('.btn-remove-meal')) button.closest('.meal-input-group').remove();
        });

        dateInput.addEventListener('change', () => dateInput.value && loadDateIntoForm(dateInput.value));
        isSpecialDayCheckbox.addEventListener('change', toggleDateFormFields);

        dateForm.addEventListener('submit', async e => {
            e.preventDefault();
            const submitButton = dateForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            try {
                const result = await api.post('manage_date.php', new FormData(dateForm));
                showToast(result.message, 'success');
                renderWeekView(new Date(dateInput.value + 'T00:00:00'));
            } catch (error) { showToast(error.message, 'error');
            } finally { submitButton.disabled = false; }
        });
        renderWeekView(currentWeekDate);
    };

    const initMealsModule = () => {
        const container = document.querySelector('.tab-content[data-page="meals"]');
        if (!container) return;
        const mealsTableBody = container.querySelector('#meals-table tbody');
        const mealModal = document.getElementById('meal-modal');
        const mealForm = document.getElementById('meal-form');
        const modalTitle = mealModal.querySelector('#modal-title-meal');
        const openModal = (title) => { modalTitle.textContent = title; mealModal.classList.remove('hidden'); };
        const closeModal = () => { mealModal.classList.add('hidden'); mealForm.reset(); };

        container.querySelector('#btn-add-new-meal').addEventListener('click', () => openModal('Yeni Yemek Ekle'));
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
                    await api.post('manage_meal.php', new URLSearchParams({ action: 'delete', id, csrf_token: mealForm.csrf_token.value }));
                    showToast('Yemek başarıyla silindi.', 'success');
                    refreshAllMealData();
                }
            }
        });

        mealForm.addEventListener('submit', async e => {
            e.preventDefault();
            const submitButton = mealForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            const id = mealForm.querySelector('[name="meal_id"]').value;
            const formData = new FormData(mealForm);
            formData.append('action', id ? 'update' : 'create');
            try {
                await api.post('manage_meal.php', formData);
                showToast(`Yemek başarıyla ${id ? 'güncellendi' : 'eklendi'}.`, 'success');
                closeModal();
                refreshAllMealData();
            } catch (error) { showToast(error.message, 'error');
            } finally { submitButton.disabled = false; }
        });

        document.addEventListener('mealsupdated', e => {
            mealsTableBody.innerHTML = e.detail.meals.map(m => `
                <tr>
                    <td>${m.name}</td>
                    <td>${m.calories || 'N/A'}</td>
                    <td class="actions-cell">
                        <button class="btn btn-secondary btn-sm btn-edit" data-id="${m.id}"><i class="fas fa-pencil-alt"></i> Düzenle</button>
                        <button class="btn btn-danger btn-sm btn-delete" data-id="${m.id}"><i class="fas fa-trash-alt"></i> Sil</button>
                    </td>
                </tr>`).join('');
        });
    };

    const initUploadModule = () => {
        const container = document.querySelector('.tab-content[data-page="upload"]');
        if (!container) return;
        const form = container.querySelector('#csv-upload-form');
        const uploadArea = container.querySelector('#csv-upload-area');
        const previewContainer = container.querySelector('#csv-preview-container');
        const fileInput = container.querySelector('#csv-file');
        const previewList = container.querySelector('#csv-preview-list');
        const btnCommit = container.querySelector('#btn-commit-csv');
        const btnCancel = container.querySelector('#btn-cancel-csv');

        const resetView = () => {
            uploadArea.classList.remove('hidden');
            previewContainer.classList.add('hidden');
            form.reset();
        };

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!fileInput.files.length) { showToast('L��tfen bir dosya seçin.', 'error'); return; }
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            const formData = new FormData(form);
            formData.append('action', 'analyze');
            try {
                const result = await api.post('upload_csv.php', formData);
                previewList.innerHTML = result.data.map(day => `
                    <div class="preview-item">
                        <strong>${day.date} ${day.is_special ? '(Özel Gün)' : ''}</strong>
                        <ul>${day.meals.map(m => `<li class="${m.is_new ? 'new-meal' : ''}">${m.name}</li>`).join('')}</ul>
                    </div>`).join('');
                uploadArea.classList.add('hidden');
                previewContainer.classList.remove('hidden');
            } catch (error) { showToast(error.message, 'error');
            } finally { submitButton.disabled = false; }
        });

        btnCommit.addEventListener('click', async () => {
            btnCommit.disabled = true;
            const formData = new FormData();
            formData.append('action', 'commit');
            formData.append('csrf_token', form.csrf_token.value);
            try {
                const result = await api.post('upload_csv.php', formData);
                showToast(result.message, 'success');
                resetView();
                refreshAllMealData();
            } catch (error) { showToast(error.message, 'error');
            } finally { btnCommit.disabled = false; }
        });
        btnCancel.addEventListener('click', resetView);
    };

    const initFeedbackModule = () => {
        const container = document.querySelector('.tab-content[data-page="feedback"]');
        if (!container) return;

        const feedbackContainer = container.querySelector('#feedback-container');
        const searchTermInput = container.querySelector('#search-term');
        const startDateInput = container.querySelector('#start-date');
        const endDateInput = container.querySelector('#end-date');
        const statusButtonGroup = container.querySelector('#status-filter-group');
        const limitSelect = container.querySelector('#limit-select');
        const clearFiltersButton = container.querySelector('#btn-clear-filters');

        let currentPage = 1;
        let debounceTimer;

        const loadFeedback = async (page = 1) => {
            currentPage = page;
            const status = statusButtonGroup.querySelector('.btn.active').dataset.filter;
            const searchTerm = searchTermInput.value;
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            const limit = limitSelect.value;

            feedbackContainer.innerHTML = '<p class="text-center">Yükleniyor...</p>';
            
            try {
                const params = { page, status, search: searchTerm, start_date: startDate, end_date: endDate, limit };
                const result = await api.get('get_feedback.php', params);
                
                let content = '';
                if (result.data.length === 0) {
                    content = '<div class="alert alert-info text-center">Bu filtrelere uygun geri bildirim bulunamadı.</div>';
                } else {
                    content += result.data.map(fb => `
                        <div class="card feedback-item-card shadow-sm" data-status="${fb.status}">
                            <div class="card-header">
                                <div class="user-info">
                                    <strong class="user-name">${fb.name}</strong>
                                    <a href="mailto:${fb.email}" class="user-email text-muted">${fb.email}</a>
                                </div>
                                <div class="rating">${'★'.repeat(fb.rating)}${'☆'.repeat(5 - fb.rating)}</div>
                            </div>
                            <div class="card-body">
                                <p class="comment-text">${fb.comment || '<i>Kullanıcı yorum bırakmadı.</i>'}</p>
                                ${fb.image_path ? `<button class="btn btn-sm btn-outline-secondary feedback-image-link" data-filename="${fb.image_path}"><i class="fas fa-paperclip"></i> Eki Görüntüle</button>` : ''}
                            </div>
                            <div class="card-footer">
                                <div class="footer-info">
                                    <span class="status-badge status-${fb.status}">${fb.status.charAt(0).toUpperCase() + fb.status.slice(1)}</span>
                                    <small class="text-muted ml-2">Tarih: ${fb.created_at_formatted}</small>
                                </div>
                                <div class="footer-actions">
                                    <button class="btn btn-sm btn-outline-primary btn-reply" data-id="${fb.id}" data-email="${fb.email}"><i class="fas fa-reply"></i> Cevapla</button>
                                    ${fb.status === 'yeni' ? `<button class="btn btn-sm btn-outline-success btn-mark-read" data-id="${fb.id}"><i class="fas fa-check"></i> Okundu</button>` : ''}
                                </div>
                            </div>
                        </div>`).join('');
                    
                    if (result.pagination.total_pages > 1) {
                        content += `<nav class="pagination-nav mt-4">${Array.from({ length: result.pagination.total_pages }, (_, i) => i + 1).map(p => `<button class="btn btn-sm ${p === currentPage ? 'btn-primary' : 'btn-outline-secondary'}" data-page="${p}">${p}</button>`).join('')}</nav>`;
                    }
                }
                feedbackContainer.innerHTML = content;
            } catch (error) { 
                showToast(error.message, 'error'); 
                feedbackContainer.innerHTML = '<div class="alert alert-danger text-center">Geri bildirimler yüklenirken bir hata oluştu.</div>';
            }
        };

        const debouncedLoad = () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadFeedback(1), 400); // 400ms gecikme
        };

        [searchTermInput, startDateInput, endDateInput].forEach(input => {
            input.addEventListener('input', debouncedLoad);
        });

        limitSelect.addEventListener('change', () => loadFeedback(1));

        statusButtonGroup.addEventListener('click', e => {
            const button = e.target.closest('button');
            if (button && !button.classList.contains('active')) {
                statusButtonGroup.querySelector('.btn.active').classList.remove('active');
                button.classList.add('active');
                loadFeedback(1);
            }
        });
        
        clearFiltersButton.addEventListener('click', () => {
            searchTermInput.value = '';
            startDateInput.value = '';
            endDateInput.value = '';
            // limitSelect.value = '25'; // Bu satır kaldırıldı
            if (!statusButtonGroup.querySelector('[data-filter="all"]').classList.contains('active')) {
                statusButtonGroup.querySelector('.btn.active').classList.remove('active');
                statusButtonGroup.querySelector('[data-filter="all"]').classList.add('active');
            }
            loadFeedback(1);
        });

        const replyModal = document.getElementById('reply-modal');
        const replyForm = document.getElementById('reply-form');
        const btnUseTemplate = document.getElementById('btn-use-template');
        const imageViewerModal = document.getElementById('image-viewer-modal');
        const imageViewerImg = document.getElementById('image-viewer-img');
        const downloadBtn = document.getElementById('download-btn');
        let currentZoom = 1;

        feedbackContainer.addEventListener('click', async e => {
            const button = e.target.closest('button');
            if (!button) return;

            if (button.dataset.page) {
                loadFeedback(parseInt(button.dataset.page));
                return;
            }

            if (button.matches('.feedback-image-link')) {
                const filename = button.dataset.filename;
                const imageUrl = `../api/view_image.php?file=${filename}`;
                imageViewerImg.src = imageUrl;
                downloadBtn.href = `${imageUrl}&download=true`;
                imageViewerModal.classList.remove('hidden');
                currentZoom = 1;
                imageViewerImg.style.transform = 'scale(1)';
                return;
            }
            
            if (button.matches('.btn-mark-read')) {
                const id = button.dataset.id;
                const card = button.closest('.feedback-item-card');
                button.disabled = true;
                try {
                    await api.post('mark_feedback.php', new URLSearchParams({ id, status: 'okundu', csrf_token: document.querySelector('[name=csrf_token]').value }));
                    showToast('Geri bildirim "okundu" olarak işaretlendi.', 'success');
                    
                    card.dataset.status = 'okundu';
                    const statusBadge = card.querySelector('.status-badge');
                    if(statusBadge) {
                        statusBadge.classList.remove('status-yeni');
                        statusBadge.classList.add('status-okundu');
                        statusBadge.textContent = 'Okundu';
                    }
                    button.remove();
                } catch (error) {
                    showToast(error.message, 'error');
                    button.disabled = false;
                }
                return;
            }

            if (button.matches('.btn-reply')) {
                const card = button.closest('.feedback-item-card');
                const id = button.dataset.id;
                const email = button.dataset.email;
                const name = card.querySelector('.user-name').textContent;

                replyForm.querySelector('#reply-feedback-id').value = id;
                replyForm.querySelector('#reply-feedback-email').value = email;
                replyForm.querySelector('#reply-feedback-name').value = name;
                
                replyModal.classList.remove('hidden');
            }
        });

        // --- Image Viewer Modal Logic ---
        imageViewerModal.querySelector('.modal-close').addEventListener('click', () => imageViewerModal.classList.add('hidden'));
        document.getElementById('zoom-in-btn').addEventListener('click', () => {
            currentZoom = Math.min(currentZoom + 0.2, 3); // Max zoom 3x
            imageViewerImg.style.transform = `scale(${currentZoom})`;
        });
        document.getElementById('zoom-out-btn').addEventListener('click', () => {
            currentZoom = Math.max(currentZoom - 0.2, 0.4); // Min zoom 0.4x
            imageViewerImg.style.transform = `scale(${currentZoom})`;
        });


        replyModal.querySelector('.modal-close').addEventListener('click', () => {
            replyModal.classList.add('hidden');
            replyForm.reset();
        });
        
        btnUseTemplate.addEventListener('click', () => {
            const name = replyForm.querySelector('#reply-feedback-name').value;
            const template = `Sayın ${name},\n\nGeri bildiriminiz için teşekkür ederiz.\n\nKonuyla ilgili olarak gerekli incelemeler yapılmıştır.\n\nİyi günler dileriz,\n${APP_NAME}`;
            replyForm.querySelector('#reply-text').value = template;
        });

        replyForm.addEventListener('submit', async e => {
            e.preventDefault();
            const submitButton = replyForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            try {
                const result = await api.post('reply_feedback.php', new FormData(replyForm));
                showToast(result.message, 'success');
                replyModal.classList.add('hidden');
                replyForm.reset();
                loadFeedback(currentPage); // Listeyi yenile
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                submitButton.disabled = false;
            }
        });

        loadFeedback();
    };

    const initReportsModule = () => {
        const container = document.querySelector('.tab-content[data-page="reports"]');
        if (!container) return;

        const statsContainer = container.querySelector('#general-stats-container');
        const complaintWordsList = container.querySelector('#complaint-words-list');
        const charts = {
            topMeals: container.querySelector('#top-meals-chart'),
            ratings: container.querySelector('#ratings-chart'),
        };
        
        let chartInstances = {};

        const renderChart = (canvas, type, data, options = {}) => {
            if (!canvas) return;
            const key = canvas.id;
            if (chartInstances[key]) {
                chartInstances[key].destroy();
            }
            chartInstances[key] = new Chart(canvas.getContext('2d'), { type, data, options });
        };

        const loadReports = async () => {
            try {
                const result = await api.get('get_report_data.php');
                const { general_stats, top_meals_chart, ratings_chart, complaint_words } = result.data;

                // 1. Genel istatistik kartlarını doldur
                statsContainer.innerHTML = `
                    <div class="stat-card">
                        <div class="icon" style="background-color: #17a2b8;"><i class="fas fa-comments"></i></div>
                        <div class="details">
                            <h4>Toplam Geri Bildirim</h4>
                            <p class="value">${general_stats.total_feedback}</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="icon" style="background-color: #ffc107;"><i class="fas fa-star-half-alt"></i></div>
                        <div class="details">
                            <h4>Ortalama Puan</h4>
                            <p class="value">${general_stats.average_rating}</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="icon" style="background-color: #28a745;"><i class="fas fa-bell"></i></div>
                        <div class="details">
                            <h4>Yeni Geri Bildirimler</h4>
                            <p class="value">${general_stats.new_feedback_count}</p>
                        </div>
                    </div>
                `;

                // 2. Grafikleri çiz
                renderChart(charts.topMeals, 'bar', top_meals_chart, { responsive: true, plugins: { legend: { display: false } } });
                renderChart(charts.ratings, 'pie', ratings_chart, { responsive: true });

                // 3. Şikayet kelimelerini listele
                complaintWordsList.innerHTML = Object.entries(complaint_words)
                    .map(([word, count]) => `<li>${word} <span class="count">${count}</span></li>`)
                    .join('');
                if (complaintWordsList.innerHTML === '') {
                    complaintWordsList.innerHTML = '<p>Düşük puanlı yorum bulunamadı veya yorumlarda anlamlı kelimeler tespit edilemedi.</p>';
                }

            } catch (error) {
                showToast(error.message, 'error');
                container.innerHTML = '<div class="alert alert-danger">Raporlar yüklenirken bir hata oluştu.</div>';
            }
        };

        loadReports();
    };

    const initLogsModule = () => {
        const container = document.querySelector('.tab-content[data-page="logs"]');
        if (!container) return;
        const logsTableBody = container.querySelector('#logs-table tbody');
        if(!logsTableBody) return;
        const loadLogs = async () => {
            try {
                const result = await api.get('get_logs.php');
                logsTableBody.innerHTML = result.data.map(log => `
                    <tr>
                        <td>${log.created_at_formatted}</td>
                        <td>${log.admin_username}</td>
                        <td>${log.ip_address}</td>
                        <td>${log.action}</td>
                        <td>${log.details || ''}</td>
                    </tr>`).join('');
            } catch (error) { showToast(error.message, 'error'); }
        };
        loadLogs();
    };

    const initOfficialsModule = () => {
        const container = document.querySelector('.tab-content[data-page="officials"]');
        if (!container) return;
        const form = container.querySelector('#officials-form');

        const loadSettings = async () => {
            try {
                const result = await api.get('manage_officials.php');
                if (result.success) {
                    for (const [key, value] of Object.entries(result.data)) {
                        const input = form.querySelector(`[name="${key}"]`);
                        if (input) {
                            input.value = value;
                        }
                    }
                }
            } catch (error) {
                showToast(error.message, 'error');
            }
        };

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            try {
                const result = await api.post('manage_officials.php', new FormData(form));
                showToast(result.message, 'success');
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                submitButton.disabled = false;
            }
        });

        loadSettings();
    };

    // --- 4. ANA BAŞLATICI ---
    const initPage = () => {
        const urlParams = new URLSearchParams(window.location.search);
        const page = urlParams.get('page') || 'menu';

        // İlgili modülün HTML içeriğini aktif hale getir
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        const activeContent = document.querySelector(`.tab-content[data-page="${page}"]`);
        if (activeContent) activeContent.classList.add('active');

        // İlgili modülün JS kodunu çalıştır
        if (page === 'menu') initMenuModule();
        if (page === 'meals') initMealsModule();
        if (page === 'upload') initUploadModule();
        if (page === 'feedback') initFeedbackModule();
        if (page === 'reports') initReportsModule();
        if (page === 'logs') initLogsModule();
        if (page === 'officials') initOfficialsModule();

        refreshAllMealData();
    };

    initPage();
});
