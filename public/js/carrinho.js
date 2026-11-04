// carrinho.js

class Carrinho {
  constructor(todosProdutos = []) {
    this.todosProdutos = todosProdutos;
    // O catálogo JSON não possui IDs nem preços: usa o primeiro código como chave local.
    this.itens = this.carregarCarrinhoSalvo();
    this.element = null;
  }

  carregarCarrinhoSalvo() {
    let cartStorage = [];
    try { cartStorage = JSON.parse(localStorage.getItem("cart") || "[]"); } catch { cartStorage = []; }
    if (!Array.isArray(cartStorage)) return [];
    return cartStorage.map((stored) => {
      const product = this.todosProdutos.find((p) => {
        const codes = Array.isArray(p["Código"]) ? p["Código"] : [p["Código"]];
        return codes.map(String).includes(String(stored.id));
      });
      if (!product) return null;
      return { ...product, id: String(stored.id), quantidade: Math.max(1, Math.min(99, parseInt(stored.quantity, 10) || 1)), preco: null };
    }).filter(Boolean);
  }

  salvarStorage() {
    const cartData = this.itens.map((item) => ({
      id: item.id,
      quantity: item.quantidade,
    }));
    localStorage.setItem("cart", JSON.stringify(cartData));
  }

  alterarQuantidade(id, delta) {
    const item = this.itens.find((i) => i.id === id);
    if (item) {
      item.quantidade += delta;
      if (item.quantidade < 1) item.quantidade = 1;
      this.salvarStorage();
      this.render();
    }
  }

  definirQuantidade(id, valor) {
    const item = this.itens.find((i) => i.id === id);
    if (item) {
      const novaQtd = parseInt(valor, 10);
      item.quantidade = isNaN(novaQtd) || novaQtd < 1 ? 1 : novaQtd;
      this.salvarStorage();
      this.render();
    }
  }

  removerItem(id) {
    this.itens = this.itens.filter((i) => i.id !== id);
    this.salvarStorage();
    this.render();
  }

  esvaziarCarrinho() {
    this.itens = [];
    this.salvarStorage();
    this.render();
  }

  calcularSubtotal() {
    // Sem preço cadastrado/validado no servidor, não inventa valores no navegador.
    return null;
  }

  createElement() {
    const wrapper = document.createElement("div");
    wrapper.className = "cart-wrapper";
    return wrapper;
  }

  render() {
    if (!this.element) {
      this.element = this.createElement();
    }

    const totalItens = this.itens.reduce(
      (acc, item) => acc + item.quantidade,
      0,
    );

    const produtosHTML =
      this.itens.length > 0
        ? this.itens
            .map((item) => {
              const codigos = Array.isArray(item["Código"])
                ? item["Código"].join(", ")
                : item["Código"];

              const escapeHtml = (value) => String(value ?? "").replace(/[&<>"']/g, (ch) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch]));
              const description = escapeHtml(item["Descrição do Produto"]);
              const codeText = escapeHtml(codigos);
              const image = String(item.Imagem || "").replace(/^\/+/, "");
              const safeImage = /^assets\/images\/[a-zA-Z0-9._-]+$/.test(image) ? image : "assets/images/logo.png";
              return `
            <article class="cart-item" data-id="${escapeHtml(item.id)}">
                <div class="cart-item-product">
                    <div class="product-image">
                      <img src="${safeImage}" alt="${description}">
                    </div>
                    <div class="product-info">
                        <h3 class="product-title">${description}</h3>
                        <span class="product-code">Cód: ${codeText}</span>
                        <span class="product-compatibility">Aplica-se: Linha Automotiva</span>
                        <span class="product-stock">Consulte disponibilidade</span>
                    </div>
                </div>
                <div class="cart-price">Consulte preço</div>
                <div class="qty-control">
                    <button type="button" class="btn-decrease" aria-label="Diminuir">−</button>
                    <input type="number" class="input-qty" value="${item.quantidade}" min="1">
                    <button type="button" class="btn-increase" aria-label="Aumentar">+</button>
                </div>
                <div class="cart-subtotal">—</div>
                <button type="button" class="btn-remove" aria-label="Remover item">✕</button>
            </article>
        `;
            })
            .join("")
        : `
            <div class="cart-empty">
                <p>Seu carrinho está vazio.</p>
                <a href="home.html" class="btn-buy">Ver peças disponíveis</a>
            </div>
        `;

    this.element.innerHTML = `
            <main class="cart-section">
                <h2 class="section-title">Meu Carrinho</h2>

                <div class="cart-head">
                    <span>Produto</span>
                    <span>Preço</span>
                    <span>Quantidade</span>
                    <span>Subtotal</span>
                </div>

                ${produtosHTML}

                <div class="cart-actions">
                    <a href="home.html" class="link-back">← Continuar comprando</a>
                    <button type="button" class="btn-clear">Esvaziar carrinho</button>
                </div>
            </main>
            <aside class="cart-summary">
                <h3>Resumo do Pedido</h3>

                <div class="summary-group">
                    <label for="cep">Calcular frete</label>
                    <div class="input-row">
                        <input type="text" id="cep" placeholder="00000-000" maxlength="9">
                        <button type="button">OK</button>
                    </div>
                    <select aria-label="Opção de entrega">
                        <option value="">Selecione a entrega</option>
                        <option value="correios">Correios</option>
                        <option value="transportadora">Transportadoras</option>
                        <option value="direta">Entrega Direta</option>
                        <option value="motoboy">Motoboy (Uber Envios)</option>
                    </select>
                </div>

                <ul class="summary-lines">
                    <li><span>Itens (${totalItens})</span><span>Preço sob consulta</span></li>
                    <li><span>Frete</span><span>A calcular</span></li>
                    <li class="summary-total"><span>Total</span><span>Indisponível até validar preços</span></li>
                </ul>

                <button type="button" class="btn-buy btn-checkout" disabled title="O checkout será habilitado após integração de preços, estoque e pedidos no servidor">Finalizar Compra</button>
                <p class="summary-payments">O checkout ainda não está disponível. Nenhuma compra será enviada.</p>
            </aside>
        `;

    this.vincularEventos();
  }

  vincularEventos() {
    // Aumentar quantidade
    this.element.querySelectorAll(".btn-increase").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const id = e.currentTarget.closest(".cart-item").dataset.id;
        this.alterarQuantidade(id, 1);
      });
    });

    // Diminuir quantidade
    this.element.querySelectorAll(".btn-decrease").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const id = e.currentTarget.closest(".cart-item").dataset.id;
        this.alterarQuantidade(id, -1);
      });
    });

    // Alterar quantidade manualmente pelo input
    this.element.querySelectorAll(".input-qty").forEach((input) => {
      input.addEventListener("change", (e) => {
        const id = e.currentTarget.closest(".cart-item").dataset.id;
        this.definirQuantidade(id, e.target.value);
      });
    });

    // Remover item individual
    this.element.querySelectorAll(".btn-remove").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const id = e.currentTarget.closest(".cart-item").dataset.id;
        this.removerItem(id);
      });
    });

    // Esvaziar carrinho
    const btnClear = this.element.querySelector(".btn-clear");
    if (btnClear) {
      btnClear.addEventListener("click", () => this.esvaziarCarrinho());
    }
  }
}

async function loadCartData() {
  try {
    const response = await fetch("assets/data/data.json");

    if (!response.ok) {
      throw new Error(`Erro ao carregar o ficheiro: ${response.status}`);
    }

    const dataJson = await response.json();

    const container = document.querySelector(".cart-container");
    if (container) {
      const meuCarrinho = new Carrinho(dataJson);
      meuCarrinho.render();
      container.appendChild(meuCarrinho.element);
    }
  } catch (erro) {
    console.error("Ocorreu um erro ao carregar os dados:", erro);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  loadCartData();
});
