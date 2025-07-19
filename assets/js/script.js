document.addEventListener('DOMContentLoaded', function() {
    // Elementler
    const calendarGrid = document.getElementById('calendar-grid');
    const mobileListView = document.getElementById('mobile-list-view');
    const monthYearEl = document.getElementById('calendar-month-year');
    const prevMonthBtn = document.getElementById('prev-month-btn');
    const nextMonthBtn = document.getElementById('next-month-btn');
    const loadingSpinner = document.getElementById('loading-spinner');
    const goToTodayBtn = document.getElementById('go-to-today-btn');
    const detailsModal = document.getElementById('meal-details-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalBody = document.getElementById('modal-body');
    const totalCaloriesEl = document.getElementById('total-calories');
    const detailsModalCloseBtn = detailsModal.querySelector('.modal-close');
    const datePickerModal = document.getElementById('date-picker-modal');
    const datePickerForm = document.getElementById('date-picker-form');
    const selectMonth = document.getElementById('select-month');
    const selectYear = document.getElementById('select-year');
    const datePickerModalCloseBtn = datePickerModal.querySelector('.modal-close');

    let currentDate = new Date();
    const today = new Date();
    const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    const weekdays = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];

    // --- Modal Kontrol Fonksiyonları ---
    function openModal(modal) {
        if (modal) {
            modal.classList.remove('hidden');
            document.body.classList.add('modal-open');
        }
    }

    function closeModal(modal) {
        if (modal) {
            modal.classList.add('hidden');
            // Sadece tüm modallar kapalıysa body class'ını kaldır
            const anyModalOpen = document.querySelector('.modal:not(.hidden)');
            if (!anyModalOpen) {
                document.body.classList.remove('modal-open');
            }
        }
    }
    // --- Bitiş ---

    async function fetchMenuData(year, month) {
        loadingSpinner.classList.remove('hidden');
        try {
            const response = await fetch(`api/get_menu_events.php?year=${year}&month=${month + 1}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return await response.json();
        } catch (error) {
            console.error('Menü verileri alınırken hata oluştu:', error);
            return { menus: {}, special_days: {} };
        } finally {
            loadingSpinner.classList.add('hidden');
        }
    }

    async function renderCalendar(date) {
        // Önceki verileri temizle
        calendarGrid.innerHTML = '';
        mobileListView.innerHTML = '';

        // Başlıkları ekle (sadece masaüstü için)
        weekdays.forEach(day => {
            const dayEl = document.createElement('div');
            dayEl.classList.add('weekday-header');
            dayEl.textContent = day;
            calendarGrid.appendChild(dayEl);
        });

        const year = date.getFullYear();
        const month = date.getMonth();
        const { menus, special_days } = await fetchMenuData(year, month);

        monthYearEl.textContent = `${new Intl.DateTimeFormat('tr-TR', { month: 'long' }).format(date)} ${year}`;

        const firstDayOfMonth = new Date(year, month, 1);
        const lastDayOfMonth = new Date(year, month + 1, 0);
        const daysInMonth = lastDayOfMonth.getDate();
        let startingDay = (firstDayOfMonth.getDay() === 0) ? 6 : firstDayOfMonth.getDay() - 1;

        // Önceki ayın günlerini ekle (sadece masaüstü)
        const prevMonthLastDay = new Date(year, month, 0).getDate();
        for (let i = startingDay; i > 0; i--) {
            const day = prevMonthLastDay - i + 1;
            calendarGrid.appendChild(createDayCell(day, 'other-month'));
        }

        // Bu ayın günlerini ekle
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday = dateStr === todayStr;
            
            // Masaüstü için hücre oluştur
            const dayCell = createDayCell(day, '', dateStr, menus, special_days, isToday);
            calendarGrid.appendChild(dayCell);

            // Mobil için kart oluştur
            const mobileCard = createMobileDayCard(day, dateStr, menus, special_days, isToday);
            mobileListView.appendChild(mobileCard);
        }

        // Sonraki ayın günlerini ekle (sadece masaüstü)
        const totalCells = startingDay + daysInMonth;
        const remainingCells = (totalCells % 7 === 0) ? 0 : 7 - (totalCells % 7);
        for (let i = 1; i <= remainingCells; i++) {
            calendarGrid.appendChild(createDayCell(i, 'other-month'));
        }
    }

    function createDayCell(day, type, dateStr, menus, special_days, isToday) {
        const dayCell = document.createElement('div');
        dayCell.classList.add('calendar-day');
        if (type) dayCell.classList.add(type);
        if (isToday) dayCell.classList.add('today');
        if (dateStr) dayCell.dataset.date = dateStr;

        const dayNumber = document.createElement('div');
        dayNumber.classList.add('day-number');
        dayNumber.textContent = day;
        dayCell.appendChild(dayNumber);

        const mealContainer = document.createElement('div');
        mealContainer.classList.add('meal-container');

        const hasSpecials = special_days && special_days[dateStr];
        const hasMenus = menus && menus[dateStr];

        if (hasSpecials) {
            mealContainer.innerHTML = `<div class="special-day-message">${special_days[dateStr]}</div>`;
        } else if (hasMenus) {
            dayCell.classList.add('has-menu');
            const mealList = document.createElement('ul');
            mealList.classList.add('meal-list');
            menus[dateStr].meals.forEach(meal => {
                const mealItem = document.createElement('li');
                mealItem.textContent = meal.name;
                mealList.appendChild(mealItem);
            });
            mealContainer.appendChild(mealList);
            if (menus[dateStr].total_calories > 0) {
                dayCell.innerHTML += `<div class="daily-calories">🔥 ${menus[dateStr].total_calories} kcal</div>`;
            }
        } else if (!type) {
            dayCell.classList.add('is-empty');
        }
        dayCell.appendChild(mealContainer);
        return dayCell;
    }

    function createMobileDayCard(day, dateStr, menus, special_days, isToday) {
        const card = document.createElement('div');
        card.classList.add('mobile-day-card');
        if (isToday) card.classList.add('today');
        card.dataset.date = dateStr;

        const dateObj = new Date(dateStr + 'T00:00:00');
        const weekday = new Intl.DateTimeFormat('tr-TR', { weekday: 'long' }).format(dateObj);

        const header = document.createElement('div');
        header.classList.add('mobile-day-header');
        if (isToday) header.classList.add('today');
        header.innerHTML = `<span class="date">${day} ${monthYearEl.textContent.split(' ')[0]}</span><span class="weekday">${weekday}</span>`;
        card.appendChild(header);

        const hasSpecials = special_days && special_days[dateStr];
        const hasMenus = menus && menus[dateStr];

        if (hasSpecials) {
            card.innerHTML += `<div class="special-day-message">${special_days[dateStr]}</div>`;
        } else if (hasMenus) {
            card.classList.add('has-menu');
            const mealList = document.createElement('ul');
            mealList.classList.add('meal-list');
            menus[dateStr].meals.forEach(meal => {
                const mealItem = document.createElement('li');
                mealItem.textContent = meal.name;
                mealList.appendChild(mealItem);
            });
            card.appendChild(mealList);
        } else {
            card.classList.add('is-empty');
            card.innerHTML += '<span>Menü bilgisi bulunmuyor.</span>';
        }
        return card;
    }

    async function showMealDetails(dateStr) {
        try {
            const response = await fetch(`api/get_menu_events.php?date=${dateStr}`);
            if (!response.ok) throw new Error('Detaylar alınamadı.');
            
            const data = await response.json();
            if (data.menu && data.menu.length > 0) {
                modalTitle.textContent = new Date(dateStr + 'T00:00:00').toLocaleDateString('tr-TR', { dateStyle: 'full' });
                let totalCalories = 0;
                modalBody.innerHTML = '';
                data.menu.forEach(meal => {
                    totalCalories += Number(meal.calories) || 0;
                    const icons = [];
                    if (meal.is_vegetarian == 1) icons.push('<span class="diet-icon" title="Vejetaryen">🌿</span>');
                    if (meal.is_gluten_free == 1) icons.push('<span class="diet-icon" title="Glütensiz">🚫🌾</span>');
                    if (meal.has_allergens == 1) icons.push('<span class="diet-icon" title="Alerjen İçerir">⚠️</span>');
                    const ingredientsHTML = meal.ingredients ? `<details><summary>İçeriği Göster</summary><p>${meal.ingredients}</p></details>` : '';
                    modalBody.innerHTML += `<div class="meal-detail"><div class="meal-header"><h4>${meal.name}</h4><div class="diet-icons-container">${icons.join('')}</div></div><p><strong>Kalori:</strong> ${meal.calories || 'N/A'}</p>${ingredientsHTML}</div>`;
                });
                totalCaloriesEl.textContent = `${totalCalories} kcal`;
                openModal(detailsModal);
            }
        } catch (error) { console.error('Yemek detayı hatası:', error); }
    }

    function openDatePicker() {
        const currentYear = new Date().getFullYear();
        selectYear.innerHTML = '';
        for (let i = currentYear - 10; i <= currentYear + 10; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = i;
            selectYear.appendChild(option);
        }
        selectMonth.value = currentDate.getMonth();
        selectYear.value = currentDate.getFullYear();
        openModal(datePickerModal);
    }

    async function loadSiteInfo() {
        const officialsContainer = document.getElementById('officials-info');
        if (!officialsContainer) return;
        try {
            const response = await fetch('api/get_site_info.php');
            const result = await response.json();
            if (result.success && result.data) {
                let html = '';
                for (const [title, name] of Object.entries(result.data)) {
                    if (name) {
                        if (title.includes('E-posta')) {
                            html += `<div class="official-item"><a href="mailto:${name}" class="name">${name}</a><span class="title">${title}</span></div>`;
                        } else {
                            html += `<div class="official-item"><span class="name">${name}</span><span class="title">${title}</span></div>`;
                        }
                    }
                }
                officialsContainer.innerHTML = html;
            }
        } catch (error) { console.error('Yetkili bilgileri alınırken hata:', error); }
    }

    // Event Listeners
    prevMonthBtn.addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() - 1); renderCalendar(currentDate); });
    nextMonthBtn.addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() + 1); renderCalendar(currentDate); });
    goToTodayBtn.addEventListener('click', () => { currentDate = new Date(); renderCalendar(currentDate); });
    monthYearEl.addEventListener('click', openDatePicker);
    calendarGrid.addEventListener('click', (e) => {
        const dayCell = e.target.closest('.calendar-day.has-menu');
        if (dayCell) showMealDetails(dayCell.dataset.date);
    });
    mobileListView.addEventListener('click', (e) => {
        const dayCard = e.target.closest('.mobile-day-card.has-menu');
        if (dayCard) showMealDetails(dayCard.dataset.date);
    });
    
    // Geri Bildirim Modalı
    const feedbackModal = document.getElementById('feedback-modal');
    const feedbackBtn = document.getElementById('feedback-btn');
    const feedbackForm = document.getElementById('feedback-form');
    const feedbackModalCloseBtn = feedbackModal.querySelector('.modal-close');
    feedbackBtn.addEventListener('click', () => openModal(feedbackModal));
    
    // Tüm modallar için ortak kapatma eventleri
    [detailsModal, datePickerModal, feedbackModal].forEach(modal => {
        if (modal) {
            const closeBtn = modal.querySelector('.modal-close');
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(modal); });
            if(closeBtn) closeBtn.addEventListener('click', () => closeModal(modal));
        }
    });

    datePickerForm.addEventListener('submit', (e) => {
        e.preventDefault();
        currentDate = new Date(parseInt(selectYear.value), parseInt(selectMonth.value), 1);
        renderCalendar(currentDate);
        closeModal(datePickerModal);
    });

    feedbackForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitButton = feedbackForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Gönderiliyor...';
        try {
            const response = await fetch('api/submit_feedback.php', { method: 'POST', body: new FormData(feedbackForm) });
            const result = await response.json();
            if (result.success) {
                closeModal(feedbackModal);
                feedbackForm.reset();
                alert('Geri bildiriminiz için teşekkür ederiz!');
            } else {
                throw new Error(result.message || 'Bir hata oluştu.');
            }
        } catch (error) {
            alert(`Hata: ${error.message}`);
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Gönder';
        }
    });

    // Initial Load
    renderCalendar(currentDate);
    loadSiteInfo();
});