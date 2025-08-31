<?php
/**
 * Simple Excel Import
 * File: import_simple.php
 * Copy this content to your import.php file
 */

require_once 'models/Employee.php';

$message = '';
$error = '';
$importResults = null;

// Handle file upload
if ($_POST && isset($_FILES['excel_file'])) {
    $uploadedFile = $_FILES['excel_file'];
    
    // Validate file
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        $error = 'Terjadi kesalahan saat upload file';
    } elseif (empty($uploadedFile['name'])) {
        $error = 'Silakan pilih file untuk diupload';
    } else {
        // Process file (simple CSV processing for demo)
        try {
            $employee = new Employee();
            
            // Read file as CSV
            $handle = fopen($uploadedFile['tmp_name'], 'r');
            if ($handle) {
                $employeesData = [];
                $rowNumber = 0;
                
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $rowNumber++;
                    
                    if ($rowNumber === 1) {
                        // Skip header row
                        continue;
                    }
                    
                    // Map data based on expected columns
                    if (count($data) >= 4) {
                        $employeesData[] = [
                            'nama' => trim($data[0]),
                            'division' => trim($data[1]),
                            'status_headcount' => trim($data[2]),
                            'replace_person' => !empty(trim($data[3])) ? trim($data[3]) : null,
                            'assign_month' => isset($data[4]) && !empty($data[4]) ? date('Y-m-d', strtotime(trim($data[4]))) : date('Y-m-d')
                        ];
                    }
                }
                
                fclose($handle);
                
                if (!empty($employeesData)) {
                    $importResults = $employee->importEmployees($employeesData);
                    if ($importResults['success']) {
                        $message = "Berhasil mengimpor {$importResults['imported']} karyawan";
                    } else {
                        $error = "Gagal mengimpor data: " . ($importResults['error'] ?? 'Unknown error');
                    }
                } else {
                    $error = 'Tidak ada data valid dalam file';
                }
            } else {
                $error = 'Tidak dapat membaca file';
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan saat memproses file: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Excel - Satria HR System</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                        <h1 class="text-xl font-semibold text-gray-900">Import Data Excel</h1>
                    </div>
                    <a href="index.php" class="text-gray-600 hover:text-gray-900">
                        Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?= htmlspecialchars($message) ?>
                    <div class="mt-2">
                        <a href="index.php" class="text-green-800 hover:text-green-900 underline">
                            Lihat Data yang Diimpor
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Upload Form -->
                <div class="bg-white shadow-sm rounded-lg border">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Upload File Excel/CSV</h2>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" class="p-6">
                        <div class="space-y-6">
                            <div>
                                <label for="excel_file" class="block text-sm font-medium text-gray-700 mb-2">
                                    Pilih File Excel/CSV <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600">
                                            <label for="excel_file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                                <span>Upload file</span>
                                                <input id="excel_file" name="excel_file" type="file" class="sr-only" accept=".xls,.xlsx,.csv" required>
                                            </label>
                                            <p class="pl-1">atau drag and drop</p>
                                        </div>
                                        <p class="text-xs text-gray-500">Excel (.xlsx, .xls) atau CSV hingga 10MB</p>
                                    </div>
                                </div>
                                <div id="file-info" class="mt-2 text-sm text-gray-600 hidden"></div>
                            </div>

                            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                                <div class="flex">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-blue-800">Format File yang Diharapkan</h3>
                                        <div class="mt-2 text-sm text-blue-700">
                                            <p>Kolom yang dibutuhkan (urutan sesuai):</p>
                                            <ol class="list-decimal list-inside mt-1">
                                                <li>Nama</li>
                                                <li>Division</li>
                                                <li>Status Headcount</li>
                                                <li>Replace (opsional)</li>
                                                <li>assign month (format: YYYY-MM-DD)</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-4">
                                <a href="index.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Batal
                                </a>
                                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Import Data
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Instructions -->
                <div class="bg-white shadow-sm rounded-lg border">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Instruksi Import</h2>
                    </div>
                    
                    <div class="p-6 space-y-4">
                        <div class="space-y-2">
                            <h3 class="font-medium text-gray-900">1. Format File</h3>
                            <p class="text-sm text-gray-600">
                                Upload file Excel (.xlsx, .xls) atau CSV dengan struktur kolom yang sesuai.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <h3 class="font-medium text-gray-900">2. Struktur Data</h3>
                            <p class="text-sm text-gray-600">
                                Pastikan baris pertama adalah header kolom, dan data dimulai dari baris kedua.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <h3 class="font-medium text-gray-900">3. Status Headcount</h3>
                            <p class="text-sm text-gray-600">
                                Nilai yang valid: "Replacement", "New Headcount", "New Request"
                            </p>
                        </div>

                        <div class="space-y-2">
                            <h3 class="font-medium text-gray-900">4. Data Duplikat</h3>
                            <p class="text-sm text-gray-600">
                                Jika nama karyawan sudah ada, data akan diperbarui dengan informasi terbaru.
                            </p>
                        </div>

                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-800">
                                        <strong>Peringatan:</strong> Pastikan data sudah benar sebelum import. 
                                        Backup data lama jika diperlukan.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sample Template -->
            <div class="mt-8 bg-white shadow-sm rounded-lg border">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Template CSV</h2>
                </div>
                
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-4">
                        Contoh format file CSV yang dapat digunakan:
                    </p>
                    
                    <div class="bg-gray-50 rounded-md p-4 text-sm font-mono">
                        Nama,Division,Status Headcount,Replace,assign month<br>
                        John Doe,IT,New Headcount,,2024-01-15<br>
                        Jane Smith,Finance,Replacement,Mike Johnson,2024-01-20<br>
                        Ahmad Satria,HR,New Request,,2024-02-01
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Handle file selection
        document.getElementById('excel_file').addEventListener('change', function() {
            const fileInfo = document.getElementById('file-info');
            const file = this.files[0];
            
            if (file) {
                fileInfo.innerHTML = `
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        File terpilih: <strong>${file.name}</strong> (${(file.size/1024/1024).toFixed(2)} MB)
                    </div>
                `;
                fileInfo.classList.remove('hidden');
            } else {
                fileInfo.classList.add('hidden');
            }
        });
    </script>
</body>
</html>