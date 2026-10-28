async function loadData() {
  try {
    const response = await fetch("../public/assets/data/data.json");

    if (!response.ok) {
      throw new Error(`Erro ao carregar o ficheiro: ${response.status}`);
    }

    const dataJson = await response.json();

    console.log(dataJson);
  } catch (erro) {
    console.error("Ocorreu um erro:", erro);
  }
}

loadData();

class carrinho {
  constructor(index, optionsData = {}) {
    this.index = index;
    this.optionsData = optionsData;
    this.element = this.createElement();
  }

  createElement() {
    const cart = document.createElement("div");
    cart.className = "cart-wrapper";
    cart.innerHTML = `
            <main class="cart-section">
                <h2 class="section-title">Meu Carrinho</h2>

                <div class="cart-head">
                    <span>Produto</span>
                    <span>Preço</span>
                    <span>Quantidade</span>
                    <span>Subtotal</span>
                </div>

                <article class="cart-item">
                    <div class="cart-item-product">
                        <div class="product-image"><img src="" alt="[Foto da Peça]"></div>
                        <div class="product-info">
                            <h3 class="product-title">Filtro de Óleo Do Motor</h3>
                            <span class="product-code">Cód: FO-2390</span>
                            <span class="product-compatibility">Aplica-se: Ducato 2.3</span>
                            <span class="product-stock">✓ Em Estoque</span>
                        </div>
                    </div>
                    <div class="cart-price">R$ 45,90</div>
                    <div class="qty-control">
                        <button type="button" aria-label="Diminuir">−</button>
                        <input type="number" value="1" min="1">
                        <button type="button" aria-label="Aumentar">+</button>
                    </div>
                    <div class="cart-subtotal">R$ 45,90</div>
                    <button type="button" class="btn-remove" aria-label="Remover item">✕</button>
                </article>

                <article class="cart-item">
                    <div class="cart-item-product">
                        <div class="product-image"><img src="" alt="[Foto da Peça]"></div>
                        <div class="product-info">
                            <h3 class="product-title">Pastilha de Freio Dianteira</h3>
                            <span class="product-code">Cód: PF-1180</span>
                            <span class="product-compatibility">Aplica-se: Master 2.5</span>
                            <span class="product-stock">✓ Em Estoque</span>
                        </div>
                    </div>
                    <div class="cart-price">R$ 129,90</div>
                    <div class="qty-control">
                        <button type="button" aria-label="Diminuir">−</button>
                        <input type="number" value="2" min="1">
                        <button type="button" aria-label="Aumentar">+</button>
                    </div>
                    <div class="cart-subtotal">R$ 259,80</div>
                    <button type="button" class="btn-remove" aria-label="Remover item">✕</button>
                </article>

                <!-- Estado de carrinho vazio  -->
                <div class="cart-empty" hidden>
                    <p>Seu carrinho está vazio.</p>
                    <a href="home.html" class="btn-buy">Ver peças disponíveis</a>
                </div>

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
                    <li><span>Subtotal (3 itens)</span><span>R$ 305,70</span></li>
                    <li><span>Frete</span><span>A calcular</span></li>
                    <li><span>Desconto</span><span>− R$ 0,00</span></li>
                    <li class="summary-total"><span>Total</span><span>R$ 305,70</span></li>
                </ul>

                <button type="button" class="btn-buy btn-checkout">Finalizar Compra</button>

                <p class="summary-payments">Aceitamos: Cartão de Crédito, Cartão de Débito e Pix</p>
            </aside>
        `;
    return cart;
  }
}

document.addEventListener("DOMContentLoaded", () => {
  const container = document.querySelector(".cart-container");
  if (container) {
    const meuCarrinho = new carrinho(1);
    container.appendChild(meuCarrinho.element);
  }
});
