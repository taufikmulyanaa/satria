<?php
/**
 * Employee API Endpoint
 * File: api/employees.php
 * 
 * Simple REST API for employee data
 * Usage:
 * GET /api/employees.php - Get all employees
 * GET /api/employees.php?id=1 - Get specific employee
 * GET /api/employees.php?division=IT - Filter by division
 * GET /api/employees.php?status=Active - Filter by status
 * GET /api/employees.php?stats=true - Get statistics only
 */

// Set content type to JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests for now
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'error' => 'Method not allowed',
        'message' => 'Only GET requests are supported'
    ]);
    exit;
}

// Include required files
require_once '../models/Employee.php';

try {
    $employee = new Employee();
    
    // Check if requesting statistics only
    if (isset($_GET['stats']) && $_GET['stats'] === 'true') {
        $stats = $employee->getStatistics();
        
        echo json_encode([
            'success' => true,
            'data' => $stats,
            'timestamp' => date('c')
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Get specific employee by ID
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $emp = $employee->getEmployeeById($id);
        
        if ($emp) {
            echo json_encode([
                'success' => true,
                'data' => $emp,
                'timestamp' => date('c')
            ], JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Employee not found',
                'message' => "Employee with ID {$id} does not exist"
            ]);
        }
        exit;
    }
    
    // Prepare filters
    $filters = [];
    
    // Filter by division
    if (isset($_GET['division']) && !empty($_GET['division'])) {
        $filters['division'] = $_GET['division'];
    }
    
    // Filter by status
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $filters['status'] = $_GET['status'];
    }
    
    // Filter by month
    if (isset($_GET['month']) && !empty($_GET['month'])) {
        $filters['month'] = $_GET['month'];
    }
    
    // Search filter
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $filters['search'] = $_GET['search'];
    }
    
    // Pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 50;
    $offset = ($page - 1) * $limit;
    
    // Get employees
    $employees = $employee->getAllEmployees($filters);
    
    // Apply pagination
    $total = count($employees);
    $employees = array_slice($employees, $offset, $limit);
    
    // Prepare response
    $response = [
        'success' => true,
        'data' => $employees,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit),
            'has_next' => $page < ceil($total / $limit),
            'has_prev' => $page > 1
        ],
        'filters' => $filters,
        'timestamp' => date('c')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => 'An error occurred while processing your request',
        'timestamp' => date('c')
    ]);
    
    // Log the error
    error_log("API Error: " . $e->getMessage());
}
?>