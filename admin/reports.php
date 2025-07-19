<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}
.stat-card {
    background-color: #fff;
    padding: 1.5rem;
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    display: flex;
    align-items: center;
    gap: 1rem;
}
.stat-card .icon {
    font-size: 2.5rem;
    padding: 1rem;
    border-radius: 50%;
    color: #fff;
}
.stat-card .details h4 {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    color: var(--secondary-color);
}
.stat-card .details .value {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
}
.charts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
.complaint-words-list {
    list-style: none;
    padding: 0;
    columns: 2;
}
.complaint-words-list li {
    padding: 0.5rem;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
}
.complaint-words-list .count {
    font-weight: bold;
    background-color: var(--danger-color);
    color: white;
    padding: 0.1rem 0.4rem;
    border-radius: 5px;
    font-size: 0.8rem;
}
@media (max-width: 992px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div id="reports-container">
    <!-- Genel İstatistik Kartları -->
    <div class="stats-grid" id="general-stats-container">
        <!-- JS ile dolacak -->
    </div>

    <div class="charts-grid">
        <!-- Puan Dağılımı Grafiği -->
        <div class="card">
            <div class="card-header"><h4>Puan Dağılımı</h4></div>
            <div class="card-body"><canvas id="ratings-chart"></canvas></div>
        </div>
        <!-- Ayın Popüler Yemekleri -->
        <div class="card">
            <div class="card-header"><h4>Bu Ayın Popüler Yemekleri</h4></div>
            <div class="card-body"><canvas id="top-meals-chart"></canvas></div>
        </div>
    </div>

    <!-- Şikayetlerde Öne Çıkan Kelimeler -->
    <div class="card">
        <div class="card-header"><h4>Şikayetlerde Öne Çıkan Kelimeler (1-2 Puan)</h4></div>
        <div class="card-body">
            <ul id="complaint-words-list" class="complaint-words-list">
                <!-- JS ile dolacak -->
            </ul>
        </div>
    </div>
</div>

