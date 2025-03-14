/**
 * Main JavaScript for Thesis Management System
 */

// Enable Bootstrap tooltips everywhere
document.addEventListener('DOMContentLoaded', function () {
  // Initialize tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
  });

  // Initialize popovers
  var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
  var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl)
  });

  // Role-specific form fields in registration
  const roleSelect = document.getElementById('role');
  if (roleSelect) {
    roleSelect.addEventListener('change', function() {
      const studentFields = document.getElementById('student-fields');
      const facultyFields = document.getElementById('faculty-fields');
      
      // Hide all role-specific fields
      if (studentFields) studentFields.style.display = 'none';
      if (facultyFields) facultyFields.style.display = 'none';
      
      // Show fields for selected role
      if (this.value === 'student' && studentFields) {
        studentFields.style.display = 'block';
      } else if (this.value === 'faculty' && facultyFields) {
        facultyFields.style.display = 'block';
      }
    });
  }

  // Form validation
  const forms = document.querySelectorAll('.needs-validation');
  Array.prototype.slice.call(forms).forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });

  // Confirm delete actions
  const deleteButtons = document.querySelectorAll('.btn-delete');
  deleteButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      if (!confirm('Bạn có chắc chắn muốn xóa?')) {
        e.preventDefault();
      }
    });
  });

  // Auto-hide alerts after 5 seconds
  const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
  alerts.forEach(alert => {
    setTimeout(() => {
      const bsAlert = new bootstrap.Alert(alert);
      bsAlert.close();
    }, 5000);
  });
});