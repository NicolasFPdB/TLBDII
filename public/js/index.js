document.addEventListener('DOMContentLoaded', () => {
  let allProducts = [];
  let selectedCategory = 'all';
  let searchQuery = '';

  const normalize = (value) => String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
  const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));

  async function loadData() {
    try {
      const response = await fetch('assets/data/data.json');
      if (!response.ok) throw new Error(`Falha ao carregar catálogo (${response.status}).`);
      allProducts = await response.json();
      filterProducts();
    } catch (error) { console.error('Erro ao carregar catálogo:', error); }
  }

  function renderProducts(products) {
    const grid = document.querySelector('.product-grid');
    if (!grid) return;
    if (!products.length) {
      grid.textContent = '';
      const message = document.createElement('p');
      message.textContent = 'Nenhum produto encontrado.';
      message.style.cssText = 'padding:20px;grid-column:1/-1;text-align:center;';
      grid.appendChild(message);
      return;
    }
    grid.innerHTML = products.map((item) => {
      const description = escapeHtml(item['Descrição do Produto']);
      const codes = Array.isArray(item['Código']) ? item['Código'].join(', ') : item['Código'];
      const code = escapeHtml(codes);
      const imagePath = String(item.Imagem || '').replace(/^\/+/, '');
      const safeImage = /^assets\/images\/[a-zA-Z0-9._-]+$/.test(imagePath) ? imagePath : 'assets/images/logo.png';
      const category = escapeHtml(Array.isArray(item.Categoria) ? item.Categoria.join(', ') : item.Categoria);
      return `<article class="product-card">
        <a href="forms.html" class="product-card-link" style="text-decoration:none;color:inherit;display:block;">
          <div class="product-img"><img src="${safeImage}" alt="${description}" loading="lazy"></div>
          <div class="product-info"><div class="product-title">${description}</div>
          <div class="product-category">${category}</div><div class="product-price">Consulte o preço</div>
          <span class="product-code">Cód: ${code}</span><div class="shipping-tag">Consulte disponibilidade</div></div>
        </a></article>`;
    }).join('');
  }

  function filterProducts() {
    const filtered = allProducts.filter((item) => {
      const description = normalize(item['Descrição do Produto']);
      const code = normalize(Array.isArray(item['Código']) ? item['Código'].join(' ') : item['Código']);
      const categories = normalize(Array.isArray(item.Categoria) ? item.Categoria.join(' ') : item.Categoria);
      const categoryMatch = selectedCategory === 'all' || categories.includes(normalize(selectedCategory.replaceAll('-', ' ')));
      const searchMatch = !searchQuery || description.includes(searchQuery) || code.includes(searchQuery) || categories.includes(searchQuery);
      return categoryMatch && searchMatch;
    });
    renderProducts(filtered);
  }

  window.scrollCategories = (direction) => {
    document.getElementById('categoriesContainer')?.scrollBy({ left: direction * 500, behavior: 'smooth' });
  };

  const searchInput = document.querySelector('.search-box input');
  const searchButton = document.querySelector('.search-box button');
  searchInput?.addEventListener('input', () => { searchQuery = normalize(searchInput.value); filterProducts(); });
  searchButton?.addEventListener('click', (event) => { event.preventDefault(); searchQuery = normalize(searchInput?.value); filterProducts(); });

  document.querySelectorAll('.cat-card').forEach((button) => {
    button.addEventListener('click', () => {
      selectedCategory = button.dataset.category || 'all';
      document.querySelectorAll('.cat-card').forEach((item) => item.classList.toggle('active', item === button));
      filterProducts();
    });
  });
  loadData();
});
