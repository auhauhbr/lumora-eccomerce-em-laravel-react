import type { Carrinho, ItemCarrinho } from '../tipos/catalogo';
import { Minus, PackageSearch, Plus, Trash2, X } from './Icones';

const moeda = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

type Propriedades = {
    aberto: boolean;
    carrinho: Carrinho | null;
    subtotal: string;
    carregando: boolean;
    autenticado: boolean;
    aoFechar: () => void;
    aoEntrar: () => void;
    aoFinalizar: () => void;
    aoAtualizar: (item: ItemCarrinho, quantidade: number) => void;
    aoRemover: (item: ItemCarrinho) => void;
};

export function PainelCarrinho({
    aberto,
    carrinho,
    subtotal,
    carregando,
    autenticado,
    aoFechar,
    aoEntrar,
    aoFinalizar,
    aoAtualizar,
    aoRemover,
}: Propriedades) {
    return (
        <>
            <button
                type="button"
                aria-label="Fechar carrinho"
                className={`fundo-painel ${aberto ? 'fundo-painel--aberto' : ''}`}
                onClick={aoFechar}
            />
            <aside className={`painel-carrinho ${aberto ? 'painel-carrinho--aberto' : ''}`}>
                <header>
                    <div>
                        <span>Sua seleção</span>
                        <h2>Carrinho</h2>
                    </div>
                    <button type="button" onClick={aoFechar} aria-label="Fechar carrinho">
                        <X size={20} />
                    </button>
                </header>

                {!autenticado ? (
                    <div className="carrinho-vazio">
                        <PackageSearch size={38} />
                        <h3>Entre para montar seu carrinho</h3>
                        <p>Seus itens ficam vinculados à sua conta e ao estoque real.</p>
                        <button type="button" onClick={aoEntrar}>
                            Entrar na Lumora
                        </button>
                    </div>
                ) : carregando ? (
                    <div className="carrinho-vazio">Carregando carrinho...</div>
                ) : !carrinho?.itens.length ? (
                    <div className="carrinho-vazio">
                        <PackageSearch size={38} />
                        <h3>Seu carrinho está vazio</h3>
                        <p>Adicione produtos do catálogo para continuar.</p>
                    </div>
                ) : (
                    <>
                        <div className="lista-carrinho">
                            {carrinho.itens.map((item) => (
                                <article key={item.id} className="item-carrinho">
                                    <img
                                        src={
                                            item.produto.image_url ??
                                            `https://picsum.photos/seed/${item.produto.slug}/200/200`
                                        }
                                        alt={item.produto.name}
                                    />
                                    <div>
                                        <span>{item.produto.categoria.name}</span>
                                        <h3>{item.produto.name}</h3>
                                        <strong>
                                            {moeda.format(Number(item.unit_price) * item.quantity)}
                                        </strong>
                                        <div className="controle-quantidade">
                                            <button
                                                type="button"
                                                disabled={item.quantity <= 1}
                                                onClick={() =>
                                                    aoAtualizar(item, item.quantity - 1)
                                                }
                                            >
                                                <Minus size={14} />
                                            </button>
                                            <span>{item.quantity}</span>
                                            <button
                                                type="button"
                                                disabled={item.quantity >= item.produto.stock}
                                                onClick={() =>
                                                    aoAtualizar(item, item.quantity + 1)
                                                }
                                            >
                                                <Plus size={14} />
                                            </button>
                                            <button
                                                className="remover-item"
                                                type="button"
                                                onClick={() => aoRemover(item)}
                                            >
                                                <Trash2 size={15} />
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            ))}
                        </div>
                        <footer className="resumo-carrinho">
                            <div>
                                <span>Subtotal</span>
                                <strong>{moeda.format(Number(subtotal))}</strong>
                            </div>
                            <small>Frete calculado na etapa de endereço.</small>
                            <button type="button" onClick={aoFinalizar}>
                                Finalizar compra
                            </button>
                        </footer>
                    </>
                )}
            </aside>
        </>
    );
}
