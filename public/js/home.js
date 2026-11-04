document.addEventListener('DOMContentLoaded', () => {
  let allProducts = [];
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
    const grid = document.querySelector('.products-grid');
    if (!grid) return;
    if (!products.length) {
      grid.textContent = 'Nenhuma peça encontrada.';
      return;
    }
    grid.innerHTML = products.map((item) => {
      const description = escapeHtml(item['Descrição do Produto']);
      const rawCodes = Array.isArray(item['Código']) ? item['Código'] : [item['Código']];
      const codes = rawCodes.map((code) => String(code ?? '').trim()).filter(Boolean);
      const key = codes[0] || '';
      const codeLabel = escapeHtml(codes.join(', '));
      const rawImage = String(item.Imagem || '').replace(/^\/+/, '');
      const image = /^assets\/images\/[a-zA-Z0-9._-]+$/.test(rawImage) ? rawImage : 'assets/images/logo.png';
      return `<article class="product-card">
        <div class="product-image"><img src="${image}" alt="${description}" loading="lazy"></div>
        <div class="product-info">
          <h3 class="product-title">${description}</h3>
          <span class="product-code">Cód: ${codeLabel}</span>
          <span class="product-stock">Consulte preço e disponibilidade</span>
          <button class="btn-buy btn-add-cart" type="button" data-code="${escapeHtml(key)}" ${key ? '' : 'disabled'}>Adicionar ao carrinho</button>
        </div>
      </article>`;
    }).join('');
  }

  function filterProducts() {
    const vehicle = normalize(document.getElementById('vehicle-select')?.value || '');
    const selectedParts = Array.from(document.querySelectorAll("input[name='part']:checked")).map((cb) => normalize(cb.value));
    const query = normalize(document.querySelector('.search-bar input')?.value || '');
    renderProducts(allProducts.filter((item) => {
      const desc = normalize(item['Descrição do Produto']);
      const codes = normalize(Array.isArray(item['Código']) ? item['Código'].join(' ') : item['Código']);
      const matchesSearch = !query || desc.includes(query) || codes.includes(query);
      const matchesVehicle = !vehicle || desc.includes(vehicle);
      const matchesParts = !selectedParts.length || selectedParts.some((part) => desc.includes(part));
      return matchesSearch && matchesVehicle && matchesParts;
    }));
  }

  document.querySelectorAll("input[name='part'], #vehicle-select, .search-bar input").forEach((control) => { control.addEventListener('input', filterProducts); control.addEventListener('change', filterProducts); });
  document.querySelector('.search-bar button')?.addEventListener('click', (event) => { event.preventDefault(); filterProducts(); });

  document.querySelector('.products-grid')?.addEventListener('click', (event) => {
    const button = event.target.closest('.btn-add-cart');
    if (!button) return;
    const code = button.dataset.code;
    if (!code) return;
    let cart = [];
    try { cart = JSON.parse(localStorage.getItem('cart') || '[]'); } catch { cart = []; }
    if (!Array.isArray(cart)) cart = [];
    const existing = cart.find((item) => String(item.id) === code);
    if (existing) existing.quantity = Math.min(99, (parseInt(existing.quantity, 10) || 1) + 1);
    else cart.push({ id: code, quantity: 1 });
    localStorage.setItem('cart', JSON.stringify(cart));
    button.textContent = 'Adicionado';
    button.disabled = true;
    window.setTimeout(() => { button.textContent = 'Adicionar ao carrinho'; button.disabled = false; }, 900);
  });

  loadData();
});
