<?php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('HTTP/1.0 403 Forbidden');
    include __DIR__ . '/../errors/403.php';
    exit();
}

require_once __DIR__ . '/../partials/header.php';
?>

<div class="container mt-4">
    <h1><?= htmlspecialchars($pageTitle) ?></h1>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter"></i> Filters</h5>
        </div>
        <div class="card-body">
            <form action="" method="GET" id="incomeFilterForm">
                <input type="hidden" name="controller" value="admin">
                <input type="hidden" name="action" value="incomeAnalytics">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="resort_id" class="form-label">Resort</label>
                        <select name="resort_id" id="resort_id" class="form-select">
                            <option value="">All Resorts</option>
                            <?php foreach ($resorts as $resort): ?>
                                <option value="<?= $resort->resortId ?>" <?= (isset($_GET['resort_id']) && $_GET['resort_id'] == $resort->resortId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($resort->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="year" class="form-label">Year</label>
                        <select name="year" id="year" class="form-select">
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="month" class="form-label">Month</label>
                        <select name="month" id="month" class="form-select">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= ($month == $m) ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row d-flex align-items-stretch">
        <!-- Yearly Income Chart -->
        <div class="col-md-5 d-flex">
            <div class="card w-100">
                <div class="card-header">
                    <h5 class="mb-0">Monthly Income for <?= $year ?></h5>
                </div>
                <div class="card-body">
                    <div style="height: 350px;">
                        <canvas id="yearlyIncomeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Income Chart -->
        <div class="col-md-7 d-flex">
            <div class="card w-100">
                <div class="card-header">
                    <h5 class="mb-0">Daily Income for <?= date('F', mktime(0, 0, 0, $month, 1)) . ' ' . $year ?> (Total: ₱<?= number_format($totalMonthlyIncome, 2) ?>)</h5>
                </div>
                <div class="card-body">
                    <div style="height: 350px;">
                        <canvas id="monthlyIncomeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Submit form on filter change
    document.getElementById('resort_id').addEventListener('change', () => document.getElementById('incomeFilterForm').submit());
    document.getElementById('year').addEventListener('change', () => document.getElementById('incomeFilterForm').submit());
    document.getElementById('month').addEventListener('change', () => document.getElementById('incomeFilterForm').submit());

    // Yearly Income Chart
    const yearlyCtx = document.getElementById('yearlyIncomeChart').getContext('2d');
    const yearlyData = <?= json_encode(array_values($yearlyIncomeData)) ?>;
    const yearlyLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    new Chart(yearlyCtx, {
        type: 'bar',
        data: {
            labels: yearlyLabels,
            datasets: [{
                label: 'Monthly Income',
                data: yearlyData,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `Income: \u20B1${context.parsed.y.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => `\u20B1${(value / 1000)}k`
                    }
                }
            }
        }
    });

    // Monthly (Daily) Income Chart
    const monthlyCtx = document.getElementById('monthlyIncomeChart').getContext('2d');
    const monthlyData = <?= json_encode(array_values($dailyIncomeData)) ?>;
    const monthlyLabels = <?= json_encode(array_keys($dailyIncomeData)) ?>;

    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: monthlyLabels,
            datasets: [{
                label: 'Daily Income',
                data: monthlyData,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `Income: \u20B1${context.parsed.y.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => `\u20B1${value.toLocaleString()}`
                    }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>