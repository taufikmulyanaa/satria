<?php
/**
 * Add Employee Form
 * File: add_employee.php
 */

require_once 'models/Employee.php';

$employee = new Employee();
$message = '';
$error = '';

// Handle form submission
if ($_POST) {
    $data = [
        'nama' => trim($_POST['nama']),
        'division' => trim($_POST['division']),
        'status_headcount' => $_POST['status_headcount'],
        'replace_person' => !empty($_POST['replace_person']) ? trim($_POST['replace_person']) : null,
        'assign_month' => $_POST['assign_month']
    ];
    
    // Handle custom division
    if ($_POST['division'] === 'other' && !empty($_POST['custom_division'])) {
        $data['division'] = trim($_POST['custom_division']);
    }
    
    // Validation
    if (empty($data['nama'])) {
        $error = 'Nama harus diisi';
    } elseif (empty($data['division'])) {
        $error = 'Divisi harus diisi';
    } elseif (empty($data['status_headcount'])) {
        $error = 'Status headcount harus dipilih';
    } elseif (empty($data['assign_month'])) {
        $error = 'Tanggal assignment harus diisi';
    } else {
        if ($employee->createEmployee($data)) {
            $message = 'Karyawan berhasil ditambahkan!';
            // Reset form
            $_POST = [];
        } else {
            $error = 'Terjadi kesalahan saat menambahkan karyawan';
        }
    }
}

$divisions = $employee->getDivisions();
$statusOptions = ['Replacement', 'New Headcount', 'New Request'];
?>

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Karyawan - Satria HR System</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <a href="index.php" class="text-blue-600 hover:text-blue-800 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </a>
                        <h1 class="text-xl font-semibold text-gray-900">Tambah Karyawan</h1>
                    </div>
                    <a href="index.php" class="text-gray-600 hover:text-gray-900">
                        Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </header>

        <main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <div class="bg-white shadow-sm rounded-lg border">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Data Karyawan Baru</h2>
                </div>
                
                <form method="POST" class="p-6 space-y-6">
                    <div>
                        <label for="nama" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="nama" 
                            name="nama" 
                            value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                        >
                    </div>

                    <div>
                        <label for="division" class="block text-sm font-medium text-gray-700 mb-2">
                            Divisi <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="division" 
                            name="division" 
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                            <option value="">Pilih Divisi</option>
                            <?php foreach ($divisions as $div): ?>
                                <option value="<?= htmlspecialchars($div) ?>" <?= isset($_POST['division']) && $_POST['division'] === $div ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($div) ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="other">Divisi Lainnya...</option>
                        </select>
                    </div>

                    <!-- Custom division input -->
                    <div id="custom-division" class="hidden">
                        <label for="custom_division" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Divisi Baru
                        </label>
                        <input 
                            type="text" 
                            id="custom_division" 
                            name="custom_division" 
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Masukkan nama divisi baru"
                        >
                    </div>

                    <div>
                        <label for="status_headcount" class="block text-sm font-medium text-gray-700 mb-2">
                            Status Headcount <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="status_headcount" 
                            name="status_headcount" 
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                            <option value="">Pilih Status</option>
                            <?php foreach ($statusOptions as $status): ?>
                                <option value="<?= $status ?>" <?= isset($_POST['status_headcount']) && $_POST['status_headcount'] === $status ? 'selected' : '' ?>>
                                    <?= $status ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="replace-person-field" class="hidden">
                        <label for="replace_person" class="block text-sm font-medium text-gray-700 mb-2">
                            Menggantikan
                        </label>
                        <input 
                            type="text" 
                            id="replace_person" 
                            name="replace_person" 
                            value="<?= htmlspecialchars($_POST['replace_person'] ?? '') ?>"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Nama orang yang digantikan"
                        >
                    </div>

                    <div>
                        <label for="assign_month" class="block text-sm font-medium text-gray-700 mb-2">
                            Tanggal Assignment <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="date" 
                            id="assign_month" 
                            name="assign_month" 
                            value="<?= $_POST['assign_month'] ?? date('Y-m-d') ?>"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                        >
                    </div>

                    <div class="flex justify-end space-x-4 pt-6 border-t">
                        <a href="index.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Batal
                        </a>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Tambah Karyawan
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Handle division selection
        document.getElementById('division').addEventListener('change', function() {
            const customDiv = document.getElementById('custom-division');
            const customInput = document.getElementById('custom_division');
            
            if (this.value === 'other') {
                customDiv.classList.remove('hidden');
                customInput.required = true;
                // Set the custom division value to the hidden input
                customInput.addEventListener('input', function() {
                    document.getElementById('division').setAttribute('data-custom-value', this.value);
                });
            } else {
                customDiv.classList.add('hidden');
                customInput.required = false;
                customInput.value = '';
            }
        });

        // Handle status selection to show/hide replace person field
        document.getElementById('status_headcount').addEventListener('change', function() {
            const replaceField = document.getElementById('replace-person-field');
            const replaceInput = document.getElementById('replace_person');
            
            if (this.value === 'Replacement') {
                replaceField.classList.remove('hidden');
                replaceInput.required = true;
            } else {
                replaceField.classList.add('hidden');
                replaceInput.required = false;
                replaceInput.value = '';
            }
        });

        // Form submission handler for custom division
        document.querySelector('form').addEventListener('submit', function(e) {
            const divisionSelect = document.getElementById('division');
            const customInput = document.getElementById('custom_division');
            
            if (divisionSelect.value === 'other' && customInput.value) {
                // Create a hidden input to submit the custom division
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'division';
                hiddenInput.value = customInput.value;
                this.appendChild(hiddenInput);
                
                // Remove the name from division select to avoid conflict
                divisionSelect.removeAttribute('name');
            }
        });

        // Initialize fields based on current values
        document.addEventListener('DOMContentLoaded', function() {
            // Check status on load
            const statusSelect = document.getElementById('status_headcount');
            if (statusSelect.value === 'Replacement') {
                document.getElementById('replace-person-field').classList.remove('hidden');
            }
        });
    </script>
</body>
</html>