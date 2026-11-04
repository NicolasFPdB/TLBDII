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
      renderProducts(allProducts);
    } catch (erro) {
      console.error("Ocorreu um erro ao carregar os dados:", erro);
    }
  }

  loadData();

  // Função para renderizar os produtos na grid da home com link para o forms.html
  function renderProducts(productsArray) {
    const grid = document.querySelector(".products-grid");
    if (!grid) return;

    if (productsArray.length === 0) {
      grid.innerHTML =
        "<p style='padding: 20px; grid-column: 1 / -1; text-align: center;'>Nenhuma peça encontrada.</p>";
      return;
    }

    grid.innerHTML = productsArray
      .map(
        (item) => `
      <a href="forms.html" style="text-decoration: none; color: inherit; display: block;">
        <article class="product-card">
          <div class="product-image"><img src="" alt="[Foto da Peça]"></div>
          <div class="product-info">
            <h3 class="product-title">${item["Descrição do Produto"]}</h3>
            <span class="product-code">Cód: ${item["Código"]}</span>
            <span class="product-stock">✓ Em Estoque</span>
            <div class="product-price">R$ 45,90</div>
            <button class="btn-buy">Comprar</button>
          </div>
        </article>
      </a>
    `,
      )
      .join("");
  }

  // Filtragem combinada por Veículo e por Caixas de Seleção (Checkboxes) de Partes
  const vehicleSelect = document.getElementById("vehicle-select");
  const checkboxes = document.querySelectorAll("input[name='part']");
  const searchBarInput = document.querySelector(".search-bar input");
  const searchBarButton = document.querySelector(".search-bar button");

  function filterProducts() {
    const vehicleValue = vehicleSelect ? vehicleSelect.value.toLowerCase() : "";
    const selectedParts = Array.from(checkboxes)
      .filter((cb) => cb.checked)
      .map((cb) => cb.value.toLowerCase());
    const searchQuery = searchBarInput
      ? searchBarInput.value.toLowerCase().trim()
      : "";

    const filtered = allProducts.filter((item) => {
      const desc = item["Descrição do Produto"].toLowerCase();
      const code = String(item["Código"]).toLowerCase();

      // Filtro de texto da barra de pesquisa
      const matchesSearch =
        searchQuery === "" ||
        desc.includes(searchQuery) ||
        code.includes(searchQuery);

      // Filtro de veículo selecionado no select
      const matchesVehicle = vehicleValue === "" || desc.includes(vehicleValue);

      // Filtro de checkboxes (partes do veículo)
      const matchesParts =
        selectedParts.length === 0 ||
        selectedParts.some((part) => desc.includes(part));

      return matchesSearch && matchesVehicle && matchesParts;
    });

    renderProducts(filtered);
  }

  // Event Listeners para os filtros
  if (vehicleSelect) {
    vehicleSelect.addEventListener("change", filterProducts);
  }

  checkboxes.forEach((cb) => {
    cb.addEventListener("change", filterProducts);
  });

  if (searchBarInput) {
    searchBarInput.addEventListener("input", filterProducts);
  }

  if (searchBarButton) {
    searchBarButton.addEventListener("click", (e) => {
      e.preventDefault();
      filterProducts();
    });
  }
});
