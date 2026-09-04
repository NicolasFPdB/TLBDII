// rolagem das categorias
function scrollCategories(direction) {
  const container = document.getElementById("categoriesContainer");

  container.scrollBy({
    left: direction * 500,
    behavior: "smooth"
  });
}

// seleção das categorias
const categoryButtons = document.querySelectorAll(".cat-card");
const products = document.querySelectorAll(".product-card");

categoryButtons.forEach(button => {

  button.addEventListener("click", () => {

    const selectedCategory = button.dataset.category;

    // se clicar novamente na categoria selecionada, desativa o filtro e mostra todos os produtos
    if (button.classList.contains("active")) {

      button.classList.remove("active");

      products.forEach(product => {
        product.style.display = "";
      });

      return;
    }

    // remove a seleção das outras categorias
    categoryButtons.forEach(otherButton => {
      otherButton.classList.remove("active");
    });

    // ativa a categoria escolhida
    button.classList.add("active");

    // mostra apenas os produtos da categoria escolhida
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

  });

});