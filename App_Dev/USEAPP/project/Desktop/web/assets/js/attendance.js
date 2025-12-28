function markAttendance(action) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition((position) => {
            const location = `${position.coords.latitude},${position.coords.longitude}`;
            submitAttendance(action, location);
        }, () => {
            submitAttendance(action, 'Location not available');
        });
    } else {
        submitAttendance(action, 'Location not available');
    }
}

function submitAttendance(action, location) {
    fetch('../controller/attendanceProcessor.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=${action}&location=${location}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: data.message
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    });
}

function markAbsent() {
    Swal.fire({
        title: 'Mark Absent',
        html: `
            <input type="date" id="absentDate" class="swal2-input" value="${new Date().toISOString().split('T')[0]}">
            <textarea id="absentReason" class="swal2-textarea" placeholder="Reason for absence"></textarea>
        `,
        showCancelButton: true,
        confirmButtonText: 'Submit',
        preConfirm: () => {
            const date = document.getElementById('absentDate').value;
            const reason = document.getElementById('absentReason').value;
            return { date, reason };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            submitAttendance('absent', 'N/A', result.value.date, result.value.reason);
        }
    });
}

function submitAttendance(action, location, date = null, reason = null) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('location', location);
    if (date) formData.append('date', date);
    if (reason) formData.append('reason', reason);

    fetch('../controller/attendanceProcessor.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: data.message
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    });
}