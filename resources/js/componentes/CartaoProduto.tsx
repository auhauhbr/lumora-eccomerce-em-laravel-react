import type { Produto } from '../tipos/catalogo';
import { ShoppingBag, Star, Truck } from './Icones';

type Propriedades = {
    produto: Produto;
    modo: 'grade' | 'lista';
    aoSelecionar: (produto: Produto) => void;
    aoAdicionar: (produto: Produto) => void;
};

const moeda = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const imagemReserva = '/imagens/produtos/lumora-tecnologia.svg';

export function CartaoProduto({
    produto,
    modo,
    aoSelecionar,
    aoAdicionar,
}: Propriedades) {
    const disponivel = produto.stock > 0;
    const valor = Number(produto.price);

    return (
        <article
            className={`cartao-produto cartao-produto--${modo}`}
            onClick={() => aoSelecionar(produto)}
        >
            <div className="produto-imagem">
                <img
                    src={produto.image_url ?? imagemReserva}
                    alt={produto.name}
                    loading="lazy"
                    onError={(evento) => {
                        evento.currentTarget.src = imagemReserva;
                    }}
                />
                <span className={disponivel ? 'estoque-ok' : 'estoque-zero'}>
                    {disponivel ? `${produto.stock} em estoque` : 'Indisponível'}
                </span>
            </div>
            <div className="produto-conteudo">
                <span className="produto-categoria">
                    {produto.categoria.name} · {produto.brand ?? 'Lumora'}
                </span>
                <h2>{produto.name}</h2>
                <div className="produto-avaliacao" aria-label="Produto bem avaliado">
                    {[0, 1, 2, 3].map((estrela) => (
                        <Star key={estrela} size={13} fill="currentColor" />
                    ))}
                    <Star size={13} />
                    <span>4,8</span>
                </div>
                <p>{produto.description ?? 'Produto selecionado para o catálogo Lumora.'}</p>
                <small className="produto-parcelamento">
                    12x de {moeda.format(valor / 12)} sem juros
                </small>
                <span className="produto-frete">
                    <Truck size={14} />
                    Frete grátis acima de R$ 199
                </span>
                <div className="produto-rodape">
                    <strong>{moeda.format(valor)}</strong>
                    <button
                        type="button"
                        disabled={!disponivel}
                        aria-label={`Adicionar ${produto.name} ao carrinho`}
                        onClick={(evento) => {
                            evento.stopPropagation();
                            aoAdicionar(produto);
                        }}
                    >
                        <ShoppingBag size={15} />
                        Adicionar
                    </button>
                </div>
            </div>
        </article>
    );
}
