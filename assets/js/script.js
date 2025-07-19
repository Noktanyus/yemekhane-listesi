document.addEventListener('DOMContentLoaded', function() {
    // Takvim Elementleri
    const calendarGrid = document.getElementById('calendar-grid');
    const monthYearEl = document.getElementById('calendar-month-year');
    const prevMonthBtn = document.getElementById('prev-month-btn');
    const nextMonthBtn = document.getElementById('next-month-btn');
    const loadingSpinner = document.getElementById('loading-spinner');
    const goToTodayBtn = document.getElementById('go-to-today-btn');
    const printBtn = document.getElementById('print-btn');

    // Yemek Detay Modalı Elementleri
    const detailsModal = document.getElementById('meal-details-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalBody = document.getElementById('modal-body');
    const totalCaloriesEl = document.getElementById('total-calories');
    const detailsModalCloseBtn = detailsModal.querySelector('.modal-close');

    // Tarih Seçici Modalı Elementleri
    const datePickerModal = document.getElementById('date-picker-modal');
    const datePickerForm = document.getElementById('date-picker-form');
    const selectMonth = document.getElementById('select-month');
    const selectYear = document.getElementById('select-year');
    const datePickerModalCloseBtn = datePickerModal.querySelector('.modal-close');

    let currentDate = new Date();
    const today = new Date();
    const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

    const weekdays = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
    weekdays.forEach(day => {
        const dayEl = document.createElement('div');
        dayEl.classList.add('weekday-header');
        dayEl.textContent = day;
        calendarGrid.appendChild(dayEl);
    });

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
        document.querySelectorAll('.calendar-day').forEach(cell => cell.remove());
        const year = date.getFullYear();
        const month = date.getMonth();
        const { menus, special_days } = await fetchMenuData(year, month);

        monthYearEl.textContent = `${new Intl.DateTimeFormat('tr-TR', { month: 'long' }).format(date)} ${year}`;

        const firstDayOfMonth = new Date(year, month, 1);
        const lastDayOfMonth = new Date(year, month + 1, 0);
        const daysInMonth = lastDayOfMonth.getDate();
        let startingDay = (firstDayOfMonth.getDay() === 0) ? 6 : firstDayOfMonth.getDay() - 1;

        const prevMonthLastDay = new Date(year, month, 0).getDate();
        for (let i = startingDay; i > 0; i--) {
            const day = prevMonthLastDay - i + 1;
            calendarGrid.appendChild(createDayCell(day, 'other-month'));
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const dayCell = createDayCell(day, '', dateStr, menus, special_days);
            if (dateStr === todayStr) dayCell.classList.add('today');
            calendarGrid.appendChild(dayCell);
        }

        const totalCells = startingDay + daysInMonth;
        const remainingCells = (totalCells % 7 === 0) ? 0 : 7 - (totalCells % 7);
        for (let i = 1; i <= remainingCells; i++) {
            calendarGrid.appendChild(createDayCell(i, 'other-month'));
        }
    }

    function createDayCell(day, type, dateStr, menus, special_days) {
        const dayCell = document.createElement('div');
        dayCell.classList.add('calendar-day');
        if (type) {
            dayCell.classList.add(type);
        }
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
            const specialMessage = document.createElement('div');
            specialMessage.classList.add('special-day-message');
            let icon = '';
            if (special_days[dateStr].toLowerCase().includes('bayram')) icon = '🎉 ';
            else if (special_days[dateStr].toLowerCase().includes('tatil')) icon = '🇹🇷 ';
            specialMessage.innerHTML = `<span class="special-day-icon">${icon}</span>${special_days[dateStr]}`;
            mealContainer.appendChild(specialMessage);
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
                const caloriesDiv = document.createElement('div');
                caloriesDiv.classList.add('daily-calories');
                caloriesDiv.innerHTML = `🔥 ${menus[dateStr].total_calories} kcal`;
                dayCell.appendChild(caloriesDiv);
            }
        } else {
            dayCell.classList.add('is-empty');
        }
        dayCell.appendChild(mealContainer);
        return dayCell;
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

                    const ingredientsHTML = meal.ingredients 
                        ? `<details><summary>İçeriği Göster</summary><p>${meal.ingredients}</p></details>` 
                        : '';

                    modalBody.innerHTML += `
                        <div class="meal-detail">
                            <div class="meal-header">
                                <h4>${meal.name}</h4>
                                <div class="diet-icons-container">${icons.join('')}</div>
                            </div>
                            <p><strong>Kalori:</strong> ${meal.calories || 'N/A'}</p>
                            ${ingredientsHTML}
                        </div>
                    `;
                });

                totalCaloriesEl.textContent = `${totalCalories} kcal`;
                detailsModal.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Yemek detayı hatası:', error);
        }
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
        datePickerModal.classList.remove('hidden');
    }

    prevMonthBtn.addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() - 1); renderCalendar(currentDate); });
    nextMonthBtn.addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() + 1); renderCalendar(currentDate); });
    goToTodayBtn.addEventListener('click', () => { currentDate = new Date(); renderCalendar(currentDate); });
    monthYearEl.addEventListener('click', openDatePicker);

    calendarGrid.addEventListener('click', (e) => {
        const dayCell = e.target.closest('.calendar-day.has-menu');
        if (dayCell) showMealDetails(dayCell.dataset.date);
    });

    [detailsModal, datePickerModal].forEach(modal => {
        modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.add('hidden'); });
    });
    detailsModalCloseBtn.addEventListener('click', () => detailsModal.classList.add('hidden'));
    datePickerModalCloseBtn.addEventListener('click', () => datePickerModal.classList.add('hidden'));

    datePickerForm.addEventListener('submit', (e) => {
        e.preventDefault();
        currentDate = new Date(parseInt(selectYear.value), parseInt(selectMonth.value), 1);
        renderCalendar(currentDate);
        datePickerModal.classList.add('hidden');
    });

    printBtn.addEventListener('click', () => {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Yazdırılabilir Menü - ${monthYearEl.textContent}</title>
                    <link rel="preconnect" href="https://fonts.googleapis.com">
                    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Merriweather:wght@400;700;900&display=swap" rel="stylesheet">
                    <link rel="stylesheet" href="assets/css/style.css">
                    <link rel="stylesheet" href="assets/css/print.css">
                </head>
                <body>
                    <div class="print-header">
                        <img src="assets/logo.png" class="app-logo" alt="Logo">
                        <div class="header-text">
                            <p class="app-name">Akdeniz Üniversitesi</p>
                            <p class="app-subname">Sağlık, Kültür ve Spor Dairesi Başkanlığı Merkezi Yemekhane</p>
                        </div>
                    </div>
                    <h1 id="print-month-year">${monthYearEl.textContent}</h1>
                    <div id="calendar-grid-wrapper">
                        <div id="calendar-grid">${calendarGrid.innerHTML}</div>
                    </div>
                    <script>
                        setTimeout(() => { window.print(); window.close(); }, 300);
                    </script>
                </body>
            </html>
        `);
        printWindow.document.close();
    });

    // Geri Bildirim Modalı İşlevselliği
    const feedbackModal = document.getElementById('feedback-modal');
    const feedbackBtn = document.getElementById('feedback-btn');
    const feedbackForm = document.getElementById('feedback-form');
    const feedbackModalCloseBtn = feedbackModal.querySelector('.modal-close');

    feedbackBtn.addEventListener('click', () => feedbackModal.classList.remove('hidden'));
    feedbackModalCloseBtn.addEventListener('click', () => feedbackModal.classList.add('hidden'));
    feedbackModal.addEventListener('click', (e) => {
        if (e.target === feedbackModal) feedbackModal.classList.add('hidden');
    });

    feedbackForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitButton = feedbackForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Gönderiliyor...';

        try {
            const response = await fetch('api/submit_feedback.php', {
                method: 'POST',
                body: new FormData(feedbackForm)
            });
            const result = await response.json();
            if (result.success) {
                feedbackModal.classList.add('hidden');
                feedbackForm.reset();
                // İsteğe bağlı: Başarı mesajı gösterilebilir.
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
                            html += `<div class="official-item"><span class="title">${title}:</span> <a href="mailto:${name}" class="value">${name}</a></div>`;
                        } else {
                            html += `<div class="official-item"><span class="title">${title}:</span> <span class="value">${name}</span></div>`;
                        }
                    }
                }
                officialsContainer.innerHTML = html;
            }
        } catch (error) {
            console.error('Yetkili bilgileri alınırken hata:', error);
            officialsContainer.innerHTML = '<p>Yetkili bilgileri yüklenemedi.</p>';
        }
    }

    renderCalendar(currentDate);
    loadSiteInfo();
});