const modal = document.getElementById("createAccountModal");
const openModalBtn = document.getElementById("create-account-btn");
const closeModalBtn = document.getElementById("closeModal");

openModalBtn.onclick = () => {
  modal.style.display = "flex";
};

closeModalBtn.onclick = () => {
  modal.style.display = "none";
};

window.onclick = (event) => {
  if (event.target == modal) {
    modal.style.display = "none";
  }
};