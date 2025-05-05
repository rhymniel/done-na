document.addEventListener('DOMContentLoaded', function() {
    // Password change modal
    const modal = document.getElementById('passwordModal');
    const changePasswordBtns = document.querySelectorAll('.change-password');
    const closeBtn = document.querySelector('.close');
    
    changePasswordBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('studentId').value = this.getAttribute('data-id');
            modal.style.display = 'block';
        });
    });
    
    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});