document.addEventListener("DOMContentLoaded", () => {
  let allProducts = [];

  // Função para carregar os dados do JSON
  async function loadData() {
    try {
      const response = await fetch("../public/assets/data/data.json");

      if (!response.ok) {
        throw new Error(`Erro ao carregar o ficheiro: ${response.status}`);
      }

      allProducts = await response.json();
      renderProducts(allProducts); // Renderiza todos os produtos inicialmente
    } catch (erro) {
      console.error("Ocorreu um erro:", erro);
    }
  }

  loadData();

  // Função para renderizar os produtos dinamicamente com link âncora para forms.html
  function renderProducts(productsArray) {
    const grid = document.querySelector(".product-grid");
    if (!grid) return;

    if (productsArray.length === 0) {
      grid.innerHTML =
        "<p style='padding: 20px; grid-column: 1 / -1; text-align: center;'>Nenhum produto encontrado.</p>";
      return;
    }

    grid.innerHTML = productsArray
      .map(
        (item) => `
      <a href="forms.html" class="product-card-link" style="text-decoration: none; color: inherit; display: block;">
        <div class="product-card">
          <div class="product-img">[Imagem do Produto]</div>
          <div class="product-info">
            <div class="product-title">${item["Descrição do Produto"]}</div>
            <div class="product-price">R$ 45,90</div>
            <span class="product-code" style="font-size: 0.85rem; color: #666;">Cód: ${item["Código"]}</span>
            <div class="shipping-tag" style="margin-top: 5px;">✓ Em Estoque</div>
            <button class="btn-buy" style="margin-top: 10px;">Comprar</button>
          </div>
        </div>
      </a>
    `,
      )
      .join("");
  }

  // Rolagem de categorias
  window.scrollCategories = function (direction) {
    const container = document.getElementById("categoriesContainer");
    if (container) {
      container.scrollBy({
        left: direction * 500,
        behavior: "smooth",
      });
    }
  };

  // Funcionalidade de Pesquisa (Input e Botão Buscar)
  const searchInput = document.querySelector(".search-box input");
  const searchButton = document.querySelector(".search-box button");

  function performSearch() {
    const query = searchInput.value.toLowerCase().trim();
    const filtered = allProducts.filter(
      (item) =>
        item["Descrição do Produto"].toLowerCase().includes(query) ||
        String(item["Código"]).toLowerCase().includes(query),
    );
    renderProducts(filtered);
  }

  if (searchInput) {
    searchInput.addEventListener("input", performSearch);
  }
  if (searchButton) {
    searchButton.addEventListener("click", (e) => {
      e.preventDefault();
      performSearch();
    });
  }

  // Seleção e filtragem por categorias
  const categoryButtons = document.querySelectorAll(".cat-card");
  categoryButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const selectedCategory = button.dataset.category;

      categoryButtons.forEach((btn) => btn.classList.remove("active"));
      button.classList.add("active");

      if (selectedCategory === "all") {
        renderProducts(allProducts);
      } else {
        // Filtra os produtos cuja descrição contenha a categoria selecionada
        const filtered = allProducts.filter((item) =>
          item["Descrição do Produto"]
            .toLowerCase()
            .includes(selectedCategory.replace("-", " ")),
        );
        renderProducts(filtered);
      }
    });
  });
});
