/**
 * Admin Panel JavaScript v7.1 (Tüm Düzeltmeler Uygulandı)
 */
document.addEventListener('DOMContentLoaded', () => {
    // --- 1. GENEL YARDIMCILAR (API & TOAST) ---
    const api = {
        _fetch: async (url, options) => {
            const response = await fetch(url, options);
            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                // JSON parse hatası olduysa, sunucudan düz metin bir hata mesajı gelmiş olabilir.
                console.error("JSON Parse Error. Response text:", text);
                const errorSnippet = text.substring(0, 300).replace(/<[^>]+>/g, ''); // HTML taglarını temizle
                throw new Error(`Sunucudan geçersiz yanıt alındı. Hata başlangıcı: "${errorSnippet}..."`);
            }

            if (!response.ok) {
                // Sunucu HTTP hata kodu döndürdüyse (4xx, 5xx), JSON içindeki mesajı kullan.
                throw new Error(data.message || `Sunucu Hatası: ${response.status}`);
            }

            return data; // Başarılı yanıt
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
        // Menü modülü kodu... (Mevcut haliyle doğru çalışıyor)
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
        // Yemekler modülü kodu... (Mevcut haliyle doğru çalışıyor)
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
        // Yükleme modülü kodu... (Mevcut haliyle doğru çalışıyor)
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
            if (!fileInput.files.length) { showToast('Lütfen bir dosya seçin.', 'error'); return; }
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
        const csrfTokenEl = container.querySelector('[name=csrf_token]');
        if (!csrfTokenEl) return; // CSRF token yoksa devam etme
        const csrfToken = csrfTokenEl.value;

        let currentPage = 1;
        let debounceTimer;

        const getActionButtons = (fb) => {
            let buttons = '';
            const btnReply = `<button class="btn btn-sm btn-outline-primary btn-reply" data-id="${fb.id}" data-email="${fb.email}"><i class="fas fa-reply"></i> Cevapla</button>`;
            const btnArchive = `<button class="btn btn-sm btn-outline-secondary btn-archive" data-id="${fb.id}"><i class="fas fa-archive"></i> Arşivle</button>`;
            const btnUnarchive = `<button class="btn btn-sm btn-outline-secondary btn-unarchive" data-id="${fb.id}"><i class="fas fa-box-open"></i> Arşivden Çıkar</button>`;
            const btnMarkRead = `<button class="btn btn-sm btn-outline-success btn-mark-read" data-id="${fb.id}"><i class="fas fa-check"></i> Okundu</button>`;

            switch (fb.status) {
                case 'yeni': buttons = `${btnMarkRead} ${btnReply} ${btnArchive}`; break;
                case 'okundu': buttons = `${btnReply} ${btnArchive}`; break;
                case 'cevaplandı': buttons = `${btnArchive}`; break;
                case 'arsivlendi': buttons = `${btnUnarchive}`; break;
            }
            return buttons;
        };

        const loadFeedback = async (page = 1) => {
            currentPage = page;
            const params = {
                page,
                status: statusButtonGroup.querySelector('.btn.active').dataset.filter,
                search: searchTermInput.value,
                start_date: startDateInput.value,
                end_date: endDateInput.value,
                limit: limitSelect.value
            };
            feedbackContainer.innerHTML = '<p class="text-center">Yükleniyor...</p>';
            try {
                const result = await api.get('get_feedback.php', params);
                if (result.data.length === 0) {
                    feedbackContainer.innerHTML = '<div class="alert alert-info text-center">Bu filtrelere uygun geri bildirim bulunamadı.</div>';
                    return;
                }
                let content = result.data.map(fb => `
                    <div class="card feedback-item-card shadow-sm" data-id="${fb.id}" data-status="${fb.status}">
                        <div class="card-header">
                            <div class="user-info"><strong class="user-name">${fb.name}</strong><a href="mailto:${fb.email}" class="user-email text-muted">${fb.email}</a></div>
                            <div class="rating">${'★'.repeat(fb.rating)}${'☆'.repeat(5 - fb.rating)}</div>
                        </div>
                        <div class="card-body">
                            <p class="comment-text">${fb.comment || '<i>Yorum yok.</i>'}</p>
                            ${fb.image_path ? `<button class="btn btn-sm btn-outline-secondary feedback-image-link" data-filename="${fb.image_path}"><i class="fas fa-paperclip"></i> Eki Görüntüle</button>` : ''}
                        </div>
                        <div class="card-footer">
                            <div class="footer-info">
                                <span class="status-badge status-${fb.status}">${fb.status.charAt(0).toUpperCase() + fb.status.slice(1)}</span>
                                <small class="text-muted ml-2">Tarih: ${fb.created_at_formatted}</small>
                                ${fb.replied_by_username ? `<small class="text-muted ml-2">Cevaplayan: ${fb.replied_by_username}</small>` : ''}
                            </div>
                            <div class="footer-actions">${getActionButtons(fb)}</div>
                        </div>
                    </div>`).join('');
                if (result.pagination.total_pages > 1) {
                    content += `<nav class="pagination-nav mt-4">${Array.from({ length: result.pagination.total_pages }, (_, i) => i + 1).map(p => `<button class="btn btn-sm ${p === currentPage ? 'btn-primary' : 'btn-outline-secondary'}" data-page="${p}">${p}</button>`).join('')}</nav>`;
                }
                feedbackContainer.innerHTML = content;
            } catch (error) {
                showToast(error.message, 'error');
                console.error('Geri bildirim yükleme hatası:', error);
                feedbackContainer.innerHTML = '<div class="alert alert-danger text-center">Geri bildirimler yüklenirken bir hata oluştu.</div>';
            }
        };

        const debouncedLoad = () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => loadFeedback(1), 400); };
        [searchTermInput, startDateInput, endDateInput].forEach(input => input.addEventListener('input', debouncedLoad));
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
            searchTermInput.value = ''; startDateInput.value = ''; endDateInput.value = '';
            if (!statusButtonGroup.querySelector('[data-filter="all"]').classList.contains('active')) {
                statusButtonGroup.querySelector('.btn.active').classList.remove('active');
                statusButtonGroup.querySelector('[data-filter="all"]').classList.add('active');
            }
            loadFeedback(1);
        });

        const replyModal = document.getElementById('reply-modal');
        const replyForm = document.getElementById('reply-form');
        const imageViewerModal = document.getElementById('image-viewer-modal');
        const imageViewerImg = document.getElementById('image-viewer-img');
        const downloadBtn = document.getElementById('download-btn');
        
        // Görüntüleyici için durum değişkenleri
        let currentZoom = 1;
        let isDragging = false;
        let startX, startY;
        let translateX = 0, translateY = 0;

        const applyTransform = () => {
            imageViewerImg.style.transform = `scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
        };

        const handleFeedbackAction = async (button, id, action) => {
            button.disabled = true;
            try {
                const result = await api.post('mark_feedback.php', new URLSearchParams({ id, action, csrf_token: csrfToken }));
                showToast(result.message, 'success');
                const card = button.closest('.feedback-item-card');
                const updatedFeedback = result.feedback;
                card.dataset.status = updatedFeedback.status;
                const statusBadge = card.querySelector('.status-badge');
                statusBadge.className = `status-badge status-${updatedFeedback.status}`;
                statusBadge.textContent = updatedFeedback.status.charAt(0).toUpperCase() + updatedFeedback.status.slice(1);
                card.querySelector('.footer-actions').innerHTML = getActionButtons(updatedFeedback);
            } catch (error) {
                showToast(error.message, 'error');
                button.disabled = false;
            }
        };

        feedbackContainer.addEventListener('click', async e => {
            const button = e.target.closest('button');
            if (!button) return;
            const id = button.dataset.id;
            if (button.dataset.page) { loadFeedback(parseInt(button.dataset.page)); return; }
            if (button.matches('.feedback-image-link')) {
                const filename = button.dataset.filename;
                const imageUrl = `../api/view_image.php?file=${filename}`;
                imageViewerImg.src = imageUrl;
                downloadBtn.href = `${imageUrl}&download=true`;
                downloadBtn.setAttribute('download', filename);
                imageViewerModal.classList.remove('hidden');
                // Görüntüleyiciyi sıfırla
                currentZoom = 1;
                translateX = 0;
                translateY = 0;
                applyTransform();
                return;
            }
            if (button.matches('.btn-mark-read')) { handleFeedbackAction(button, id, 'mark_read'); return; }
            if (button.matches('.btn-archive')) { handleFeedbackAction(button, id, 'archive'); return; }
            if (button.matches('.btn-unarchive')) { handleFeedbackAction(button, id, 'unarchive'); return; }
            if (button.matches('.btn-reply')) {
                const card = button.closest('.feedback-item-card');
                replyForm.querySelector('#reply-feedback-id').value = id;
                replyForm.querySelector('#reply-feedback-email').value = button.dataset.email;
                replyForm.querySelector('#reply-feedback-name').value = card.querySelector('.user-name').textContent;
                replyModal.classList.remove('hidden');
            }
        });

        // --- Image Viewer Modal Logic (Sürükleme ve Zoom) ---
        imageViewerModal.querySelector('.modal-close').addEventListener('click', () => imageViewerModal.classList.add('hidden'));
        
        document.getElementById('zoom-in-btn').addEventListener('click', () => {
            currentZoom = Math.min(currentZoom + 0.2, 3);
            applyTransform();
        });
        
        document.getElementById('zoom-out-btn').addEventListener('click', () => {
            currentZoom = Math.max(currentZoom - 0.2, 0.4);
            if (currentZoom <= 1) { // Eğer normal boyuta veya daha küçüğe dönerse, sürüklemeyi sıfırla
                translateX = 0;
                translateY = 0;
            }
            applyTransform();
        });

        imageViewerImg.addEventListener('mousedown', (e) => {
            if (currentZoom <= 1) return; // Sadece büyütülmüşse sürükle
            e.preventDefault();
            isDragging = true;
            startX = e.clientX - translateX;
            startY = e.clientY - translateY;
            imageViewerImg.classList.add('is-dragging');
        });

        window.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            e.preventDefault();
            translateX = e.clientX - startX;
            translateY = e.clientY - startY;
            applyTransform();
        });

        window.addEventListener('mouseup', () => {
            if (!isDragging) return;
            isDragging = false;
            imageViewerImg.classList.remove('is-dragging');
        });
        
        replyModal.querySelector('.modal-close').addEventListener('click', () => { replyModal.classList.add('hidden'); replyForm.reset(); });
        document.getElementById('btn-use-template').addEventListener('click', () => {
            const name = replyForm.querySelector('#reply-feedback-name').value;
            replyForm.querySelector('#reply-text').value = `Sayın ${name},\n\nGeri bildiriminiz için teşekkür ederiz.\n\nKonuyla ilgili olarak gerekli incelemeler yapılmıştır.\n\nİyi günler dileriz,\nAkdeniz Üniversitesi`;
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
                loadFeedback(currentPage);
            } catch (error) { showToast(error.message, 'error');
            } finally { submitButton.disabled = false; }
        });

        loadFeedback();
    };

    const initReportsModule = () => {
        const container = document.querySelector('.tab-content[data-page="reports"]');
        if (!container) return;
        // Raporlar modülü kodu... (Mevcut haliyle doğru çalışıyor)
        const statsContainer = container.querySelector('#general-stats-container');
        const complaintWordsList = container.querySelector('#complaint-words-list');
        const charts = { topMeals: container.querySelector('#top-meals-chart'), ratings: container.querySelector('#ratings-chart'), };
        let chartInstances = {};
        const renderChart = (canvas, type, data, options = {}) => {
            if (!canvas) return;
            const key = canvas.id;
            if (chartInstances[key]) chartInstances[key].destroy();
            chartInstances[key] = new Chart(canvas.getContext('2d'), { type, data, options });
        };
        const loadReports = async () => {
            try {
                const result = await api.get('get_report_data.php');
                const { general_stats, top_meals_chart, ratings_chart, complaint_words } = result.data;
                statsContainer.innerHTML = `
                    <div class="stat-card"><div class="icon" style="background-color: #17a2b8;"><i class="fas fa-comments"></i></div><div class="details"><h4>Toplam Geri Bildirim</h4><p class="value">${general_stats.total_feedback}</p></div></div>
                    <div class="stat-card"><div class="icon" style="background-color: #ffc107;"><i class="fas fa-star-half-alt"></i></div><div class="details"><h4>Ortalama Puan</h4><p class="value">${general_stats.average_rating}</p></div></div>
                    <div class="stat-card"><div class="icon" style="background-color: #28a745;"><i class="fas fa-bell"></i></div><div class="details"><h4>Yeni Geri Bildirimler</h4><p class="value">${general_stats.new_feedback_count}</p></div></div>`;
                renderChart(charts.topMeals, 'bar', top_meals_chart, { responsive: true, plugins: { legend: { display: false } } });
                renderChart(charts.ratings, 'pie', ratings_chart, { responsive: true });
                complaintWordsList.innerHTML = Object.entries(complaint_words).map(([word, count]) => `<li>${word} <span class="count">${count}</span></li>`).join('');
                if (complaintWordsList.innerHTML === '') complaintWordsList.innerHTML = '<p>Düşük puanlı yorum bulunamadı.</p>';
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
                        <td>${log.action_time_formatted}</td>
                        <td>${log.admin_username}</td>
                        <td>${log.ip_address || 'N/A'}</td>
                        <td>${log.action_type}</td>
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
                        if (input) input.value = value;
                    }
                }
            } catch (error) { showToast(error.message, 'error'); }
        };
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            try {
                const result = await api.post('manage_officials.php', new FormData(form));
                showToast(result.message, 'success');
            } catch (error) { showToast(error.message, 'error');
            } finally { submitButton.disabled = false; }
        });
        loadSettings();
    };

    // --- 4. ANA BAŞLATICI ---
    const initPage = () => {
        const urlParams = new URLSearchParams(window.location.search);
        const page = urlParams.get('page') || 'menu';

        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        const activeContent = document.querySelector(`.tab-content[data-page="${page}"]`);
        if (activeContent) activeContent.classList.add('active');

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