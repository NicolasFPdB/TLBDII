// Rolagem
function scrollCategories(direction) {
  const container = document.getElementById("categoriesContainer");

  container.scrollBy({
    left: direction * 500,
    behavior: "smooth"
  });
}

// Seleção das categorias
const categoryButtons = document.querySelectorAll(".cat-card");
const products = document.querySelectorAll(".product-card");

categoryButtons.forEach(button => {
  button.addEventListener("click", () => {
    const selectedCategory = button.dataset.category;
    if (button.classList.contains("active")) {
      button.classList.remove("active");
      products.forEach(product => {
        product.style.display = "";
      });
      return;
    }
    else {
      categoryButtons.forEach(otherButton => {
        otherButton.classList.remove("active");
      });
      button.classList.add("active");
      products.forEach(product => {
        if (
          selectedCategory === "todos" ||
          product.dataset.category === selectedCategory
        ) {
          product.style.display = "";
        } else {
          product.style.display = "none";
        }
      });
    }
  });
});