@extends('layouts.admin')

@section('title', 'لوحة تحكم الوكلاء')
@section('description', 'إدارة ومراقبة وكلاء الذكاء الاصطناعي في النظام')

@push('styles')
<style>
    .chart-container {
        position: relative;
        height: 300px;
    }
    
    .status-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-left: 8px;
    }
    
    .status-active { background-color: #1cc88a; }
    .status-stopped { background-color: #e74a3b; }
    .status-warning { background-color: #f6c23e; }
    .status-loading { background-color: #36b9cc; }
    
    .real-time-indicator {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
    
    .agent-card:hover {
        transform: translateY(-2px);
        transition: all 0.3s ease;
    }
    
    .metric-card {
        transition: all 0.3s ease;
    }
    
    .metric-card:hover {
        transform: scale(1.02);
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Dashboard Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">لوحة إدارة الوكلاء الذكيين</h1>
                    <p class="text-muted">مراقبة وإدارة وكلاء الذكاء الاصطناعي</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" id="refreshDashboard">
                        <i class="fas fa-sync-alt"></i> تحديث
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAgentModal">
                        <i class="fas fa-plus"></i> إضافة وكيل
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- System Overview Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                إجمالي الوكلاء
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalAgents">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-robot fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                الوكلاء النشطون
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeAgents">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                الوكلاء المتوقفون
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stoppedAgents">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-pause-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                الوكلاء المعطلون
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="failedAgents">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Status and Charts -->
    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">أداء النظام</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                            aria-labelledby="dropdownMenuLink">
                            <div class="dropdown-header">خيارات الرسم البياني:</div>
                            <a class="dropdown-item" href="#" onclick="updateChart('throughput')">معدل الإنتاجية</a>
                            <a class="dropdown-item" href="#" onclick="updateChart('response_time')">وقت الاستجابة</a>
                            <a class="dropdown-item" href="#" onclick="updateChart('error_rate')">معدل الأخطاء</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="systemPerformanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">حالة الموارد</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="small mb-1">استخدام المعالج <span class="float-right" id="cpuUsage">-</span></div>
                        <div class="progress">
                            <div class="progress-bar bg-primary" role="progressbar" id="cpuProgress" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="small mb-1">استخدام الذاكرة <span class="float-right" id="memoryUsage">-</span></div>
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar" id="memoryProgress" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="small mb-1">استخدام القرص <span class="float-right" id="diskUsage">-</span></div>
                        <div class="progress">
                            <div class="progress-bar bg-info" role="progressbar" id="diskProgress" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="small mb-1">استخدام الشبكة <span class="float-right" id="networkUsage">-</span></div>
                        <div class="progress">
                            <div class="progress-bar bg-warning" role="progressbar" id="networkProgress" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Agents List -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">قائمة الوكلاء</h6>
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control form-control-sm" id="agentSearch" placeholder="البحث في الوكلاء..." style="width: 200px;">
                        <select class="form-select form-select-sm" id="statusFilter" style="width: 150px;">
                            <option value="">جميع الحالات</option>
                            <option value="active">نشط</option>
                            <option value="stopped">متوقف</option>
                            <option value="failed">معطل</option>
                            <option value="paused">مؤقت</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="agentsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>اسم الوكيل</th>
                                    <th>النوع</th>
                                    <th>الحالة</th>
                                    <th>آخر نشاط</th>
                                    <th>وقت الاستجابة</th>
                                    <th>معدل النجاح</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="agentsTableBody">
                                <!-- Dynamic content will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">النشاط الأخير</h6>
                </div>
                <div class="card-body">
                    <div id="recentActivity" style="max-height: 300px; overflow-y: auto;">
                        <!-- Dynamic activity feed will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Agent Modal -->
<div class="modal fade" id="addAgentModal" tabindex="-1" aria-labelledby="addAgentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAgentModalLabel">إضافة وكيل جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addAgentForm">
                    <div class="mb-3">
                        <label for="agentName" class="form-label">اسم الوكيل</label>
                        <input type="text" class="form-control" id="agentName" required>
                    </div>
                    <div class="mb-3">
                        <label for="agentType" class="form-label">نوع الوكيل</label>
                        <select class="form-select" id="agentType" required>
                            <option value="">اختر النوع</option>
                            <option value="text_analyzer">محلل النصوص</option>
                            <option value="image_processor">معالج الصور</option>
                            <option value="recommendation_engine">محرك التوصيات</option>
                            <option value="classification_agent">وكيل التصنيف</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="agentConfig" class="form-label">التكوين (JSON)</label>
                        <textarea class="form-control" id="agentConfig" rows="4" placeholder='{"key": "value"}'></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" onclick="addAgent()">إضافة الوكيل</button>
            </div>
        </div>
    </div>
</div>

<!-- Agent Details Modal -->
<div class="modal fade" id="agentDetailsModal" tabindex="-1" aria-labelledby="agentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="agentDetailsModalLabel">تفاصيل الوكيل</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="agentDetailsContent">
                <!-- Dynamic content will be loaded here -->
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
// Global variables
let dashboardData = {};
let performanceChart = null;
let eventSource = null;

// Initialize dashboard
$(document).ready(function() {
    initializeDashboard();
    setupEventListeners();
    startRealTimeUpdates();
});

// Initialize dashboard data
function initializeDashboard() {
    loadDashboardData();
    initializeChart();
}

// Load dashboard data
function loadDashboardData() {
    $.ajax({
        url: '{{ route("admin.ai.dashboard.data") }}',
        method: 'GET',
        success: function(response) {
            // Handle both direct data and wrapped response
            const data = response.data || response;
            dashboardData = data;
            updateDashboardUI(data);
        },
        error: function(xhr, status, error) {
            console.error('Error loading dashboard data:', error);
            showAlert('خطأ في تحميل بيانات اللوحة', 'danger');
        }
    });
}

// Update dashboard UI
function updateDashboardUI(data) {
    // Update overview cards
    $('#totalAgents').text(data.overview.total_agents);
    $('#activeAgents').text(data.overview.active_agents);
    $('#stoppedAgents').text(data.overview.stopped_agents);
    $('#failedAgents').text(data.overview.failed_agents);

    // Update resource usage
    updateResourceUsage(data.system_health);

    // Update agents table
    updateAgentsTable(data.agents);

    // Update recent activity
    updateRecentActivity(data.recent_activity);

    // Update chart
    updatePerformanceChart(data.metrics);
}

// Update resource usage
function updateResourceUsage(health) {
    $('#cpuUsage').text(health.cpu_usage + '%');
    $('#cpuProgress').css('width', health.cpu_usage + '%');
    
    $('#memoryUsage').text(health.memory_usage + '%');
    $('#memoryProgress').css('width', health.memory_usage + '%');
    
    $('#diskUsage').text(health.disk_usage + '%');
    $('#diskProgress').css('width', health.disk_usage + '%');
    
    $('#networkUsage').text(health.network_usage + '%');
    $('#networkProgress').css('width', health.network_usage + '%');
}

// Update agents table
function updateAgentsTable(agents) {
    const tbody = $('#agentsTableBody');
    tbody.empty();

    agents.forEach(agent => {
        const statusBadge = getStatusBadge(agent.status);
        const successRate = agent.success_rate ? agent.success_rate.toFixed(1) + '%' : 'N/A';
        
        const row = `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="agent-avatar me-2">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div>
                            <div class="fw-bold">${agent.name}</div>
                            <div class="text-muted small">${agent.id}</div>
                        </div>
                    </div>
                </td>
                <td>${agent.type}</td>
                <td>${statusBadge}</td>
                <td>${formatDateTime(agent.last_activity)}</td>
                <td>${agent.response_time}ms</td>
                <td>${successRate}</td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-primary" onclick="viewAgentDetails('${agent.id}')">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-success" onclick="startAgent('${agent.id}')" ${agent.status === 'active' ? 'disabled' : ''}>
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning" onclick="stopAgent('${agent.id}')" ${agent.status === 'stopped' ? 'disabled' : ''}>
                            <i class="fas fa-stop"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-info" onclick="restartAgent('${agent.id}')">
                            <i class="fas fa-redo"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="testAgent('${agent.id}')">
                            <i class="fas fa-vial"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

// Get status badge
function getStatusBadge(status) {
    const badges = {
        'active': '<span class="badge bg-success">نشط</span>',
        'stopped': '<span class="badge bg-secondary">متوقف</span>',
        'failed': '<span class="badge bg-danger">معطل</span>',
        'paused': '<span class="badge bg-warning">مؤقت</span>',
        'initializing': '<span class="badge bg-info">يتم التهيئة</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">غير معروف</span>';
}

// Update recent activity
function updateRecentActivity(activities) {
    const container = $('#recentActivity');
    container.empty();

    activities.forEach(activity => {
        const item = `
            <div class="d-flex align-items-center mb-3">
                <div class="activity-icon me-3">
                    <i class="fas ${getActivityIcon(activity.type)} text-${getActivityColor(activity.type)}"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold">${activity.message}</div>
                    <div class="text-muted small">${formatDateTime(activity.timestamp)}</div>
                </div>
            </div>
        `;
        container.append(item);
    });
}

// Get activity icon
function getActivityIcon(type) {
    const icons = {
        'agent_started': 'fa-play-circle',
        'agent_stopped': 'fa-stop-circle',
        'agent_failed': 'fa-exclamation-triangle',
        'agent_recovered': 'fa-check-circle',
        'config_updated': 'fa-cog',
        'test_completed': 'fa-vial'
    };
    return icons[type] || 'fa-info-circle';
}

// Get activity color
function getActivityColor(type) {
    const colors = {
        'agent_started': 'success',
        'agent_stopped': 'warning',
        'agent_failed': 'danger',
        'agent_recovered': 'success',
        'config_updated': 'info',
        'test_completed': 'primary'
    };
    return colors[type] || 'secondary';
}

// Initialize performance chart
function initializeChart() {
    const ctx = document.getElementById('systemPerformanceChart').getContext('2d');
    performanceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'معدل الإنتاجية',
                data: [],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Update performance chart
function updatePerformanceChart(metrics) {
    if (!performanceChart || !metrics) return;

    const labels = metrics.timestamps || [];
    const data = metrics.throughput || [];

    performanceChart.data.labels = labels;
    performanceChart.data.datasets[0].data = data;
    performanceChart.update();
}

// Start real-time updates
function startRealTimeUpdates() {
    if (eventSource) {
        eventSource.close();
    }

    eventSource = new EventSource('{{ route("admin.ai.dashboard.stream") }}');
    
    eventSource.onmessage = function(event) {
        const data = JSON.parse(event.data);
        updateDashboardUI(data);
    };

    eventSource.onerror = function(event) {
        console.error('EventSource failed:', event);
        // Retry connection after 5 seconds
        setTimeout(() => {
            startRealTimeUpdates();
        }, 5000);
    };
}

// Setup event listeners
function setupEventListeners() {
    $('#refreshDashboard').click(function() {
        loadDashboardData();
    });

    $('#agentSearch').on('input', function() {
        filterAgents();
    });

    $('#statusFilter').change(function() {
        filterAgents();
    });
}

// Filter agents
function filterAgents() {
    const searchTerm = $('#agentSearch').val().toLowerCase();
    const statusFilter = $('#statusFilter').val();

    $('#agentsTableBody tr').each(function() {
        const row = $(this);
        const agentName = row.find('td:first .fw-bold').text().toLowerCase();
        const agentStatus = row.find('td:nth-child(3) .badge').text().toLowerCase();

        const matchesSearch = agentName.includes(searchTerm);
        const matchesStatus = !statusFilter || agentStatus.includes(statusFilter);

        row.toggle(matchesSearch && matchesStatus);
    });
}

// Agent control functions
function startAgent(agentId) {
    controlAgent(agentId, 'start', 'تشغيل');
}

function stopAgent(agentId) {
    controlAgent(agentId, 'stop', 'إيقاف');
}

function restartAgent(agentId) {
    controlAgent(agentId, 'restart', 'إعادة تشغيل');
}

function controlAgent(agentId, action, actionName) {
    $.ajax({
        url: `{{ route("admin.ai.agents.start", ":agentId") }}`.replace(':agentId', agentId).replace('start', action),
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            showAlert(`تم ${actionName} الوكيل بنجاح`, 'success');
            loadDashboardData();
        },
        error: function(xhr, status, error) {
            showAlert(`خطأ في ${actionName} الوكيل`, 'danger');
        }
    });
}

function testAgent(agentId) {
    $.ajax({
        url: `{{ route("admin.ai.agents.test", ":agentId") }}`.replace(':agentId', agentId),
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            showAlert('تم اختبار الوكيل بنجاح', 'success');
        },
        error: function(xhr, status, error) {
            showAlert('خطأ في اختبار الوكيل', 'danger');
        }
    });
}

function viewAgentDetails(agentId) {
    $.ajax({
        url: `{{ route("admin.ai.dashboard.agent.details", ":agentId") }}`.replace(':agentId', agentId),
        method: 'GET',
        success: function(data) {
            displayAgentDetails(data);
        },
        error: function(xhr, status, error) {
            showAlert('خطأ في تحميل تفاصيل الوكيل', 'danger');
        }
    });
}

function displayAgentDetails(agent) {
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h6>معلومات أساسية</h6>
                <table class="table table-sm">
                    <tr><td>الاسم:</td><td>${agent.name}</td></tr>
                    <tr><td>النوع:</td><td>${agent.type}</td></tr>
                    <tr><td>الحالة:</td><td>${getStatusBadge(agent.status)}</td></tr>
                    <tr><td>آخر نشاط:</td><td>${formatDateTime(agent.last_activity)}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>إحصائيات الأداء</h6>
                <table class="table table-sm">
                    <tr><td>وقت الاستجابة:</td><td>${agent.response_time}ms</td></tr>
                    <tr><td>معدل النجاح:</td><td>${agent.success_rate}%</td></tr>
                    <tr><td>إجمالي الطلبات:</td><td>${agent.total_requests}</td></tr>
                    <tr><td>الطلبات الناجحة:</td><td>${agent.successful_requests}</td></tr>
                </table>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6>التكوين الحالي</h6>
                <pre class="bg-light p-3 rounded"><code>${JSON.stringify(agent.configuration, null, 2)}</code></pre>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6>السجلات الأخيرة</h6>
                <div style="max-height: 200px; overflow-y: auto;">
                    ${agent.recent_logs.map(log => `
                        <div class="d-flex justify-content-between">
                            <span>${log.message}</span>
                            <small class="text-muted">${formatDateTime(log.timestamp)}</small>
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
    `;

    $('#agentDetailsContent').html(content);
    $('#agentDetailsModal').modal('show');
}

// Utility functions
function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('ar-SA');
}

function showAlert(message, type) {
    const alert = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').remove();
    
    // Add new alert at the top of the container
    $('.container-fluid').prepend(alert);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
}

// Update chart type
function updateChart(type) {
    if (!dashboardData.metrics) return;

    const datasets = {
        'throughput': {
            label: 'معدل الإنتاجية',
            data: dashboardData.metrics.throughput,
            borderColor: 'rgb(75, 192, 192)'
        },
        'response_time': {
            label: 'وقت الاستجابة (ms)',
            data: dashboardData.metrics.response_times,
            borderColor: 'rgb(255, 99, 132)'
        },
        'error_rate': {
            label: 'معدل الأخطاء (%)',
            data: dashboardData.metrics.error_rates,
            borderColor: 'rgb(255, 205, 86)'
        }
    };

    if (datasets[type]) {
        performanceChart.data.datasets[0] = {
            ...datasets[type],
            backgroundColor: datasets[type].borderColor.replace('rgb', 'rgba').replace(')', ', 0.2)'),
            tension: 0.1
        };
        performanceChart.update();
    }
}

// Add new agent
function addAgent() {
    const formData = {
        name: $('#agentName').val(),
        type: $('#agentType').val(),
        configuration: $('#agentConfig').val()
    };

    if (!formData.name || !formData.type) {
        showAlert('يرجى ملء جميع الحقول المطلوبة', 'warning');
        return;
    }

    try {
        if (formData.configuration) {
            JSON.parse(formData.configuration);
        }
    } catch (e) {
        showAlert('تكوين JSON غير صحيح', 'danger');
        return;
    }

    // Here you would make an AJAX call to create the agent
    showAlert('سيتم إضافة هذه الميزة قريباً', 'info');
    $('#addAgentModal').modal('hide');
}

// Cleanup on page unload
$(window).on('beforeunload', function() {
    if (eventSource) {
        eventSource.close();
    }
});
</script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection