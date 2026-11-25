<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/adminlte.min.css') }}">
<link href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<style>
    :root {
        --bs-body-font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    body {
        font-family: var(--bs-body-font-family);
        background-color: #f1f5f9 !important;
        color: #0f172a;
    }
    /* Sidebar & Brand Refinement */
    .app-sidebar {
        background-color: #0f172a !important;
        border-right: 1px solid rgba(255, 255, 255, 0.06);
    }
    .sidebar-brand {
        height: 57px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        padding: 0 0.875rem !important;
        display: flex;
        align-items: center;
    }
    .sidebar-brand .brand-link {
        display: flex !important;
        align-items: center;
        gap: 0.625rem;
        width: 100%;
        padding: 0.375rem 0.25rem !important;
        overflow: hidden;
    }
    .sidebar-brand .brand-image {
        width: 32px !important;
        height: 32px !important;
        max-height: 32px !important;
        margin: 0 !important;
        border-radius: 8px;
        flex-shrink: 0;
        object-fit: contain;
    }
    .sidebar-brand .brand-text {
        font-size: 0.95rem;
        font-weight: 600 !important;
        color: #f8fafc !important;
        letter-spacing: -0.01em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sidebar-menu .nav-link {
        border-radius: 0.5rem !important;
        margin: 2px 0.5rem !important;
        padding: 0.55rem 0.75rem !important;
        font-size: 0.885rem;
        font-weight: 500;
        color: #94a3b8 !important;
        transition: all 0.15s ease;
    }
    .sidebar-menu .nav-link:hover {
        color: #f8fafc !important;
        background-color: rgba(255, 255, 255, 0.06) !important;
    }
    .sidebar-menu .nav-link.active {
        color: #ffffff !important;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }
    /* Header & User Menu Dropdown Polish */
    .app-header {
        background-color: #ffffff !important;
        border-bottom: 1px solid #e2e8f0 !important;
        min-height: 57px;
    }
    .user-menu > .nav-link {
        display: inline-flex !important;
        align-items: center;
        gap: 0.5rem;
        padding: 0.25rem 0.625rem !important;
        border-radius: 999px;
        transition: background-color 0.15s ease;
    }
    .user-menu > .nav-link:hover {
        background-color: #f1f5f9;
    }
    .user-menu > .nav-link .user-image {
        float: none !important;
        margin: 0 !important;
        width: 32px !important;
        height: 32px !important;
    }
    .user-menu .dropdown-menu {
        width: 270px !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 0.75rem !important;
        box-shadow: 0 12px 28px -4px rgba(15, 23, 42, 0.14) !important;
        overflow: hidden;
        padding: 0 !important;
        margin-top: 0.5rem !important;
    }
    .user-menu .user-header {
        height: auto !important;
        padding: 1.25rem 1rem !important;
        background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%) !important;
        text-align: center;
    }
    .user-menu .user-header .user-image {
        width: 60px !important;
        height: 60px !important;
        border: 3px solid rgba(255, 255, 255, 0.35) !important;
        margin-bottom: 0.5rem;
    }
    .user-menu .user-header p {
        margin: 0 !important;
        font-size: 0.95rem !important;
        font-weight: 600;
        color: #ffffff !important;
    }
    .user-menu .user-header p small {
        display: block;
        font-size: 0.78rem !important;
        font-weight: 400;
        opacity: 0.85;
        margin-top: 2px;
    }
    .user-menu .user-footer {
        background-color: #f8fafc !important;
        border-top: 1px solid #e2e8f0;
        padding: 0.75rem 1rem !important;
        display: flex !important;
        align-items: center;
        justify-content: space-between !important;
    }
    .user-menu .user-footer::after {
        display: none !important;
        content: none !important;
    }
    /* Cards, Tables & Content Layout */
    .app-content-header {
        padding: 1.15rem 0.5rem 0.65rem !important;
    }
    .app-content-header h3 {
        font-size: 1.35rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: #0f172a;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    .card {
        border: 1px solid #e2e8f0 !important;
        border-radius: 0.65rem !important;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04) !important;
    }
    .card-header {
        background-color: #ffffff !important;
        border-bottom: 1px solid #f1f5f9 !important;
        padding: 0.85rem 1.15rem;
        font-weight: 600;
    }
    .table > thead > tr > th {
        background-color: #f8fafc;
        color: #475569;
        font-size: 0.775rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border-bottom-width: 1px;
    }
    .media-file-card {
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }
    .media-file-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08) !important;
    }
    .app-footer {
        background-color: #ffffff !important;
        border-top: 1px solid #e2e8f0 !important;
        font-size: 0.825rem;
        color: #64748b !important;
    }
</style>
@stack('styles')
