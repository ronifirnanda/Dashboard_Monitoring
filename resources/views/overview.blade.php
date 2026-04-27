@extends('layouts.app')

@section('title', 'Overview')

@section('subtitle', 'Plan, prioritize, and monitor your tasks with a calmer, greener dashboard.')

@section('page-actions')
<a href="#" class="btn-pill btn-primary-soft"><i class="bi bi-plus-lg me-2"></i>Add Project</a>
<a href="#" class="btn-pill btn-outline-soft"><i class="bi bi-upload me-2"></i>Import Data</a>
@endsection

@section('content')
<div class="stats-grid">
    <div class="stat-card stat-card--primary">
        <div class="label">Total Projects</div>
        <div class="value">24</div>
        <div class="trend"><i class="bi bi-graph-up-arrow"></i> Increased from last month</div>
    </div>

    <div class="stat-card">
        <div class="label">Ended Projects</div>
        <div class="value">10</div>
        <div class="trend"><i class="bi bi-arrow-up-right"></i> Increased from last month</div>
    </div>

    <div class="stat-card">
        <div class="label">Running Projects</div>
        <div class="value">12</div>
        <div class="trend"><i class="bi bi-arrow-up-right"></i> Increased from last month</div>
    </div>

    <div class="stat-card">
        <div class="label">Pending Project</div>
        <div class="value">2</div>
        <div class="trend"><i class="bi bi-dot"></i> On discuss</div>
    </div>
</div>

<div class="section-grid">
    <div class="panel">
        <div class="panel-header">
            <div>
                <div class="panel-title">Project Analytics</div>
                <div class="panel-small">Weekly distribution of active work.</div>
            </div>
            <button type="button" class="icon-btn" style="width:36px;height:36px;"><i class="bi bi-arrow-up-right"></i></button>
        </div>

        <div class="chart-bars">
            <div class="bar-wrap"><div class="bar" style="height: 68px;"></div><div class="day">S</div></div>
            <div class="bar-wrap"><div class="bar filled" style="height: 124px;"></div><div class="day">M</div></div>
            <div class="bar-wrap"><div class="bar filled" style="height: 92px;"></div><div class="day">T</div></div>
            <div class="bar-wrap"><div class="bar filled" style="height: 160px;"></div><div class="day">W</div></div>
            <div class="bar-wrap"><div class="bar" style="height: 88px;"></div><div class="day">T</div></div>
            <div class="bar-wrap"><div class="bar" style="height: 138px;"></div><div class="day">F</div></div>
            <div class="bar-wrap"><div class="bar" style="height: 80px;"></div><div class="day">S</div></div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <div class="panel-title">Reminders</div>
                <div class="panel-small">Today agenda</div>
            </div>
        </div>

        <div class="reminder-card">
            <div>
                <h4>Meeting with Arc Company</h4>
                <p>Time: 02:00 pm - 04:00 pm</p>
            </div>
            <a href="#" class="btn-pill btn-primary-soft text-center w-100"><i class="bi bi-camera-video me-2"></i>Start Meeting</a>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <div class="panel-title">Project</div>
            <div class="panel-small">Latest items from the dashboard queue.</div>
        </div>
        <a href="#" class="btn-pill btn-outline-soft py-2 px-3"><i class="bi bi-plus-lg me-2"></i>New</a>
    </div>

    <div class="project-list">
        <div class="project-item">
            <div class="project-dot" style="background:#3866ff;"><i class="bi bi-lightning-charge-fill"></i></div>
            <div class="project-text">
                <strong>Develop API Endpoints</strong>
                <span>Due date: Nov 26, 2024</span>
            </div>
        </div>
        <div class="project-item">
            <div class="project-dot" style="background:#3bb3aa;"><i class="bi bi-circle-half"></i></div>
            <div class="project-text">
                <strong>Onboarding Flow</strong>
                <span>Due date: Nov 28, 2024</span>
            </div>
        </div>
        <div class="project-item">
            <div class="project-dot" style="background:#1f6b45;"><i class="bi bi-kanban-fill"></i></div>
            <div class="project-text">
                <strong>Build Dashboard</strong>
                <span>Due date: Nov 30, 2024</span>
            </div>
        </div>
        <div class="project-item">
            <div class="project-dot" style="background:#ffb02e;"><i class="bi bi-upload"></i></div>
            <div class="project-text">
                <strong>Optimize Page Load</strong>
                <span>Due date: Dec 2, 2024</span>
            </div>
        </div>
    </div>
</div>
@endsection
