document.getElementById("dashboard").addEventListener("click", function () {
    document.getElementById("content").innerHTML = `
        <div class="dashboard-section">
            <div class="dashboard-banner">
                <img src="/images/announcement.png" alt="Announcement Icon" class="dashboard-icon">
                <h2>Latest Announcements</h2>
            </div>
            <div class="dashboard-content">
                <ul class="announcement-list">
                    <li>📢 Class schedules updated for next semester.</li>
                    <li>🎓 Graduation ceremony on June 15, 2025.</li>
                    <li>📌 Enrollment for new students starts next week.</li>
                </ul>
                <img src="/images/announce.png" alt="Dashboard Announcement Image" class="dashboard-img">
            </div>
        </div>
    `;
});

document.getElementById("profile").addEventListener("click", function () {
    fetch("fetch_profile.php")
        .then(response => response.text())
        .then(data => {
            document.getElementById("content").innerHTML = data;
        });
});

document.getElementById("attendance").addEventListener("click", function () {
    document.getElementById("content").innerHTML = `
        <div class="attendance-section">
            <h2>Attendance</h2>
            <p>No records available at the moment.</p>
        </div>
    `;
});

function logout() {
    window.location.href = "/home/home.html";
}
