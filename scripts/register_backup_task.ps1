# ==============================================================================
# StockEdp - Windows Task Scheduler Registration
# Mendaftarkan task otomatisasi backup database setiap hari pukul 23:00 WIB
# ==============================================================================

$taskName = "StockEdp_Database_Backup"
$batPath = "D:\laragon\www\StockEdp\scripts\backup_db.bat"
$time = "23:00"

Write-Host "======================================================"
Write-Host "Pendaftaran Tugas Terjadwal (Windows Task Scheduler)"
Write-Host "Nama Tugas : $taskName"
Write-Host "File Script: $batPath"
Write-Host "Jadwal     : Setiap hari pukul $time WIB"
Write-Host "Tujuan     : D:\ADM"
Write-Host "======================================================"

# Perintah schtasks untuk mendaftarkan task harian
$schArgs = @(
    "/Create",
    "/SC", "DAILY",
    "/TN", $taskName,
    "/TR", "`"$batPath`"",
    "/ST", $time,
    "/F"
)

$res = Start-Process -FilePath "schtasks.exe" -ArgumentList $schArgs -Wait -PassThru -NoNewWindow

if ($res.ExitCode -eq 0) {
    Write-Host "`n[SUKSES] Task '$taskName' berhasil didaftarkan ke Windows Task Scheduler."
    Write-Host "Backup otomatis akan dijalankan setiap hari pada pukul $time WIB."
} else {
    Write-Host "`n[INFO] schtasks memerlukan hak akses administrator. Jalankan terminal sebagai Administrator jika diperlukan."
}
