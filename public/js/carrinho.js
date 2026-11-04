// carrinho.js

class Carrinho {
  constructor(todosProdutos = []) {
    this.todosProdutos = todosProdutos;
    // Carrega os itens salvos no localStorage ou usa os 2 primeiros do JSON como exemplo
    this.itens = this.carregarCarrinhoSalvo();
    this.element = null;
  }

  carregarCarrinhoSalvo() {
    const cartStorage = JSON.parse(localStorage.getItem("cart") || "[]");

    if (cartStorage.length > 0) {
      return cartStorage.map((itemStorage) => {
        const produtoOriginal =
          this.todosProdutos.find((p) => p.id === itemStorage.id) || {};
        return {
          ...produtoOriginal,
          quantidade: itemStorage.quantity || 1,
          preco: itemStorage.preco || 45.9, // Preço padrão caso não exista no JSON
        };
      });
    }

    // Caso o localStorage esteja vazio, carrega 2 itens de demonstração
    return this.todosProdutos.slice(0, 2).map((produto) => ({
      ...produto,
      quantidade: 1,
      preco: 45.9,
    }));
  }

  salvarStorage() {
    const cartData = this.itens.map((item) => ({
      id: item.id,
      quantity: item.quantidade,
      preco: item.preco,
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
    return this.itens.reduce(
      (total, item) => total + item.preco * item.quantidade,
      0,
    );
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

    const subtotalGeral = this.calcularSubtotal();
    const totalItens = this.itens.reduce(
      (acc, item) => acc + item.quantidade,
      0,
    );

    const produtosHTML =
      this.itens.length > 0
        ? this.itens
            .map((item) => {
              const subtotalItem = (item.preco * item.quantidade).toFixed(2);
              const codigos = Array.isArray(item["Código"])
                ? item["Código"].join(", ")
                : item["Código"];

              return `
            <article class="cart-item" data-id="${item.id}">
                <div class="cart-item-product">
                    <div class="product-image">
                      <img src="../public/${item.Imagem || "assets/images/placeholder.png"}" alt="${item["Descrição do Produto"]}">
                    </div>
                    <div class="product-info">
                        <h3 class="product-title">${item["Descrição do Produto"]}</h3>
                        <span class="product-code">Cód: ${codigos}</span>
                        <span class="product-compatibility">Aplica-se: Linha Automotiva</span>
                        <span class="product-stock">✓ Em Estoque</span>
                    </div>
                </div>
                <div class="cart-price">R$ ${item.preco.toFixed(2).replace(".", ",")}</div>
                <div class="qty-control">
                    <button type="button" class="btn-decrease" aria-label="Diminuir">−</button>
                    <input type="number" class="input-qty" value="${item.quantidade}" min="1">
                    <button type="button" class="btn-increase" aria-label="Aumentar">+</button>
                </div>
                <div class="cart-subtotal">R$ ${subtotalItem.replace(".", ",")}</div>
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
                    <li><span>Subtotal (${totalItens} ${totalItens === 1 ? "item" : "itens"})</span><span>R$ ${subtotalGeral.toFixed(2).replace(".", ",")}</span></li>
                    <li><span>Frete</span><span>A calcular</span></li>
                    <li><span>Desconto</span><span>− R$ 0,00</span></li>
                    <li class="summary-total"><span>Total</span><span>R$ ${subtotalGeral.toFixed(2).replace(".", ",")}</span></li>
                </ul>

                <button type="button" class="btn-buy btn-checkout" ${this.itens.length === 0 ? "disabled" : ""}>Finalizar Compra</button>

                <p class="summary-payments">Aceitamos: Cartão de Crédito, Cartão de Débito e Pix</p>
            </aside>
        `;

    this.vincularEventos();
  }

  vincularEventos() {
    // Aumentar quantidade
    this.element.querySelectorAll(".btn-increase").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const id = e.target.closest(".cart-item").dataset.id;
        this.alterarQuantidade(id, 1);
      });
    });

    // Diminuir quantidade
    this.element.querySelectorAll(".btn-decrease").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const id = e.target.closest(".cart-item").dataset.id;
        this.alterarQuantidade(id, -1);
      });
    });

    // Alterar quantidade manualmente pelo input
    this.element.querySelectorAll(".input-qty").forEach((input) => {
      input.addEventListener("change", (e) => {
        const id = e.target.closest(".cart-item").dataset.id;
        this.definirQuantidade(id, e.target.value);
      });
    });

    // Remover item individual
    this.element.querySelectorAll(".btn-remove").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const id = e.target.closest(".cart-item").dataset.id;
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
    const response = await fetch("../public/assets/data/data.json");

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
