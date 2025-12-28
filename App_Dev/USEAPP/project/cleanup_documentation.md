# AttendRoll Codebase Cleanup Documentation

This document explains the files being removed from the codebase and why they are considered unnecessary for the core functionality of the AttendRoll system.

## Root Directory Files

| File | Reason for Removal |
|------|-------------------|
| debug_payslips.php | Debug file used for testing payslip functionality, not needed in production |
| check_payslip_duplicates.php | Utility file for debugging duplicate payslip issues |
| add_status_column.php | One-time database modification script for adding a status column |
| fpdf.zip | Source archive, not needed as FPDF is already extracted and integrated |
| delete_salary.php | Utility script for deleting salary records, not part of main application flow |
| check_salary.php | Debug/utility script for checking salary calculations |
| test_session.php | Testing file for session functionality |
| sync_attendance.php | Utility script for synchronizing attendance records |
| check_user_attendance.php | Debug/utility script for checking user attendance |
| describe_table.php | Database inspection utility for development purposes |
| db_fix.php | Database repair script, not needed after execution |
| payslip.php | Appears to be a test or alternate version of payslip handling |
| temp_fpdf/ | Temporary FPDF files, the actual FPDF library is already included in the controller/fpdf directory |

## API Directory Files

| File | Reason for Removal |
|------|-------------------|
| Desktop/AR_Attendance/api/test.php | Test file for API functionality |

## Controller Directory Files

| File | Reason for Removal |
|------|-------------------|
| Desktop/AR_Attendance/controller/test_fpdf.php | Test file for FPDF integration |
| Desktop/AR_Attendance/controller/check_payslip_table.php | Debug file for checking payslip table structure |
| Desktop/AR_Attendance/controller/check_user_payslips.php | Debug file for checking user payslips |
| Desktop/AR_Attendance/controller/diagnose_payslips.php | Debug file for diagnosing payslip issues |
| Desktop/AR_Attendance/controller/check_salary_structure.php | Debug file for checking salary structure |
| Desktop/AR_Attendance/controller/salary_table_fix.php | One-time fix script for salary table |

## View Directory Files

| File | Reason for Removal |
|------|-------------------|
| Desktop/AR_Attendance/view/users/HistoryDebug.php | Debug version of history view |
| Desktop/AR_Attendance/view/users/payslip_clean.php | Test or alternate implementation of payslip view |

## Upload Directory Files

| File | Reason for Removal |
|------|-------------------|
| Desktop/AR_Attendance/uploads/payslips/test_payslip.txt | Test payslip file |

## How to Restore Files If Needed

If you discover that any of the removed files are actually needed, they can be found in the `backup_unused_files` directory with their original directory structure preserved. Simply move them back to their original location.

## Verification

After cleanup, please verify that all core functionality of the AttendRoll system continues to work as expected:

1. User authentication (login/registration)
2. Employee management
3. Attendance tracking
4. Payroll calculation
5. Payslip generation and viewing
6. Admin dashboard functionality
7. Mobile app functionality

If any issues are detected, check if they're related to the removed files and restore as necessary. 