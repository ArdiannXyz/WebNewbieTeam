
function toggleDropdown() {
    var dropdown = document.getElementById("dropdown");
    var dropdownBtn = document.getElementById("dropdown-btn");
    dropdown.classList.toggle("active");
    dropdownBtn.classList.toggle("active");
  }

  function validateForm() {
    var role = document.getElementById("role").value;
    if (role === "pilih peran") {
        alert("Silakan pilih peran sebelum mendaftar.");
        return false;
    }
    return true; // Lanjutkan pengiriman form jika valid
}

document.addEventListener('DOMContentLoaded', () => {
  const menuIcon = document.querySelector('.header .menu-icon');
  const sidebar = document.querySelector('.sidebar');
  const content = document.querySelector('.content');
  const container = document.querySelector('.container');
  const footer = document.querySelector('.footer');
  const registerBox = document.querySelector('.register-box');  // Perbaikan selector
  console.log(menuIcon, sidebar, content, container, footer, registerBox);
  
  menuIcon.addEventListener('click', () => {
    // Toggle kelas untuk sidebar dan konten
    sidebar.classList.toggle('sidebar-expanded');
    content.classList.toggle('sidebar-expanded-content'); 
    container.classList.toggle('sidebar-expanded-container');
    footer.classList.toggle('footer-active'); 
    registerBox.classList.toggle('register-box');  // Perbaikan selector
  });
});

//option value daftar
// Fungsi untuk memperbarui nilai role_user di input hidden berdasarkan pilihan dropdown
function updateRoleUser() {
  const roleSelect = document.getElementById("role");
  const selectedRole = roleSelect.value;
  const roleUserInput = document.getElementById("roleUser");

  // Update value input hidden dengan nilai role yang dipilih
  roleUserInput.value = selectedRole;
}

// Fungsi untuk menangani redirect berdasarkan role yang dipilih
function redirectToRolePage(event) {
  event.preventDefault(); // Mencegah pengiriman formulir langsung

  const selectedRole = document.getElementById("role").value;
  const roleUserInput = document.getElementById("roleUser");

  // Pastikan role dipilih, jika tidak beri peringatan
  if (!selectedRole) {
      alert("Silakan pilih role terlebih dahulu!");
      return;
  }

  // Update nilai roleUserInput untuk memastikan data terkirim
  roleUserInput.value = selectedRole;

  // Redirect berdasarkan role yang dipilih
  if (selectedRole === "admin") {
      window.location.href = "daftaradmin.php?role=" + selectedRole;
  } else if (selectedRole === "guru") {
      window.location.href = "daftarguru.php?role=" + selectedRole; // Halaman daftar untuk guru
  } else if (selectedRole === "siswa") {
      window.location.href = "daftarsiswa.php?role=" + selectedRole; // Halaman daftar untuk siswa
  }
}
  