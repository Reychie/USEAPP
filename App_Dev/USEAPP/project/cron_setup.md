# Setting Up Automatic Payslip Generation

This document explains how to set up the automatic payslip generation system that will create payslips when employees reach 22 workdays in a month.

## Overview

The system consists of three components:

1. **Backend API**: A new endpoint in the payslip API that checks work days and generates payslips
2. **Scheduler**: A cron job that runs daily to check all employees' work days
3. **Mobile/Web Interface**: Notifications in the employee dashboard when payslips are generated

## Setting Up the Cron Job

### Windows (Using Task Scheduler)

1. Create a batch file (e.g., `run_payslip_check.bat`) with the following content:

```batch
@echo off
cd C:\xampp\htdocs\app_dev_last
C:\xampp\php\php.exe check_and_generate_payslips.php
```

2. Open Task Scheduler (search for it in the Start menu)
3. Click "Create Basic Task..."
4. Enter a name like "Daily Payslip Check" and click Next
5. Select "Daily" and click Next
6. Choose a start time (e.g., 12:00 AM) and click Next
7. Select "Start a program" and click Next
8. Browse to select your batch file and click Next
9. Click Finish

### Linux/Unix (Using crontab)

1. Open terminal
2. Type `crontab -e` to edit your cron jobs
3. Add the following line to run the script at midnight every day:

```
0 0 * * * /usr/bin/php /path/to/your/app_dev_last/check_and_generate_payslips.php >> /path/to/your/app_dev_last/payslip_cron.log 2>&1
```

4. Save and exit

### Manual Testing

To test the automatic payslip generation manually:

```
cd C:\xampp\htdocs\app_dev_last
php check_and_generate_payslips.php
```

## How the System Works

1. The cron job runs daily and checks all active employees
2. For each employee, it counts the number of present days in the current month
3. If an employee has 22 or more present days and doesn't already have a payslip:
   - It checks if a salary record exists for the month
   - It generates a payslip if all conditions are met
4. When employees log in to the mobile app, they'll receive a notification if a payslip was generated

## Integration Points

- `update_salary_table.php`: Script to check and generate payslips manually
- `check_and_generate_payslips.php`: Script for the cron job
- `Desktop/AR_Attendance/api/payslip.php`: API with new `check_work_days` endpoint
- Mobile app Employee Dashboard: Shows notification when payslip is generated

## Troubleshooting

Check the log files for issues:
- `payslip_generator.log`: Contains logs from the cron job
- PHP error logs: Check your server's error logs for any PHP errors

If payslips aren't being generated automatically:
1. Ensure the cron job is running (check the logs)
2. Verify that employees have at least 22 workdays in the current month
3. Check that salary records exist for the current month
4. Make sure the API endpoint is working by testing it directly 