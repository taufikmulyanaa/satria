<?php
/**
 * Enhanced Main Dashboard with DataTables and Export Features
 * File: index.php
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Check required files
    if (!file_exists('models/Employee.php')) {
        throw new Exception('Employee model file not found. Please check your installation.');
    }

    if (!file_exists('config/database.php')) {
        throw new Exception('Database configuration file not found. Please check your installation.');
    }

    require_once 'models/Employee.php';

    $employee = new Employee();

    // Handle AJAX requests for DataTables
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'true') {
        header('Content-Type: application/json');
        
        // Get filters
        $filters = [];
        if (isset($_GET['division']) && !empty($_GET['division'])) {
            $filters['division'] = $_GET['division'];
        }
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        if (isset($_GET['month']) && !empty($_GET['month'])) {
            $filters['month'] = $_GET['month'];
        }
        if (isset($_GET['search']['value']) && !empty($_GET['search']['value'])) {
            $filters['search'] = $_GET['search']['value'];
        }

        // Get data
        $employees = $employee->getAllEmployees($filters);
        
        // Format data for DataTables
        $data = [];
        foreach ($employees as $index => $emp) {
            $statusColors = [
                'Replacement' => 'bg-yellow-100 text-yellow-800',
                'New Headcount' => 'bg-green-100 text-green-800',
                'New Request' => 'bg-purple-100 text-purple-800'
            ];
            $colorClass = $statusColors[$emp['status_headcount']] ?? 'bg-gray-100 text-gray-800';
            
            $data[] = [
                $index + 1,
                htmlspecialchars($emp['nama']),
                htmlspecialchars($emp['division']),
                '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ' . $colorClass . '">' . 
                htmlspecialchars($emp['status_headcount']) . '</span>',
                $emp['replace_person'] ? htmlspecialchars($emp['replace_person']) : '-',
                date('d/m/Y', strtotime($emp['assign_month'])),
                '<div class="flex items-center gap-2">
                    <a href="edit_employee.php?id=' . $emp['id'] . '" 
                       class="action-btn inline-flex items-center justify-center w-8 h-8 text-blue-600 hover:text-white hover:bg-blue-600 rounded-lg border border-blue-200 hover:border-blue-600 transition-all duration-200"
                       title="Edit Karyawan">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </a>
                    <a href="delete_employee.php?id=' . $emp['id'] . '" 
                       class="action-btn inline-flex items-center justify-center w-8 h-8 text-red-600 hover:text-white hover:bg-red-600 rounded-lg border border-red-200 hover:border-red-600 transition-all duration-200"
                       onclick="return confirm(\'Yakin ingin menghapus karyawan ini?\')"
                       title="Hapus Karyawan">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </a>
                </div>'
            ];
        }

        echo json_encode([
            'draw' => intval($_GET['draw'] ?? 1),
            'recordsTotal' => count($employees),
            'recordsFiltered' => count($employees),
            'data' => $data
        ]);
        exit;
    }

    // Handle export requests
    if (isset($_GET['export']) && $_GET['export'] === 'xlsx') {
        // Get filters for export
        $filters = [];
        if (isset($_GET['division']) && !empty($_GET['division'])) {
            $filters['division'] = $_GET['division'];
        }
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        if (isset($_GET['month']) && !empty($_GET['month'])) {
            $filters['month'] = $_GET['month'];
        }
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }

        $employees = $employee->getAllEmployees($filters);
        
        // Set headers for CSV download (XLSX alternative)
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=data_karyawan_' . date('Y-m-d_H-i-s') . '.csv');
        header('Cache-Control: no-cache, must-revalidate');
        
        // Create output
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        
        // Headers
        $headers = ['No', 'Nama', 'Divisi', 'Status', 'Menggantikan', 'Tanggal Assignment'];
        fputcsv($output, $headers);
        
        // Data
        foreach ($employees as $index => $emp) {
            $row = [
                $index + 1,
                $emp['nama'],
                $emp['division'],
                $emp['status_headcount'],
                $emp['replace_person'] ?? '-',
                date('d/m/Y', strtotime($emp['assign_month']))
            ];
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }

    // Get data for initial page load
    $divisions = $employee->getDivisions();
    $statistics = $employee->getStatistics();
    $statusOptions = ['Replacement', 'New Headcount', 'New Request'];

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    include 'error_page.php';
    exit;
}
?>

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Satria HR System - Dashboard</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js for graphics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Custom scrollbar styling */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* DataTables custom styling */
        .dataTables_wrapper {
            font-family: 'Inter', sans-serif;
        }
        
        .dataTables_filter input {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.5rem 0.75rem !important;
            font-size: 0.875rem !important;
        }
        
        .dataTables_length select {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.25rem 0.5rem !important;
            font-size: 0.875rem !important;
        }
        
        .dataTables_info {
            color: #6b7280 !important;
            font-size: 0.875rem !important;
        }
        
        .dataTables_paginate .paginate_button {
            padding: 0.5rem 0.75rem !important;
            margin: 0 0.125rem !important;
            border-radius: 0.375rem !important;
            border: 1px solid #d1d5db !important;
            background: white !important;
            color: #374151 !important;
        }
        
        .dataTables_paginate .paginate_button:hover {
            background: #f9fafb !important;
            border-color: #d1d5db !important;
        }
        
        .dataTables_paginate .paginate_button.current {
            background: #3b82f6 !important;
            color: white !important;
            border-color: #3b82f6 !important;
        }
        
        .dt-buttons {
            display: none !important; /* Hide default DataTables buttons */
        }
        
        .dataTables_filter {
            display: none !important; /* Hide default DataTables search */
        }
        
        /* Custom Export Dropdown Styles */
        .export-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .export-dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            z-index: 1000;
            top: 100%;
            right: 0;
            margin-top: 0.25rem;
        }
        
        .export-dropdown-content.show {
            display: block;
        }
        
        .export-option {
            padding: 0.75rem 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.2s;
            font-size: 0.875rem;
            color: #374151;
        }
        
        .export-option:hover {
            background-color: #f9fafb;
        }
        
        .export-option:first-child {
            border-radius: 0.5rem 0.5rem 0 0;
        }
        
        .export-option:last-child {
            border-radius: 0 0 0.5rem 0.5rem;
        }
        
        .export-option svg {
            width: 1rem;
            height: 1rem;
        }
        
        .export-excel-icon { color: #059669; }
        .export-pdf-icon { color: #dc2626; }
        .export-print-icon { color: #7c3aed; }
        
        /* Action Buttons Styling */
        .action-btn {
            transition: all 0.2s ease-in-out;
            transform: scale(1);
        }
        
        .action-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .action-btn:active {
            transform: scale(0.95);
        }
        
        /* Success alert animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes slideUp {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-10px); }
        }
        
        .animate-slideUp {
            animation: slideUp 0.3s ease-in-out forwards;
        }
    </style>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        border: 'hsl(214.3, 31.8%, 91.4%)',
                        input: 'hsl(214.3, 31.8%, 91.4%)',
                        ring: 'hsl(222.2, 84%, 4.9%)',
                        background: 'hsl(0, 0%, 100%)',
                        foreground: 'hsl(222.2, 84%, 4.9%)',
                        primary: {
                            DEFAULT: 'hsl(221.2, 83.2%, 53.3%)',
                            foreground: 'hsl(210, 40%, 98%)',
                        },
                        secondary: {
                            DEFAULT: 'hsl(210, 40%, 96.1%)',
                            foreground: 'hsl(222.2, 47.4%, 11.2%)',
                        },
                        muted: {
                            DEFAULT: 'hsl(210, 40%, 96.1%)',
                            foreground: 'hsl(215.4, 16.3%, 46.9%)',
                        },
                        accent: {
                            DEFAULT: 'hsl(210, 40%, 96.1%)',
                            foreground: 'hsl(222.2, 47.4%, 11.2%)',
                        },
                        card: {
                            DEFAULT: 'hsl(0, 0%, 100%)',
                            foreground: 'hsl(222.2, 84%, 4.9%)',
                        },
                    },
                    borderRadius: {
                        lg: `0.5rem`,
                        md: `calc(0.5rem - 2px)`,
                        sm: `calc(0.5rem - 4px)`,
                    },
                }
            }
        }
    </script>
</head>
<body class="bg-muted/40 font-sans text-foreground antialiased">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="h-16 flex items-center justify-between px-6 border-b border-border bg-card flex-shrink-0 sticky top-0 z-10">
            <div class="max-w-6xl mx-auto w-full flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex items-center gap-3">
                        <!-- Logo Icon -->
                        <svg width="24" height="24" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-primary">
                           <path fill-rule="evenodd" clip-rule="evenodd" d="M22.5 25C22.5 23.6193 23.6193 22.5 25 22.5H75C76.3807 22.5 77.5 23.6193 77.5 25V37.5H22.5V25ZM22.5 42.5H77.5V57.5H22.5V42.5ZM22.5 62.5H77.5V75C77.5 76.3807 76.3807 77.5 75 77.5H25C23.6193 77.5 22.5 76.3807 22.5 75V62.5Z" fill="currentColor"/>
                        </svg>
                        <h1 class="text-xl font-semibold text-foreground">Satria HR System</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="refreshData()" class="px-4 py-2 text-sm font-medium border border-border rounded-lg flex items-center gap-2 hover:bg-accent transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh
                    </button>
                    
                    <!-- Export Dropdown -->
                    <div class="export-dropdown">
                        <button onclick="toggleExportDropdown()" class="px-4 py-2 text-sm font-medium bg-green-600 text-white rounded-lg flex items-center gap-2 hover:bg-green-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export Data
                            <svg class="w-4 h-4 transition-transform duration-200" id="export-dropdown-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="export-dropdown-content" id="export-dropdown-menu">
                            <div class="export-option" onclick="exportData('xlsx')">
                                <svg class="export-excel-icon" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                </svg>
                                <div>
                                    <div class="font-medium">Export Excel</div>
                                    <div class="text-xs text-gray-500">Unduh sebagai file .csv</div>
                                </div>
                            </div>
                            <div class="export-option" onclick="exportData('pdf')">
                                <svg class="export-pdf-icon" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                </svg>
                                <div>
                                    <div class="font-medium">Export PDF</div>
                                    <div class="text-xs text-gray-500">Unduh sebagai file PDF</div>
                                </div>
                            </div>
                            <div class="export-option" onclick="printData()">
                                <svg class="export-print-icon" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18,3H6V7H18M19,12A1,1 0 0,1 18,11A1,1 0 0,1 19,10A1,1 0 0,1 20,11A1,1 0 0,1 19,12M16,19H8V14H16M19,8H5A3,3 0 0,0 2,11V17H6V21H18V17H22V11A3,3 0 0,0 19,8Z"/>
                                </svg>
                                <div>
                                    <div class="font-medium">Print</div>
                                    <div class="text-xs text-gray-500">Cetak data langsung</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <a href="add_employee.php" class="px-4 py-2 text-sm font-medium bg-primary text-white rounded-lg flex items-center gap-2 hover:bg-primary/90 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Tambah Karyawan
                    </a>
                    <a href="import.php" class="px-4 py-2 text-sm font-medium border border-border rounded-lg flex items-center gap-2 hover:bg-accent transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                        </svg>
                        Import Excel
                    </a>
                </div>
            </div>
        </header>

        <main class="max-w-6xl mx-auto p-6 space-y-6">
            <!-- Success Message -->
            <?php if (isset($_GET['success']) && $_GET['success'] == '1' && isset($_GET['message'])): ?>
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg animate-fadeIn" id="success-alert">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span><?= htmlspecialchars($_GET['message']) ?></span>
                        </div>
                        <button onclick="closeSuccessAlert()" class="text-green-600 hover:text-green-800 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-card p-5 rounded-xl border border-border">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <p class="text-sm text-muted-foreground">Total Karyawan</p>
                            <p class="text-2xl font-bold mt-2"><?= number_format($statistics['total']) ?></p>
                        </div>
                        <div class="w-8 h-8 bg-blue-500/10 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-card p-5 rounded-xl border border-border">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <p class="text-sm text-muted-foreground">Replacement</p>
                            <p class="text-2xl font-bold mt-2 text-yellow-500"><?= $statistics['by_status']['Replacement'] ?? 0 ?></p>
                        </div>
                        <div class="w-8 h-8 bg-yellow-500/10 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.752.932A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1z"/>
                                <path d="M5.707 14.707A1 1 0 015 13.586V10a1 1 0 112 0v2.101a7.002 7.002 0 0011.601 2.566 1 1 0 11-1.752.932A5.002 5.002 0 0110.001 14H7a1 1 0 01-.293-.707z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-card p-5 rounded-xl border border-border">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <p class="text-sm text-muted-foreground">New Headcount</p>
                            <p class="text-2xl font-bold mt-2 text-green-500"><?= $statistics['by_status']['New Headcount'] ?? 0 ?></p>
                        </div>
                        <div class="w-8 h-8 bg-green-500/10 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-card p-5 rounded-xl border border-border">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <p class="text-sm text-muted-foreground">New Request</p>
                            <p class="text-2xl font-bold mt-2 text-purple-500"><?= $statistics['by_status']['New Request'] ?? 0 ?></p>
                        </div>
                        <div class="w-8 h-8 bg-purple-500/10 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Filters -->
            <div class="bg-card p-6 rounded-xl border border-border">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-foreground">Filter Data</h3>
                    <button onclick="resetFilters()" class="text-sm text-muted-foreground hover:text-foreground transition-colors">
                        Reset Semua Filter
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Divisi</label>
                        <select id="filter-division" class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-background">
                            <option value="">Semua Divisi</option>
                            <?php foreach ($divisions as $div): ?>
                                <option value="<?= htmlspecialchars($div) ?>"><?= htmlspecialchars($div) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Status</label>
                        <select id="filter-status" class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-background">
                            <option value="">Semua Status</option>
                            <?php foreach ($statusOptions as $status): ?>
                                <option value="<?= $status ?>"><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Bulan Assignment</label>
                        <input type="month" id="filter-month" class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-background">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Pencarian</label>
                        <div class="relative">
                            <input type="text" id="datatable-search" placeholder="Cari data..." class="w-full border border-border rounded-lg px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-background">
                            <svg class="absolute right-3 top-2.5 w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employee DataTable -->
            <div class="bg-card rounded-xl border border-border overflow-hidden">
                <div class="px-6 py-4 border-b border-border">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-foreground">Data Karyawan</h3>
                            <p class="text-sm text-muted-foreground mt-1">Kelola data karyawan dengan mudah</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-6">
                    <table id="employees-table" class="w-full table-auto">
                        <thead>
                            <tr>
                                <th class="text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">No</th>
                                <th class="text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Nama</th>
                                <th class="text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Divisi</th>
                                <th class="text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                                <th class="text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Menggantikan</th>
                                <th class="text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Tanggal</th>
                                <th class="text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        let employeesTable;
        
        $(document).ready(function() {
            // Initialize DataTable
            employeesTable = $('#employees-table').DataTable({
                processing: true,
                serverSide: false,
                searching: true, // Enable searching but hide the default search box
                ajax: {
                    url: '?ajax=true',
                    data: function(d) {
                        d.division = $('#filter-division').val();
                        d.status = $('#filter-status').val();
                        d.month = $('#filter-month').val();
                        // Don't pass global search here as DataTables handles it
                    }
                },
                columns: [
                    { data: 0, orderable: false, width: '60px' },
                    { data: 1, name: 'nama' },
                    { data: 2, name: 'division' },
                    { data: 3, name: 'status', orderable: false, width: '120px' },
                    { data: 4, name: 'replace_person', orderable: false },
                    { data: 5, name: 'assign_month', width: '100px' },
                    { data: 6, name: 'actions', orderable: false, width: '90px' }
                ],
                dom: 'Blrtip', // Keep buttons but hide them with CSS, hide default search (f)
                buttons: [
                    {
                        extend: 'pdf',
                        title: 'Data Karyawan - Satria HR System',
                        orientation: 'landscape',
                        pageSize: 'A4',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5] // Exclude actions column
                        },
                        customize: function(doc) {
                            // Customize PDF styling
                            doc.defaultStyle.fontSize = 9;
                            doc.styles.tableHeader.fontSize = 10;
                            doc.styles.tableHeader.fillColor = '#f8f9fa';
                            doc.styles.title.fontSize = 16;
                            doc.styles.title.alignment = 'center';
                            doc.content[1].margin = [0, 10, 0, 10];
                            
                            // Add date
                            doc.content.splice(1, 0, {
                                text: 'Dicetak pada: ' + new Date().toLocaleDateString('id-ID') + ' pukul ' + new Date().toLocaleTimeString('id-ID'),
                                style: { fontSize: 10, alignment: 'center' },
                                margin: [0, 0, 0, 10]
                            });
                        }
                    }
                ],
                pageLength: 25,
                lengthMenu: [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"] ],
                language: {
                    processing: "Memproses...",
                    search: "", // Hide default search label
                    searchPlaceholder: "",
                    lengthMenu: "Tampilkan _MENU_ data per halaman",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                    infoFiltered: "(disaring dari _MAX_ total data)",
                    loadingRecords: "Memuat...",
                    zeroRecords: "Tidak ada data yang cocok",
                    emptyTable: "Tidak ada data tersedia",
                    paginate: {
                        first: "Pertama",
                        previous: "Sebelumnya", 
                        next: "Selanjutnya",
                        last: "Terakhir"
                    }
                },
                drawCallback: function(settings) {
                    // DataTable callback after draw
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).addClass('hover:bg-muted/50 transition-colors');
                    $(row).find('td').addClass('px-6 py-4 whitespace-nowrap text-sm');
                }
            });

            // Filter event handlers
            $('#filter-division, #filter-status, #filter-month').on('change', function() {
                employeesTable.ajax.reload();
            });

            // DataTable search (moved to filter card)
            $('#datatable-search').on('keyup', function() {
                employeesTable.search(this.value).draw();
            });
        });

        // Export function
        function exportData(format) {
            // Close dropdown after export
            document.getElementById('export-dropdown-menu').classList.remove('show');
            document.getElementById('export-dropdown-arrow').style.transform = 'rotate(0deg)';
            
            if (format === 'pdf') {
                // Use DataTables built-in PDF export
                employeesTable.button(0).trigger();
            } else {
                const params = new URLSearchParams({
                    export: format,
                    division: $('#filter-division').val(),
                    status: $('#filter-status').val(),
                    month: $('#filter-month').val(),
                    search: $('#datatable-search').val()
                });
                
                window.location.href = '?' + params.toString();
            }
        }

        // Print function
        function printData() {
            // Close dropdown
            document.getElementById('export-dropdown-menu').classList.remove('show');
            document.getElementById('export-dropdown-arrow').style.transform = 'rotate(0deg)';
            
            // Get current filtered data
            const data = employeesTable.rows({ search: 'applied' }).data();
            
            // Create print window
            const printWindow = window.open('', '_blank');
            const printContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Data Karyawan - Satria HR System</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .header h1 { margin: 0; color: #1f2937; }
                        .header p { margin: 5px 0; color: #6b7280; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; font-size: 12px; }
                        th { background-color: #f9fafb; font-weight: bold; }
                        .status-replacement { background-color: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; }
                        .status-new-headcount { background-color: #d1fae5; color: #065f46; padding: 4px 8px; border-radius: 4px; }
                        .status-new-request { background-color: #e0e7ff; color: #3730a3; padding: 4px 8px; border-radius: 4px; }
                        @media print {
                            body { margin: 0; }
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>Data Karyawan</h1>
                        <p>Satria HR System</p>
                        <p>Dicetak pada: ${new Date().toLocaleDateString('id-ID')} pukul ${new Date().toLocaleTimeString('id-ID')}</p>
                        <p>Total: ${data.length} karyawan</p>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Divisi</th>
                                <th>Status</th>
                                <th>Menggantikan</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${Array.from(data).map((row, index) => `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${row[1]}</td>
                                    <td>${row[2]}</td>
                                    <td>${row[3].replace(/<[^>]*>/g, '')}</td>
                                    <td>${row[4]}</td>
                                    <td>${row[5]}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </body>
                </html>
            `;
            
            printWindow.document.write(printContent);
            printWindow.document.close();
            
            // Wait for content to load then print
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        }

        // Toggle export dropdown
        function toggleExportDropdown() {
            const dropdown = document.getElementById('export-dropdown-menu');
            const arrow = document.getElementById('export-dropdown-arrow');
            
            if (dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
                arrow.style.transform = 'rotate(0deg)';
            } else {
                dropdown.classList.add('show');
                arrow.style.transform = 'rotate(180deg)';
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.export-dropdown');
            const dropdownMenu = document.getElementById('export-dropdown-menu');
            const arrow = document.getElementById('export-dropdown-arrow');
            
            if (!dropdown.contains(event.target)) {
                dropdownMenu.classList.remove('show');
                arrow.style.transform = 'rotate(0deg)';
            }
        });

        // Close success alert
        function closeSuccessAlert() {
            const alert = document.getElementById('success-alert');
            if (alert) {
                alert.classList.add('animate-slideUp');
                setTimeout(() => {
                    alert.remove();
                    // Update URL to remove success parameters
                    const url = new URL(window.location);
                    url.searchParams.delete('success');
                    url.searchParams.delete('message');
                    window.history.replaceState({}, '', url);
                }, 300);
            }
        }

        // Auto-hide success alert after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successAlert = document.getElementById('success-alert');
            if (successAlert) {
                setTimeout(() => {
                    closeSuccessAlert();
                }, 5000);
            }
        });

        // Refresh data
        function refreshData() {
            employeesTable.ajax.reload(null, false);
            
            // Show refresh animation
            const refreshBtn = event.target.closest('button');
            const icon = refreshBtn.querySelector('svg');
            icon.classList.add('animate-spin');
            
            setTimeout(() => {
                icon.classList.remove('animate-spin');
            }, 1000);
        }

        // Reset all filters
        function resetFilters() {
            $('#filter-division').val('');
            $('#filter-status').val(''); 
            $('#filter-month').val('');
            $('#datatable-search').val('');
            employeesTable.search('').ajax.reload();
        }
    </script>
</body>
</html>