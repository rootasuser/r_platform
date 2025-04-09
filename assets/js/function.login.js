document.addEventListener("DOMContentLoaded", function() {
  const createAccountBtn = document.getElementById("create-account-btn");
  const createAccountModal = document.getElementById("createAccountModal");
  const closeModalBtn = document.getElementById("closeModal");

  createAccountBtn.addEventListener("click", function(e) {
    e.preventDefault();
    createAccountModal.style.display = "block";
  });

  closeModalBtn.addEventListener("click", function() {
    createAccountModal.style.display = "none";
  });

  window.addEventListener("click", function(e) {
    if (e.target === createAccountModal) {
      createAccountModal.style.display = "none";
    }
  });
});