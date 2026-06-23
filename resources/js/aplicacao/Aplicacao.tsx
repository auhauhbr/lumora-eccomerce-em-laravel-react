import { useCallback, useEffect, useMemo, useState } from 'react';
import { Cabecalho } from '../componentes/Cabecalho';
import { CartaoProduto } from '../componentes/CartaoProduto';
import {
    FiltrosCatalogo,
    type Filtros,
} from '../componentes/FiltrosCatalogo';
import { ModalAutenticacao } from '../componentes/ModalAutenticacao';
import { PainelAdministrativo } from '../componentes/PainelAdministrativo';
import { PaginaCheckout } from '../componentes/PaginaCheckout';
import { PaginaConta } from '../componentes/PaginaConta';
import { PaginaProduto } from '../componentes/PaginaProduto';
import { PaginaRetornoPagamento } from '../componentes/PaginaRetornoPagamento';
import { PainelCarrinho } from '../componentes/PainelCarrinho';
import { Grid2X2, List, PackageSearch, Search } from '../componentes/Icones';
import {
    ErroApi,
    obterToken,
    removerToken,
    requisitar,
} from '../servicos/api';
import type {
    Carrinho,
    Categoria,
    ItemCarrinho,
    Produto,
    Usuario,
} from '../tipos/catalogo';

type RespostaCatalogo = {
    dados: Produto[];
    filtros: {
        marcas: string[];
    };
    paginacao: {
        pagina_atual: number;
        ultima_pagina: number;
        por_pagina: number;
        total: number;
    };
};
type RespostaCategorias = { dados: Categoria[] };
type RespostaCarrinho = {
    dados: Carrinho;
    subtotal: string;
    quantidade_itens: number;
};
type RespostaUsuario = { usuario: Usuario };
type TipoRetorno = 'sucesso' | 'pendente' | 'falha';

const filtrosIniciais: Filtros = {
    categoria: '',
    marca: '',
    condicao: '',
    minimo: '',
    maximo: '',
    ordenacao: '',
    emEstoque: false,
};

export function Aplicacao() {
    const [produtos, setProdutos] = useState<Produto[]>([]);
    const [categorias, setCategorias] = useState<Categoria[]>([]);
    const [marcas, setMarcas] = useState<string[]>([]);
    const [filtros, setFiltros] = useState(filtrosIniciais);
    const [busca, setBusca] = useState('');
    const [buscaAplicada, setBuscaAplicada] = useState('');
    const [total, setTotal] = useState(0);
    const [pagina, setPagina] = useState(1);
    const [ultimaPagina, setUltimaPagina] = useState(1);
    const [carregando, setCarregando] = useState(true);
    const [erro, setErro] = useState('');
    const [produtoSelecionado, setProdutoSelecionado] = useState<Produto | null>(null);
    const [usuario, setUsuario] = useState<Usuario | null>(null);
    const [carrinho, setCarrinho] = useState<Carrinho | null>(null);
    const [subtotal, setSubtotal] = useState('0.00');
    const [quantidadeCarrinho, setQuantidadeCarrinho] = useState(0);
    const [carrinhoAberto, setCarrinhoAberto] = useState(false);
    const [loginAberto, setLoginAberto] = useState(false);
    const [filtrosAbertos, setFiltrosAbertos] = useState(false);
    const [carregandoCarrinho, setCarregandoCarrinho] = useState(false);
    const [aviso, setAviso] = useState('');
    const [modoVisualizacao, setModoVisualizacao] = useState<'grade' | 'lista'>('grade');
    const [checkoutAberto, setCheckoutAberto] = useState(false);
    const [carrinhoCheckout, setCarrinhoCheckout] = useState<Carrinho | null>(null);
    const [subtotalCheckout, setSubtotalCheckout] = useState('0.00');
    const [contaAberta, setContaAberta] = useState(false);
    const [adminAberto, setAdminAberto] = useState(false);
    const [retornoPagamento] = useState(() => {
        const correspondencia = window.location.pathname.match(
            /^\/pagamento\/(sucesso|pendente|falha)$/,
        );
        if (!correspondencia) return null;
        const pedido = Number(new URLSearchParams(window.location.search).get('pedido'));
        return {
            tipo: correspondencia[1] as TipoRetorno,
            pedidoId: Number.isInteger(pedido) && pedido > 0 ? pedido : null,
        };
    });
    const [exibirRetorno, setExibirRetorno] = useState(Boolean(retornoPagamento));

    const parametros = useMemo(() => {
        const consulta = new URLSearchParams({ per_page: '12', page: String(pagina) });
        if (buscaAplicada) consulta.set('search', buscaAplicada);
        if (filtros.categoria) consulta.set('category', filtros.categoria);
        if (filtros.marca) consulta.set('brand', filtros.marca);
        if (filtros.condicao) consulta.set('condition', filtros.condicao);
        if (filtros.minimo) consulta.set('min_price', filtros.minimo);
        if (filtros.maximo) consulta.set('max_price', filtros.maximo);
        if (filtros.ordenacao) consulta.set('sort', filtros.ordenacao);
        if (filtros.emEstoque) consulta.set('in_stock', '1');
        return consulta.toString();
    }, [buscaAplicada, filtros, pagina]);

    const carregarCarrinho = useCallback(async () => {
        if (!obterToken()) return;
        setCarregandoCarrinho(true);
        try {
            const resposta = await requisitar<RespostaCarrinho>('/api/cart');
            setCarrinho(resposta.dados);
            setSubtotal(resposta.subtotal);
            setQuantidadeCarrinho(resposta.quantidade_itens);
        } catch (falha) {
            if (falha instanceof ErroApi && falha.status === 401) {
                removerToken();
                setUsuario(null);
            }
        } finally {
            setCarregandoCarrinho(false);
        }
    }, []);

    useEffect(() => {
        Promise.all([
            requisitar<RespostaCategorias>('/api/categories'),
            obterToken()
                ? requisitar<RespostaUsuario>('/api/me')
                : Promise.resolve(null),
        ])
            .then(([respostaCategorias, respostaUsuario]) => {
                setCategorias(respostaCategorias.dados);
                if (respostaUsuario) setUsuario(respostaUsuario.usuario);
            })
            .catch(() => removerToken());
    }, []);

    useEffect(() => {
        setCarregando(true);
        setErro('');
        requisitar<RespostaCatalogo>(`/api/products?${parametros}`)
            .then((resposta) => {
                setProdutos(resposta.dados);
                setMarcas(resposta.filtros.marcas);
                setTotal(resposta.paginacao.total);
                setPagina(resposta.paginacao.pagina_atual);
                setUltimaPagina(resposta.paginacao.ultima_pagina);
            })
            .catch(() => setErro('Não foi possível carregar o catálogo.'))
            .finally(() => setCarregando(false));
    }, [parametros]);

    useEffect(() => {
        if (usuario) void carregarCarrinho();
    }, [usuario, carregarCarrinho]);

    useEffect(() => {
        if (!produtoSelecionado) return;
        const temporizador = window.setTimeout(() => {
            document.documentElement.scrollTop = 0;
            document.body.scrollTop = 0;
            window.scrollTo(0, 0);
        }, 50);
        return () => window.clearTimeout(temporizador);
    }, [produtoSelecionado]);

    async function adicionarProduto(produto: Produto, quantidade = 1) {
        if (!usuario) {
            setProdutoSelecionado(null);
            setLoginAberto(true);
            return;
        }
        try {
            const resposta = await requisitar<RespostaCarrinho>('/api/cart/items', {
                method: 'POST',
                body: JSON.stringify({ product_id: produto.id, quantity: quantidade }),
            });
            aplicarCarrinho(resposta);
            setProdutoSelecionado(null);
            setCarrinhoAberto(true);
            mostrarAviso(`${produto.name} foi adicionado.`);
        } catch (falha) {
            mostrarAviso(falha instanceof ErroApi ? falha.message : 'Não foi possível adicionar.');
        }
    }

    async function atualizarItem(item: ItemCarrinho, quantidade: number) {
        const resposta = await requisitar<RespostaCarrinho>(
            `/api/cart/items/${item.id}`,
            {
                method: 'PATCH',
                body: JSON.stringify({ quantity: quantidade }),
            },
        );
        aplicarCarrinho(resposta);
    }

    async function removerItem(item: ItemCarrinho) {
        const resposta = await requisitar<RespostaCarrinho>(
            `/api/cart/items/${item.id}`,
            { method: 'DELETE' },
        );
        aplicarCarrinho(resposta);
    }

    function aplicarCarrinho(resposta: RespostaCarrinho) {
        setCarrinho(resposta.dados);
        setSubtotal(resposta.subtotal);
        setQuantidadeCarrinho(resposta.quantidade_itens);
    }

    function mostrarAviso(mensagem: string) {
        setAviso(mensagem);
        window.setTimeout(() => setAviso(''), 2600);
    }

    function aplicarBusca() {
        setPagina(1);
        setBuscaAplicada(busca.trim());
    }

    function alterarFiltros(novosFiltros: Filtros) {
        setPagina(1);
        setFiltros(novosFiltros);
    }

    function abrirCheckout() {
        if (!carrinho?.itens.length) return;
        setCarrinhoCheckout(carrinho);
        setSubtotalCheckout(subtotal);
        setCarrinhoAberto(false);
        setProdutoSelecionado(null);
        setCheckoutAberto(true);
        window.scrollTo({ top: 0 });
    }

    function abrirConta() {
        setProdutoSelecionado(null);
        setCheckoutAberto(false);
        setExibirRetorno(false);
        if (usuario?.role === 'admin') setAdminAberto(true);
        else setContaAberta(true);
        window.history.replaceState({}, '', '/');
        window.scrollTo({ top: 0 });
    }

    function voltarLoja() {
        setProdutoSelecionado(null);
        setCheckoutAberto(false);
        setContaAberta(false);
        setAdminAberto(false);
        setExibirRetorno(false);
        window.history.replaceState({}, '', '/');
        window.scrollTo({ top: 0 });
    }

    function abrirProduto(produto: Produto) {
        setProdutoSelecionado(produto);
    }

    async function sair() {
        try {
            await requisitar('/api/logout', { method: 'POST' });
        } finally {
            removerToken();
            setUsuario(null);
            setCarrinho(null);
            setQuantidadeCarrinho(0);
            setSubtotal('0.00');
            setContaAberta(false);
            setAdminAberto(false);
        }
    }

    return (
        <div className="aplicacao">
            <Cabecalho
                usuario={usuario}
                quantidadeCarrinho={quantidadeCarrinho}
                aoAbrirCarrinho={() => setCarrinhoAberto(true)}
                aoAbrirLogin={() => setLoginAberto(true)}
                aoAbrirConta={abrirConta}
                aoAlternarFiltros={() => setFiltrosAbertos((atual) => !atual)}
            />

            {adminAberto && usuario?.role === 'admin' ? (
                <PainelAdministrativo usuario={usuario} aoVoltar={voltarLoja} />
            ) : exibirRetorno && retornoPagamento ? (
                <PaginaRetornoPagamento
                    tipo={retornoPagamento.tipo}
                    pedidoId={retornoPagamento.pedidoId}
                    autenticado={Boolean(usuario)}
                    aoAbrirConta={abrirConta}
                    aoVoltar={voltarLoja}
                    aoEntrar={() => setLoginAberto(true)}
                />
            ) : contaAberta && usuario ? (
                <PaginaConta usuario={usuario} aoVoltar={voltarLoja} aoSair={sair} />
            ) : checkoutAberto && carrinhoCheckout ? (
                <PaginaCheckout
                    carrinho={carrinhoCheckout}
                    subtotal={subtotalCheckout}
                    aoVoltar={voltarLoja}
                    aoPedidoCriado={() => {
                        setCarrinho(null);
                        setSubtotal('0.00');
                        setQuantidadeCarrinho(0);
                    }}
                />
            ) : produtoSelecionado ? (
                <PaginaProduto
                    produto={produtoSelecionado}
                    relacionados={produtos.filter(
                        (produto) => produto.id !== produtoSelecionado.id,
                    )}
                    aoVoltar={() => setProdutoSelecionado(null)}
                    aoSelecionar={abrirProduto}
                    aoAdicionar={adicionarProduto}
                />
            ) : (
            <div className="estrutura">
                <FiltrosCatalogo
                    categorias={categorias}
                    marcas={marcas}
                    filtros={filtros}
                    aberto={filtrosAbertos}
                    aoAlterar={alterarFiltros}
                    aoFechar={() => setFiltrosAbertos(false)}
                />

                <main className="conteudo">
                    <section className="barra-busca">
                        <Search size={18} />
                        <input
                            value={busca}
                            placeholder="Buscar por nome ou descrição..."
                            onChange={(evento) => setBusca(evento.target.value)}
                            onKeyDown={(evento) => {
                                if (evento.key === 'Enter') aplicarBusca();
                            }}
                        />
                        <button type="button" onClick={aplicarBusca}>
                            Buscar produtos
                        </button>
                    </section>

                    <section className="cabecalho-catalogo">
                        <div>
                            <span>Catálogo Lumora</span>
                            <h1>Produtos</h1>
                        </div>
                        <div className="acoes-catalogo">
                            <p>
                                {total}{' '}
                                {total === 1 ? 'produto encontrado' : 'produtos encontrados'}
                            </p>
                            <div className="alternador-visualizacao" aria-label="Visualização">
                                <button
                                    type="button"
                                    className={modoVisualizacao === 'grade' ? 'ativo' : ''}
                                    onClick={() => setModoVisualizacao('grade')}
                                >
                                    <Grid2X2 size={15} />
                                    Grade
                                </button>
                                <button
                                    type="button"
                                    className={modoVisualizacao === 'lista' ? 'ativo' : ''}
                                    onClick={() => setModoVisualizacao('lista')}
                                >
                                    <List size={16} />
                                    Lista
                                </button>
                            </div>
                        </div>
                    </section>

                    {carregando ? (
                        <div className="estado-catalogo">Carregando produtos...</div>
                    ) : erro ? (
                        <div className="estado-catalogo">{erro}</div>
                    ) : produtos.length === 0 ? (
                        <div className="estado-catalogo">
                            <PackageSearch size={40} />
                            <h2>Nenhum produto encontrado</h2>
                            <p>Tente remover algum filtro ou buscar outro termo.</p>
                        </div>
                    ) : (
                        <section
                            className={`grade-produtos grade-produtos--${modoVisualizacao}`}
                        >
                            {produtos.map((produto) => (
                                <CartaoProduto
                                    key={produto.id}
                                    produto={produto}
                                    modo={modoVisualizacao}
                                    aoSelecionar={abrirProduto}
                                    aoAdicionar={adicionarProduto}
                                />
                            ))}
                        </section>
                    )}

                    {!carregando && !erro && produtos.length > 0 ? (
                        <nav className="paginacao-catalogo" aria-label="Paginação do catálogo">
                            <button
                                type="button"
                                disabled={pagina <= 1}
                                onClick={() => setPagina((atual) => Math.max(1, atual - 1))}
                            >
                                Anterior
                            </button>
                            <span>
                                Página {pagina} de {ultimaPagina}
                            </span>
                            <button
                                type="button"
                                disabled={pagina >= ultimaPagina}
                                onClick={() =>
                                    setPagina((atual) => Math.min(ultimaPagina, atual + 1))
                                }
                            >
                                Próxima
                            </button>
                        </nav>
                    ) : null}
                </main>
            </div>
            )}
            <PainelCarrinho
                aberto={carrinhoAberto}
                carrinho={carrinho}
                subtotal={subtotal}
                carregando={carregandoCarrinho}
                autenticado={Boolean(usuario)}
                aoFechar={() => setCarrinhoAberto(false)}
                aoEntrar={() => {
                    setCarrinhoAberto(false);
                    setLoginAberto(true);
                }}
                aoFinalizar={abrirCheckout}
                aoAtualizar={atualizarItem}
                aoRemover={removerItem}
            />
            <ModalAutenticacao
                aberto={loginAberto}
                aoFechar={() => setLoginAberto(false)}
                aoAutenticar={setUsuario}
            />
            {aviso ? <div className="aviso">{aviso}</div> : null}
        </div>
    );
}
