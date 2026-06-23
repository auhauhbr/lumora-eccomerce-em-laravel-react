import { useMemo, useState, type MouseEvent } from 'react';
import type { Produto } from '../tipos/catalogo';
import {
    ArrowLeft,
    Check,
    ShieldCheck,
    ShoppingBag,
    Star,
    Truck,
} from './Icones';

const moeda = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const imagemReserva = '/imagens/produtos/lumora-tecnologia.svg';

type Propriedades = {
    produto: Produto;
    relacionados: Produto[];
    aoVoltar: () => void;
    aoSelecionar: (produto: Produto) => void;
    aoAdicionar: (produto: Produto, quantidade?: number) => void;
};

function imagem(produto: Produto, variacao = '') {
    return (
        produto.image_url ??
        (variacao ? `/imagens/produtos/lumora-tecnologia.svg` : imagemReserva)
    );
}

export function PaginaProduto({
    produto,
    relacionados,
    aoVoltar,
    aoSelecionar,
    aoAdicionar,
}: Propriedades) {
    const [miniatura, setMiniatura] = useState(0);
    const [quantidade, setQuantidade] = useState(1);
    const [zoom, setZoom] = useState({ ativo: false, x: 50, y: 50 });
    const imagens = useMemo(
        () =>
            produto.image_urls?.length
                ? produto.image_urls
                : ['', '-detalhe', '-lateral', '-ambiente'].map((item) =>
                      imagem(produto, item),
                  ),
        [produto],
    );
    const valor = Number(produto.price);
    const especificacoes = [
        ['Categoria', produto.categoria.name],
        ['Marca', produto.brand ?? 'Lumora'],
        ['Condição', produto.condition === 'novo' ? 'Novo' : produto.condition],
        ['Disponibilidade', produto.stock > 0 ? 'Pronta entrega' : 'Indisponível'],
        ['Estoque atual', `${produto.stock} unidades`],
        ['Garantia', '12 meses'],
        ['Vendido por', 'Lumora Oficial'],
    ];

    function moverZoom(evento: MouseEvent<HTMLDivElement>) {
        const area = evento.currentTarget.getBoundingClientRect();
        const x = ((evento.clientX - area.left) / area.width) * 100;
        const y = ((evento.clientY - area.top) / area.height) * 100;
        setZoom({
            ativo: true,
            x: Math.max(0, Math.min(100, x)),
            y: Math.max(0, Math.min(100, y)),
        });
    }

    return (
        <main className="pagina-produto">
            <button className="voltar-produtos" type="button" onClick={aoVoltar}>
                <ArrowLeft size={17} />
                Voltar aos produtos
            </button>

            <nav className="migalhas" aria-label="Navegação estrutural">
                Produtos <span>/</span> {produto.categoria.name} <span>/</span>{' '}
                <b>{produto.name}</b>
            </nav>

            <section className="produto-principal">
                <div className="galeria-produto">
                    <div className="miniaturas-produto">
                        {imagens.map((origem, indice) => (
                            <button
                                key={origem}
                                type="button"
                                className={miniatura === indice ? 'miniatura-ativa' : ''}
                                onClick={() => setMiniatura(indice)}
                            >
                                <img
                                    src={origem}
                                    alt=""
                                    onError={(evento) => {
                                        evento.currentTarget.src = imagemReserva;
                                    }}
                                />
                            </button>
                        ))}
                    </div>
                    <div
                        className="imagem-principal-produto"
                        onMouseMove={moverZoom}
                        onMouseLeave={() => setZoom((atual) => ({ ...atual, ativo: false }))}
                    >
                        <img
                            src={imagens[miniatura]}
                            alt={produto.name}
                            onError={(evento) => {
                                evento.currentTarget.src = imagemReserva;
                            }}
                        />
                        <span
                            className="lente-zoom-produto"
                            style={{
                                opacity: zoom.ativo ? 1 : 0,
                                left: `${zoom.x}%`,
                                top: `${zoom.y}%`,
                            }}
                        />
                        <div
                            className={`zoom-produto ${zoom.ativo ? 'zoom-produto--ativo' : ''}`}
                            style={{
                                backgroundImage: `url("${imagens[miniatura]}")`,
                                backgroundPosition: `${zoom.x}% ${zoom.y}%`,
                            }}
                        />
                    </div>
                </div>

                <div className="informacoes-produto">
                    <div className="selos-produto">
                        <span>Novo</span>
                        <span>Lumora seleciona</span>
                    </div>
                    <span className="categoria-detalhe">
                        {produto.categoria.name} · {produto.brand ?? 'Lumora'}
                    </span>
                    <h1>{produto.name}</h1>
                    <div className="avaliacao-detalhe">
                        <span>4,8</span>
                        <div>
                            {[0, 1, 2, 3, 4].map((estrela) => (
                                <Star
                                    key={estrela}
                                    size={16}
                                    fill={estrela < 4 ? 'currentColor' : 'none'}
                                />
                            ))}
                        </div>
                        <small>avaliação demonstrativa</small>
                    </div>

                    <ul className="destaques-produto">
                        <li>Produto novo, selecionado para o catálogo Lumora</li>
                        <li>Compra protegida e acompanhamento do pedido</li>
                        <li>Pagamento por Pix ou cartão no checkout</li>
                    </ul>

                    <div className="preco-detalhe">
                        <strong>{moeda.format(valor)}</strong>
                        <span>12x de {moeda.format(valor / 12)} sem juros</span>
                        <b>{moeda.format(valor * 0.95)} no Pix</b>
                    </div>
                </div>

                <aside className="painel-compra">
                    <div className="entrega-produto">
                        <Truck size={20} />
                        <div>
                            <strong>Calcule a entrega</strong>
                            <span>Informe seu CEP na etapa de endereço.</span>
                        </div>
                    </div>
                    <div className="disponibilidade-produto">
                        <Check size={18} />
                        <div>
                            <strong>{produto.stock > 0 ? 'Em estoque' : 'Indisponível'}</strong>
                            <span>{produto.stock} unidades disponíveis</span>
                        </div>
                    </div>
                    <label>
                        Quantidade
                        <select
                            value={quantidade}
                            onChange={(evento) => setQuantidade(Number(evento.target.value))}
                        >
                            {Array.from(
                                { length: Math.min(produto.stock, 5) },
                                (_, indice) => indice + 1,
                            ).map((item) => (
                                <option key={item} value={item}>
                                    {item} {item === 1 ? 'unidade' : 'unidades'}
                                </option>
                            ))}
                        </select>
                    </label>
                    <button
                        className="comprar-agora"
                        type="button"
                        disabled={produto.stock === 0}
                        onClick={() => aoAdicionar(produto, quantidade)}
                    >
                        Comprar agora
                    </button>
                    <button
                        className="adicionar-detalhe"
                        type="button"
                        disabled={produto.stock === 0}
                        onClick={() => aoAdicionar(produto, quantidade)}
                    >
                        <ShoppingBag size={17} />
                        Adicionar ao carrinho
                    </button>
                    <div className="seguranca-compra">
                        <ShieldCheck size={20} />
                        Compra segura e dados protegidos
                    </div>
                </aside>
            </section>

            {relacionados.length > 0 ? (
                <section className="secao-detalhe produtos-relacionados">
                    <header>
                        <h2>Produtos relacionados</h2>
                        <span>Outras escolhas do catálogo Lumora</span>
                    </header>
                    <div>
                        {relacionados.slice(0, 4).map((item) => (
                            <button
                                key={item.id}
                                type="button"
                                onClick={() => {
                                    setMiniatura(0);
                                    aoSelecionar(item);
                                }}
                            >
                                <img
                                    src={imagem(item)}
                                    alt=""
                                    onError={(evento) => {
                                        evento.currentTarget.src = imagemReserva;
                                    }}
                                />
                                <span>{item.name}</span>
                                <strong>{moeda.format(Number(item.price))}</strong>
                            </button>
                        ))}
                    </div>
                </section>
            ) : null}

            <div className="conteudo-inferior-produto">
                <section className="secao-detalhe">
                    <header>
                        <h2>Características do produto</h2>
                        <span>Informações essenciais para decidir melhor</span>
                    </header>
                    <dl className="tabela-especificacoes">
                        {especificacoes.map(([titulo, valorEspecificacao]) => (
                            <div key={titulo}>
                                <dt>{titulo}</dt>
                                <dd>{valorEspecificacao}</dd>
                            </div>
                        ))}
                    </dl>
                </section>

                <section className="secao-detalhe descricao-produto">
                    <header>
                        <h2>Descrição</h2>
                        <span>Conheça melhor este produto</span>
                    </header>
                    <p>
                        {produto.description ??
                            `${produto.name} faz parte da seleção de tecnologia da Lumora.`}
                    </p>
                    <p>
                        Antes da compra, confira as características, disponibilidade e
                        condições de entrega. O pedido poderá ser acompanhado pela sua conta.
                    </p>
                </section>

                <section className="secao-detalhe perguntas-produto">
                    <header>
                        <h2>Perguntas frequentes</h2>
                        <span>Respostas rápidas para dúvidas comuns</span>
                    </header>
                    <details open>
                        <summary>O produto é novo?</summary>
                        <p>Sim. Os produtos ativos deste catálogo são anunciados como novos.</p>
                    </details>
                    <details>
                        <summary>Como funciona a entrega?</summary>
                        <p>O endereço e o prazo serão confirmados durante o checkout.</p>
                    </details>
                    <details>
                        <summary>Quais formas de pagamento serão aceitas?</summary>
                        <p>Pix e cartão pelo checkout do Mercado Pago.</p>
                    </details>
                </section>
            </div>
        </main>
    );
}
