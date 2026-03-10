// Modal helpers
function openModal(id) {
  document.getElementById(id).classList.add('show');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('show');
}

// Close modal on overlay click
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('show');
  }
});

// Confirm delete
function confirmDelete(msg) {
  return confirm(msg || 'Yakin ingin menghapus data ini?');
}

// Tab switching (auth page)
function switchTab(tab) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.add('hidden'));
  document.querySelector('[data-tab="' + tab + '"]').classList.add('active');
  document.getElementById('pane-' + tab).classList.remove('hidden');
}

// Image preview
function previewImage(input, previewId) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = function(e) {
    const el = document.getElementById(previewId);
    if (el) { el.src = e.target.result; el.style.display = 'block'; }
  };
  reader.readAsDataURL(file);
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(a => {
    setTimeout(() => {
      a.style.transition = 'opacity .4s';
      a.style.opacity = '0';
      setTimeout(() => a.remove(), 400);
    }, 4000);
  });
});

// Sidebar toggle (mobile)
function toggleSidebar() {
  document.querySelector('.sidebar').classList.toggle('open');
}

// Search filter for tables
function tableSearch(inputId, tableId) {
  const input = document.getElementById(inputId);
  if (!input) return;
  input.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    const rows = document.querySelectorAll('#' + tableId + ' tbody tr');
    rows.forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

document.addEventListener('DOMContentLoaded', function() {
  tableSearch('searchInput', 'dataTable');
});
