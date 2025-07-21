<style>
.feedback-filters-card .card-body {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
}
.feedback-filters-card .filter-group {
    flex-grow: 1;
}
.feedback-filters-card .filter-group.search {
    min-width: 250px;
}
.feedback-filters-card .filter-group.date {
    min-width: 150px;
}
.feedback-filters-card .filter-group label {
    font-size: 0.85rem;
    color: #6c757d;
}
.feedback-filters-card .btn-group .btn {
    border-color: #ced4da;
}
.feedback-filters-card .btn-group .btn.active {
    background-color: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}
.feedback-card-list .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1.25rem;
}
.feedback-card-list .card-body {
    padding: 1.25rem;
}
.feedback-card-list .card-footer {
    background-color: #f8f9fa;
    padding: 0.75rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.feedback-card-list .rating {
    font-size: 1.2rem;
    color: #ffc107;
}
.feedback-card-list .status-badge {
    padding: 0.25em 0.6em;
    font-size: 0.8rem;
    font-weight: 700;
    border-radius: 10rem;
    color: #fff;
}
.feedback-card-list .status-yeni { background-color: var(--warning-color); }
.feedback-card-list .status-okundu { background-color: var(--info-color); }
.feedback-card-list .status-cevaplandı { background-color: var(--success-color); }
.feedback-card-list .status-arsivlendi { background-color: var(--secondary-color); }
</style>

<div class="card feedback-filters-card shadow-sm">
    <div class="card-body">
        <div class="filter-controls">
            <div class="filter-group search">
                <label for="search-term">Arama (İsim veya Yorum)</label>
                <input type="text" id="search-term" class="form-control" placeholder="Filtrelemek için yazın...">
            </div>
            <div class="filter-group date">
                <label for="start-date">Başlangıç Tarihi</label>
                <input type="date" id="start-date" class="form-control">
            </div>
            <div class="filter-group date">
                <label for="end-date">Bitiş Tarihi</label>
                <input type="date" id="end-date" class="form-control">
            </div>
            <div class="filter-group">
                <label>Durum</label>
                <div id="status-filter-group" class="btn-group w-100">
                    <button type="button" class="btn btn-outline-secondary active" data-filter="all">Tümü</button>
                    <button type="button" class="btn btn-outline-secondary" data-filter="yeni">Yeni</button>
                    <button type="button" class="btn btn-outline-secondary" data-filter="okundu">Okundu</button>
                    <button type="button" class="btn btn-outline-secondary" data-filter="cevaplandı">Cevaplandı</button>
                    <button type="button" class="btn btn-outline-secondary" data-filter="arsivlendi">Arşivlendi</button>
                </div>
            </div>
        </div>
        <div class="filter-actions">
            <div class="filter-group">
                <label for="limit-select">Sayfa Başına</label>
                <select id="limit-select" class="form-control">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <button id="btn-clear-filters" class="btn btn-light" title="Filtreleri Temizle">
                <i class="fas fa-undo-alt"></i>
                <span>Temizle</span>
            </button>
        </div>
    </div>
</div>

<div id="feedback-container" class="feedback-card-list mt-4">
    <!-- JS ile dolacak -->
</div>
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">


