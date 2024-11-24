function togglePassword() {
  var passwordField = document.getElementById("password");
  var showPasswordText = document.querySelector(".show-password");

  if (passwordField.type === "password") {
      passwordField.type = "text";
      showPasswordText.textContent = "Hide"; // Ubah teks tombol menjadi "Hide"
  } else {
      passwordField.type = "password";
      showPasswordText.textContent = "Show"; // Ubah teks tombol kembali menjadi "Show"
  }
}


