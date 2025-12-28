@echo off
echo Moving unused files to backup directory...

REM Root directory files
move debug_payslips.php backup_unused_files\
move check_payslip_duplicates.php backup_unused_files\
move add_status_column.php backup_unused_files\
move fpdf.zip backup_unused_files\
move delete_salary.php backup_unused_files\
move check_salary.php backup_unused_files\
move test_session.php backup_unused_files\
move sync_attendance.php backup_unused_files\
move check_user_attendance.php backup_unused_files\
move describe_table.php backup_unused_files\
move db_fix.php backup_unused_files\
move payslip.php backup_unused_files\

REM Create subdirectories in backup
mkdir backup_unused_files\temp_fpdf
mkdir backup_unused_files\api
mkdir backup_unused_files\controller
mkdir backup_unused_files\view\users
mkdir backup_unused_files\uploads\payslips

REM API directory files
move Desktop\AR_Attendance\api\test.php backup_unused_files\api\

REM Controller directory files
move Desktop\AR_Attendance\controller\test_fpdf.php backup_unused_files\controller\
move Desktop\AR_Attendance\controller\check_payslip_table.php backup_unused_files\controller\
move Desktop\AR_Attendance\controller\check_user_payslips.php backup_unused_files\controller\
move Desktop\AR_Attendance\controller\diagnose_payslips.php backup_unused_files\controller\
move Desktop\AR_Attendance\controller\check_salary_structure.php backup_unused_files\controller\
move Desktop\AR_Attendance\controller\salary_table_fix.php backup_unused_files\controller\

REM View directory files
move Desktop\AR_Attendance\view\users\HistoryDebug.php backup_unused_files\view\users\
move Desktop\AR_Attendance\view\users\payslip_clean.php backup_unused_files\view\users\

REM Test uploads
move Desktop\AR_Attendance\uploads\payslips\test_payslip.txt backup_unused_files\uploads\payslips\

REM Move temp_fpdf directory (recursive)
xcopy temp_fpdf backup_unused_files\temp_fpdf /E /I /Y
rd /s /q temp_fpdf

echo Cleanup completed. All unused files are now in the backup_unused_files directory.
echo Please verify the application still works correctly before deleting the backup. 