# ==============================================================================
# StockEdp - Automated Database Backup Script (PowerShell)
# Target Default: D:\ADM
# Jadwal: Setiap malam pukul 23:00 WIB
# Fitur: mysqldump (InnoDB single-transaction), zip compression, retensi 14 hari
# ==============================================================================

param(
    [string]$BackupDir = "D:\ADM",
    [int]$RetentionDays = 14
)

$ErrorActionPreference = "Stop"

# 1. Pastikan Direktori Backup Ada
if (-not (Test-Path -Path $BackupDir)) {
    try {
        New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null
        Write-Host "[INFO] Membuat direktori backup: $BackupDir"
    } catch {
        Write-Error "[ERROR] Gagal membuat direktori backup '$BackupDir': $_"
        exit 1
    }
}

$logFile = Join-Path $BackupDir "backup.log"

function Write-Log {
    param([string]$Message)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $line = "[$timestamp] $Message"
    Write-Host $line
    Add-Content -Path $logFile -Value $line -Encoding utf8
}

Write-Log "======================================================"
Write-Log "Memulai backup database 'stockedp' ke '$BackupDir'"

# 2. Lokasi mysqldump
$mysqldumpPath = "D:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe"
if (-not (Test-Path $mysqldumpPath)) {
    # Coba cari di PATH
    $cmd = Get-Command mysqldump.exe -ErrorAction SilentlyContinue
    if ($cmd) {
        $mysqldumpPath = $cmd.Source
    } else {
        Write-Log "[ERROR] mysqldump.exe tidak ditemukan di '$mysqldumpPath' maupun sistem PATH."
        exit 1
    }
}

# 3. Konfigurasi Database
$dbHost = "127.0.0.1"
$dbPort = "3306"
$dbUser = "root"
$dbPass = ""
$dbName = "stockedp"

# 4. Nama File Backup
$timeStr = Get-Date -Format "yyyyMMdd_HHmmss"
$sqlFile = Join-Path $BackupDir "${dbName}_backup_${timeStr}.sql"
$zipFile = Join-Path $BackupDir "${dbName}_backup_${timeStr}.zip"

# 5. Jalankan mysqldump dengan single-transaction untuk InnoDB aman tanpa downtime
$dumpArgs = @(
    "-h", $dbHost,
    "-P", $dbPort,
    "-u", $dbUser,
    "--single-transaction",
    "--quick",
    "--routines",
    "--triggers",
    "--default-character-set=utf8mb4",
    $dbName
)

if ($dbPass -ne "") {
    $dumpArgs = @("-p$dbPass") + $dumpArgs
}

try {
    Write-Log "Menjalankan mysqldump untuk database '$dbName'..."
    $process = Start-Process -FilePath $mysqldumpPath -ArgumentList $dumpArgs -RedirectStandardOutput $sqlFile -RedirectStandardError (Join-Path $BackupDir "dump_err.tmp") -NoNewWindow -Wait -PassThru

    if ($process.ExitCode -ne 0) {
        $errContent = ""
        if (Test-Path (Join-Path $BackupDir "dump_err.tmp")) {
            $errContent = Get-Content (Join-Path $BackupDir "dump_err.tmp") -Raw
            Remove-Item (Join-Path $BackupDir "dump_err.tmp") -Force -ErrorAction SilentlyContinue
        }
        Write-Log "[ERROR] mysqldump gagal dengan exit code $($process.ExitCode). Pesan: $errContent"
        exit $process.ExitCode
    }

    if (Test-Path (Join-Path $BackupDir "dump_err.tmp")) {
        Remove-Item (Join-Path $BackupDir "dump_err.tmp") -Force -ErrorAction SilentlyContinue
    }

    if (-not (Test-Path $sqlFile)) {
        Write-Log "[ERROR] File dump SQL '$sqlFile' tidak tercipta."
        exit 1
    }

    $sqlItem = Get-Item $sqlFile
    if ($sqlItem.Length -eq 0) {
        Write-Log "[ERROR] File dump SQL '$sqlFile' kosong (0 bytes)."
        Remove-Item $sqlFile -Force
        exit 1
    }

    $sqlSizeKB = [math]::Round($sqlItem.Length / 1KB, 2)
    Write-Log "Dump SQL berhasil dibuat ($sqlSizeKB KB). Mengompresi ke ZIP..."

    # 6. Kompresi ZIP
    Compress-Archive -LiteralPath $sqlFile -DestinationPath $zipFile -Force
    
    # Hapus file .sql setelah zip selesai dibuat
    Remove-Item $sqlFile -Force

    $zipItem = Get-Item $zipFile
    $zipSizeKB = [math]::Round($zipItem.Length / 1KB, 2)
    Write-Log "[SUKSES] Backup berhasil dibuat dan terkompresi: $zipFile ($zipSizeKB KB)"

} catch {
    Write-Log "[EXCEPTION] Terjadi error saat backup: $_"
    exit 1
}

# 7. Pembersihan Retensi Otomatis (> 14 hari)
try {
    Write-Log "Memeriksa file backup yang berusia lebih dari $RetentionDays hari di '$BackupDir'..."
    $cutoff = (Get-Date).AddDays(-$RetentionDays)
    $oldBackups = Get-ChildItem -Path $BackupDir -Filter "${dbName}_backup_*.zip" | Where-Object { $_.LastWriteTime -lt $cutoff }

    if ($oldBackups.Count -gt 0) {
        foreach ($old in $oldBackups) {
            Write-Log "Menghapus backup lama: $($old.Name) (dibuat: $($old.LastWriteTime))"
            Remove-Item $old.FullName -Force
        }
    } else {
        Write-Log "Tidak ada backup lama yang perlu dihapus."
    }
} catch {
    Write-Log "[WARNING] Gagal saat membersihkan backup lama: $_"
}

Write-Log "Proses backup database tuntas."
Write-Log "======================================================"
exit 0
