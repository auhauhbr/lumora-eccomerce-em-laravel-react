import { useCallback, useEffect, useMemo, useState, type FormEvent } from 'react';
import { ErroApi, requisitar } from '../servicos/api';
import type { Categoria, Marca, Pedido, Produto, Usuario } from '../tipos/catalogo';
import {
    ArrowLeft,
    BarChart3,
    Boxes,
    Check,
    ClipboardList,
    PackagePlus,
    WalletCards,
} from './Icones';

const moeda = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

type Secao = 'dashboard' | 'produtos' | 'pedidos';

type DadosDashboard = {
    total_vendas: number;
    faturamento_total: string;
    faturamento_mes: string;
    pedidos_aguardando_pagamento: number;
    pedidos_pagos: number;
    pedidos_enviados: number;
    produtos_ativos: number;
    produtos_estoque_baixo: number;
    ultimos_pedidos: Pedido[];
    estoque_baixo: Produto[];
};

type FormularioProduto = {
    category_id: string;
    name: string;
    slug: string;
    description: string;
    brand: string;
    condition: 'novo' | 'usado' | 'recondicionado';
    price: string;
    stock: string;
    image_url: string;
    image_urls: string;
    is_active: boolean;
};

type FiltrosProdutosAdmin = {
    busca: string;
    categoria: string;
    marca: string;
    status: string;
    estoque: string;
    ordem: string;
};

type Propriedades = {
    usuario: Usuario;
    aoVoltar: () => void;
};

const formularioVazio: FormularioProduto = {
    category_id: '',
    name: '',
    slug: '',
    description: '',
    brand: '',
    condition: 'novo',
    price: '',
    stock: '0',
    image_url: '',
    image_urls: '',
    is_active: true,
};

const filtrosProdutosIniciais: FiltrosProdutosAdmin = {
    busca: '',
    categoria: '',
    marca: '',
    status: '',
    estoque: '',
    ordem: 'nome',
};

const rotulosStatus: Record<string, string> = {
    pending_payment: 'Aguardando pagamento',
    paid: 'Pago',
    processing: 'Em preparação',
    shipped: 'Enviado',
    delivered: 'Entregue',
    cancelled: 'Cancelado',
    expired: 'Expirado',
};

const proximosStatus: Record<string, Array<{ valor: string; rotulo: string }>> = {
    pending_payment: [
        { valor: 'cancelled', rotulo: 'Cancelar' },
        { valor: 'expired', rotulo: 'Expirar' },
    ],
    paid: [
        { valor: 'processing', rotulo: 'Iniciar preparação' },
        { valor: 'cancelled', rotulo: 'Cancelar e reembolsar' },
    ],
    processing: [
        { valor: 'shipped', rotulo: 'Marcar como enviado' },
        { valor: 'cancelled', rotulo: 'Cancelar e reembolsar' },
    ],
    shipped: [{ valor: 'delivered', rotulo: 'Marcar como entregue' }],
};

export function PainelAdministrativo({ usuario, aoVoltar }: Propriedades) {
    const [secao, setSecao] = useState<Secao>('dashboard');
    const [dashboard, setDashboard] = useState<DadosDashboard | null>(null);
    const [produtos, setProdutos] = useState<Produto[]>([]);
    const [categorias, setCategorias] = useState<Categoria[]>([]);
    const [marcas, setMarcas] = useState<Marca[]>([]);
    const [pedidos, setPedidos] = useState<Pedido[]>([]);
    const [formulario, setFormulario] = useState(formularioVazio);
    const [novaCategoria, setNovaCategoria] = useState('');
    const [novaMarca, setNovaMarca] = useState('');
    const [filtrosProdutos, setFiltrosProdutos] = useState(filtrosProdutosIniciais);
    const [produtoEditando, setProdutoEditando] = useState<Produto | null>(null);
    const [produtoEstoque, setProdutoEstoque] = useState<Produto | null>(null);
    const [ajuste, setAjuste] = useState('');
    const [motivo, setMotivo] = useState('');
    const [carregando, setCarregando] = useState(true);
    const [processando, setProcessando] = useState(false);
    const [aviso, setAviso] = useState('');

    const carregar = useCallback(async () => {
        setCarregando(true);
        setAviso('');
        try {
            const [
                respostaDashboard,
                respostaProdutos,
                respostaCategorias,
                respostaMarcas,
                respostaPedidos,
            ] =
                await Promise.all([
                    requisitar<{ dados: DadosDashboard }>('/api/admin/dashboard'),
                    requisitar<{ dados: Produto[] }>('/api/admin/products'),
                    requisitar<{ dados: Categoria[] }>('/api/admin/categories'),
                    requisitar<{ dados: Marca[] }>('/api/admin/brands'),
                    requisitar<{ dados: Pedido[] }>('/api/admin/orders'),
                ]);
            setDashboard(respostaDashboard.dados);
            setProdutos(respostaProdutos.dados);
            setCategorias(respostaCategorias.dados);
            setMarcas(respostaMarcas.dados);
            setPedidos(respostaPedidos.dados);
        } catch (falha) {
            setAviso(
                falha instanceof ErroApi
                    ? falha.message
                    : 'Não foi possível carregar o painel administrativo.',
            );
        } finally {
            setCarregando(false);
        }
    }, []);

    useEffect(() => {
        void carregar();
    }, [carregar]);

    const estoqueTotal = useMemo(
        () => produtos.reduce((total, produto) => total + produto.stock, 0),
        [produtos],
    );

    const produtosFiltrados = useMemo(() => {
        const busca = filtrosProdutos.busca.trim().toLowerCase();

        return [...produtos]
            .filter((produto) => {
                if (busca && !produto.name.toLowerCase().includes(busca)) return false;
                if (filtrosProdutos.categoria && produto.categoria.slug !== filtrosProdutos.categoria) {
                    return false;
                }
                if (filtrosProdutos.marca && produto.brand !== filtrosProdutos.marca) return false;
                if (filtrosProdutos.status === 'ativo' && !produto.is_active) return false;
                if (filtrosProdutos.status === 'inativo' && produto.is_active) return false;
                if (filtrosProdutos.estoque === 'baixo' && produto.stock > 5) return false;
                if (filtrosProdutos.estoque === 'zerado' && produto.stock !== 0) return false;
                if (filtrosProdutos.estoque === 'disponivel' && produto.stock <= 0) return false;

                return true;
            })
            .sort((a, b) => {
                if (filtrosProdutos.ordem === 'preco_asc') return Number(a.price) - Number(b.price);
                if (filtrosProdutos.ordem === 'preco_desc') return Number(b.price) - Number(a.price);
                if (filtrosProdutos.ordem === 'estoque_asc') return a.stock - b.stock;
                if (filtrosProdutos.ordem === 'estoque_desc') return b.stock - a.stock;

                return a.name.localeCompare(b.name, 'pt-BR');
            });
    }, [filtrosProdutos, produtos]);

    function editarProduto(produto: Produto) {
        setProdutoEditando(produto);
        setFormulario({
            category_id: String(produto.category_id),
            name: produto.name,
            slug: produto.slug,
            description: produto.description ?? '',
            brand: produto.brand ?? '',
            condition: produto.condition,
            price: produto.price,
            stock: String(produto.stock),
            image_url: produto.image_url ?? '',
            image_urls: produto.image_urls?.join('\n') ?? '',
            is_active: produto.is_active,
        });
    }

    function limparFormulario() {
        setProdutoEditando(null);
        setFormulario(formularioVazio);
    }

    async function salvarProduto(evento: FormEvent) {
        evento.preventDefault();
        setProcessando(true);
        setAviso('');
        try {
            const corpo = {
                ...formulario,
                category_id: Number(formulario.category_id),
                price: Number(formulario.price),
                stock: Number(formulario.stock),
                image_url: formulario.image_url || null,
                image_urls: formulario.image_urls
                    .split('\n')
                    .map((url) => url.trim())
                    .filter(Boolean),
            };
            const caminho = produtoEditando
                ? `/api/admin/products/${produtoEditando.slug}`
                : '/api/admin/products';
            const resposta = await requisitar<{ dados: Produto }>(caminho, {
                method: produtoEditando ? 'PUT' : 'POST',
                body: JSON.stringify(corpo),
            });
            setProdutos((atuais) =>
                produtoEditando
                    ? atuais.map((produto) =>
                          produto.id === resposta.dados.id ? resposta.dados : produto,
                      )
                    : [resposta.dados, ...atuais],
            );
            limparFormulario();
            setAviso('Produto salvo com sucesso.');
        } catch (falha) {
            setAviso(falha instanceof ErroApi ? falha.message : 'Não foi possível salvar.');
        } finally {
            setProcessando(false);
        }
    }

    async function criarCategoriaRapida() {
        const nome = novaCategoria.trim();
        if (!nome) return;
        setProcessando(true);
        setAviso('');
        try {
            const resposta = await requisitar<{ dados: Categoria }>('/api/admin/categories', {
                method: 'POST',
                body: JSON.stringify({
                    name: nome,
                    description: `Categoria ${nome} cadastrada pelo painel administrativo.`,
                    is_active: true,
                }),
            });
            setCategorias((atuais) =>
                [...atuais, resposta.dados].sort((a, b) => a.name.localeCompare(b.name, 'pt-BR')),
            );
            setFormulario((atual) => ({
                ...atual,
                category_id: String(resposta.dados.id),
            }));
            setNovaCategoria('');
            setAviso('Categoria criada e selecionada no produto.');
        } catch (falha) {
            setAviso(falha instanceof ErroApi ? falha.message : 'Não foi possível criar a categoria.');
        } finally {
            setProcessando(false);
        }
    }

    async function criarMarcaRapida() {
        const nome = novaMarca.trim();
        if (!nome) return;
        setProcessando(true);
        setAviso('');
        try {
            const resposta = await requisitar<{ dados: Marca }>('/api/admin/brands', {
                method: 'POST',
                body: JSON.stringify({
                    name: nome,
                    description: `Marca ${nome} cadastrada pelo painel administrativo.`,
                    is_active: true,
                }),
            });
            setMarcas((atuais) =>
                [...atuais, resposta.dados].sort((a, b) => a.name.localeCompare(b.name, 'pt-BR')),
            );
            setFormulario((atual) => ({
                ...atual,
                brand: resposta.dados.name,
            }));
            setNovaMarca('');
            setAviso('Marca criada e selecionada no produto.');
        } catch (falha) {
            setAviso(falha instanceof ErroApi ? falha.message : 'Não foi possível criar a marca.');
        } finally {
            setProcessando(false);
        }
    }

    async function alternarProduto(produto: Produto) {
        setProcessando(true);
        try {
            const resposta = await requisitar<{ dados: Produto }>(
                `/api/admin/products/${produto.slug}/toggle-active`,
                { method: 'PATCH' },
            );
            setProdutos((atuais) =>
                atuais.map((item) =>
                    item.id === produto.id
                        ? { ...item, is_active: resposta.dados.is_active }
                        : item,
                ),
            );
        } catch (falha) {
            setAviso(falha instanceof ErroApi ? falha.message : 'Não foi possível alterar.');
        } finally {
            setProcessando(false);
        }
    }

    async function ajustarEstoque(evento: FormEvent) {
        evento.preventDefault();
        if (!produtoEstoque) return;
        setProcessando(true);
        setAviso('');
        try {
            const resposta = await requisitar<{ dados: Produto }>(
                `/api/admin/products/${produtoEstoque.slug}/stock-adjustment`,
                {
                    method: 'POST',
                    body: JSON.stringify({ quantity: Number(ajuste), reason: motivo }),
                },
            );
            setProdutos((atuais) =>
                atuais.map((produto) =>
                    produto.id === produtoEstoque.id
                        ? { ...produto, stock: resposta.dados.stock }
                        : produto,
                ),
            );
            setProdutoEstoque(null);
            setAjuste('');
            setMotivo('');
            setAviso('Estoque ajustado e movimentação registrada.');
        } catch (falha) {
            setAviso(falha instanceof ErroApi ? falha.message : 'Não foi possível ajustar.');
        } finally {
            setProcessando(false);
        }
    }

    async function alterarStatus(pedido: Pedido, status: string) {
        setProcessando(true);
        setAviso('');
        try {
            const resposta = await requisitar<{ dados: Pedido }>(
                `/api/admin/orders/${pedido.id}/status`,
                { method: 'PATCH', body: JSON.stringify({ status }) },
            );
            setPedidos((atuais) =>
                atuais.map((item) => (item.id === pedido.id ? resposta.dados : item)),
            );
            setAviso('Status do pedido atualizado.');
        } catch (falha) {
            setAviso(
                falha instanceof ErroApi ? falha.message : 'Não foi possível alterar o pedido.',
            );
        } finally {
            setProcessando(false);
        }
    }

    return (
        <main className="painel-admin">
            <header className="topo-admin">
                <button type="button" onClick={aoVoltar}>
                    <ArrowLeft size={17} />
                    Voltar à loja
                </button>
                <div>
                    <span>Painel administrativo</span>
                    <h1>Operação Lumora</h1>
                    <p>Olá, {usuario.name}. Gerencie catálogo, estoque e pedidos.</p>
                </div>
            </header>

            <div className="estrutura-admin">
                <nav className="menu-admin">
                    <button
                        className={secao === 'dashboard' ? 'ativo' : ''}
                        onClick={() => setSecao('dashboard')}
                    >
                        <BarChart3 size={17} /> Dashboard
                    </button>
                    <button
                        className={secao === 'produtos' ? 'ativo' : ''}
                        onClick={() => setSecao('produtos')}
                    >
                        <Boxes size={17} /> Produtos e estoque
                    </button>
                    <button
                        className={secao === 'pedidos' ? 'ativo' : ''}
                        onClick={() => setSecao('pedidos')}
                    >
                        <ClipboardList size={17} /> Pedidos
                    </button>
                </nav>

                <section className="conteudo-admin">
                    {aviso ? <div className="aviso-admin">{aviso}</div> : null}
                    {carregando ? (
                        <div className="estado-admin">Carregando painel...</div>
                    ) : secao === 'dashboard' && dashboard ? (
                        <>
                            <div className="metricas-admin">
                                <article>
                                    <span>Faturamento total</span>
                                    <strong>{moeda.format(Number(dashboard.faturamento_total))}</strong>
                                    <small>{dashboard.total_vendas} vendas pagas</small>
                                </article>
                                <article>
                                    <span>Faturamento no mês</span>
                                    <strong>{moeda.format(Number(dashboard.faturamento_mes))}</strong>
                                    <small>Pagamentos aprovados</small>
                                </article>
                                <article>
                                    <span>Aguardando pagamento</span>
                                    <strong>{dashboard.pedidos_aguardando_pagamento}</strong>
                                    <small>Pedidos pendentes</small>
                                </article>
                                <article>
                                    <span>Estoque baixo</span>
                                    <strong>{dashboard.produtos_estoque_baixo}</strong>
                                    <small>Produtos com até 5 unidades</small>
                                </article>
                            </div>
                            <div className="grade-dashboard-admin">
                                <section>
                                    <header>
                                        <h2>Últimos pedidos</h2>
                                        <button onClick={() => setSecao('pedidos')}>
                                            Ver todos
                                        </button>
                                    </header>
                                    {dashboard.ultimos_pedidos.length ? (
                                        dashboard.ultimos_pedidos.map((pedido) => (
                                            <div key={pedido.id} className="linha-dashboard">
                                                <span>
                                                    <strong>Pedido #{pedido.id}</strong>
                                                    <small>
                                                        {pedido.usuario?.name ?? 'Cliente Lumora'}
                                                    </small>
                                                </span>
                                                <b>{rotulosStatus[pedido.status] ?? pedido.status}</b>
                                                <em>{moeda.format(Number(pedido.total))}</em>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="sem-dados-admin">Nenhum pedido registrado.</p>
                                    )}
                                </section>
                                <section>
                                    <header>
                                        <h2>Visão do catálogo</h2>
                                    </header>
                                    <dl className="resumo-catalogo-admin">
                                        <div>
                                            <dt>Produtos ativos</dt>
                                            <dd>{dashboard.produtos_ativos}</dd>
                                        </div>
                                        <div>
                                            <dt>Unidades em estoque</dt>
                                            <dd>{estoqueTotal}</dd>
                                        </div>
                                        <div>
                                            <dt>Pedidos pagos</dt>
                                            <dd>{dashboard.pedidos_pagos}</dd>
                                        </div>
                                        <div>
                                            <dt>Pedidos enviados</dt>
                                            <dd>{dashboard.pedidos_enviados}</dd>
                                        </div>
                                    </dl>
                                </section>
                            </div>
                        </>
                    ) : secao === 'produtos' ? (
                        <div className="gestao-produtos-admin">
                            <form className="form-produto-admin" onSubmit={salvarProduto}>
                                <header>
                                    <PackagePlus size={20} />
                                    <div>
                                        <h2>
                                            {produtoEditando ? 'Editar produto' : 'Novo produto'}
                                        </h2>
                                        <p>Dados comerciais e disponibilidade inicial.</p>
                                    </div>
                                </header>
                                <label>
                                    Nova categoria
                                    <span className="campo-rapido-admin">
                                        <input
                                            value={novaCategoria}
                                            placeholder="Ex.: Impressoras"
                                            onChange={(evento) => setNovaCategoria(evento.target.value)}
                                        />
                                        <button
                                            type="button"
                                            disabled={processando || !novaCategoria.trim()}
                                            onClick={() => void criarCategoriaRapida()}
                                        >
                                            Criar
                                        </button>
                                    </span>
                                </label>
                                <label>
                                    Nova marca
                                    <span className="campo-rapido-admin">
                                        <input
                                            value={novaMarca}
                                            placeholder="Ex.: ASUS"
                                            onChange={(evento) => setNovaMarca(evento.target.value)}
                                        />
                                        <button
                                            type="button"
                                            disabled={processando || !novaMarca.trim()}
                                            onClick={() => void criarMarcaRapida()}
                                        >
                                            Criar
                                        </button>
                                    </span>
                                </label>
                                <label>
                                    Categoria
                                    <select
                                        required
                                        value={formulario.category_id}
                                        onChange={(evento) =>
                                            setFormulario((atual) => ({
                                                ...atual,
                                                category_id: evento.target.value,
                                            }))
                                        }
                                    >
                                        <option value="">Selecione</option>
                                        {categorias.map((categoria) => (
                                            <option key={categoria.id} value={categoria.id}>
                                                {categoria.name}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                                <label>
                                    Nome
                                    <input
                                        required
                                        value={formulario.name}
                                        onChange={(evento) =>
                                            setFormulario((atual) => ({
                                                ...atual,
                                                name: evento.target.value,
                                            }))
                                        }
                                    />
                                </label>
                                <label>
                                    Preço
                                    <input
                                        required
                                        min="0.01"
                                        step="0.01"
                                        type="number"
                                        value={formulario.price}
                                        onChange={(evento) =>
                                            setFormulario((atual) => ({
                                                ...atual,
                                                price: evento.target.value,
                                            }))
                                        }
                                    />
                                </label>
                                <label>
                                    Marca
                                    <select
                                        value={formulario.brand}
                                        onChange={(evento) =>
                                            setFormulario((atual) => ({
                                                ...atual,
                                                brand: evento.target.value,
                                            }))
                                        }
                                    >
                                        <option value="">Selecione uma marca</option>
                                        {marcas
                                            .filter((marca) => marca.is_active)
                                            .map((marca) => (
                                                <option key={marca.id} value={marca.name}>
                                                    {marca.name}
                                                </option>
                                            ))}
                                    </select>
                                </label>
                                <label>
                                    Condição
                                    <select
                                        required
                                        value={formulario.condition}
                                        onChange={(evento) =>
                                            setFormulario((atual) => ({
                                                ...atual,
                                                condition: evento.target
                                                    .value as FormularioProduto['condition'],
                                            }))
                                        }
                                    >
                                        <option value="novo">Novo</option>
                                        <option value="usado">Usado</option>
                                        <option value="recondicionado">Recondicionado</option>
                                    </select>
                                </label>
                                <label>
                                    Estoque inicial
                                    <input
                                        required
                                        min="0"
                                        type="number"
                                        value={formulario.stock}
                                        onChange={(evento) =>
                                            setFormulario((atual) => ({
                                                ...atual,
                                                stock: evento.target.value,
                                            }))
                                        }
                                    />
                                </label>
                                <label className="campo-admin-largo">
                                    Imagem principal (URL)
                                    <input
                                        type="url"
                                        value={formulario.image_url}
                                        onChange={(evento) =>
                                            setFormulario((atual) => ({
                                                ...atual,
                                                image_url: evento.target.value,
                                            }))
                                        }
                                    />
                                </label>
                                <label className="campo-admin-largo">
                                    Galeria de imagens
                                    <textarea
                                        placeholder="Cole uma URL por linha"
                                        value={formulario.image_urls}
                                        onChange={(evento) =>
                                            setFormulario((atual) => ({
                                                ...atual,
                                                image_urls: evento.target.value,
                                            }))
                                        }
                                    />
                                </label>
                                <label className="campo-admin-largo">
                                    Descrição
                                    <textarea
                                        value={formulario.description}
                                        onChange={(evento) =>
                                            setFormulario((atual) => ({
                                                ...atual,
                                                description: evento.target.value,
                                            }))
                                        }
                                    />
                                </label>
                                <div className="acoes-form-admin campo-admin-largo">
                                    <button disabled={processando}>
                                        {produtoEditando ? 'Salvar alterações' : 'Criar produto'}
                                    </button>
                                    {produtoEditando ? (
                                        <button type="button" onClick={limparFormulario}>
                                            Cancelar edição
                                        </button>
                                    ) : null}
                                </div>
                            </form>

                            <section className="lista-produtos-admin">
                                <header>
                                    <h2>Produtos</h2>
                                    <span>
                                        {produtosFiltrados.length} de {produtos.length} cadastrados
                                    </span>
                                </header>
                                <div className="filtros-produtos-admin">
                                    <input
                                        value={filtrosProdutos.busca}
                                        placeholder="Buscar produto"
                                        onChange={(evento) =>
                                            setFiltrosProdutos((atual) => ({
                                                ...atual,
                                                busca: evento.target.value,
                                            }))
                                        }
                                    />
                                    <select
                                        value={filtrosProdutos.categoria}
                                        onChange={(evento) =>
                                            setFiltrosProdutos((atual) => ({
                                                ...atual,
                                                categoria: evento.target.value,
                                            }))
                                        }
                                    >
                                        <option value="">Categoria</option>
                                        {categorias.map((categoria) => (
                                            <option key={categoria.id} value={categoria.slug}>
                                                {categoria.name}
                                            </option>
                                        ))}
                                    </select>
                                    <select
                                        value={filtrosProdutos.marca}
                                        onChange={(evento) =>
                                            setFiltrosProdutos((atual) => ({
                                                ...atual,
                                                marca: evento.target.value,
                                            }))
                                        }
                                    >
                                        <option value="">Marca</option>
                                        {marcas.map((marca) => (
                                            <option key={marca.id} value={marca.name}>
                                                {marca.name}
                                            </option>
                                        ))}
                                    </select>
                                    <select
                                        value={filtrosProdutos.status}
                                        onChange={(evento) =>
                                            setFiltrosProdutos((atual) => ({
                                                ...atual,
                                                status: evento.target.value,
                                            }))
                                        }
                                    >
                                        <option value="">Status</option>
                                        <option value="ativo">Ativos</option>
                                        <option value="inativo">Inativos</option>
                                    </select>
                                    <select
                                        value={filtrosProdutos.estoque}
                                        onChange={(evento) =>
                                            setFiltrosProdutos((atual) => ({
                                                ...atual,
                                                estoque: evento.target.value,
                                            }))
                                        }
                                    >
                                        <option value="">Estoque</option>
                                        <option value="disponivel">Disponível</option>
                                        <option value="baixo">Baixo</option>
                                        <option value="zerado">Zerado</option>
                                    </select>
                                    <select
                                        value={filtrosProdutos.ordem}
                                        onChange={(evento) =>
                                            setFiltrosProdutos((atual) => ({
                                                ...atual,
                                                ordem: evento.target.value,
                                            }))
                                        }
                                    >
                                        <option value="nome">Nome</option>
                                        <option value="preco_asc">Menor preço</option>
                                        <option value="preco_desc">Maior preço</option>
                                        <option value="estoque_asc">Menor estoque</option>
                                        <option value="estoque_desc">Maior estoque</option>
                                    </select>
                                    <button
                                        type="button"
                                        onClick={() => setFiltrosProdutos(filtrosProdutosIniciais)}
                                    >
                                        Limpar
                                    </button>
                                </div>
                                {produtosFiltrados.map((produto) => (
                                    <article key={produto.id}>
                                        <div>
                                            <strong>{produto.name}</strong>
                                            <span>
                                                {produto.categoria.name}
                                                {produto.brand ? ` · ${produto.brand}` : ''}
                                            </span>
                                        </div>
                                        <b>{moeda.format(Number(produto.price))}</b>
                                        <span
                                            className={
                                                produto.stock <= 5
                                                    ? 'estoque-admin baixo'
                                                    : 'estoque-admin'
                                            }
                                        >
                                            {produto.stock} un.
                                        </span>
                                        <em>{produto.is_active ? 'Ativo' : 'Inativo'}</em>
                                        <div className="acoes-produto-admin">
                                            <button onClick={() => editarProduto(produto)}>
                                                Editar
                                            </button>
                                            <button onClick={() => setProdutoEstoque(produto)}>
                                                Estoque
                                            </button>
                                            <button
                                                disabled={processando}
                                                onClick={() => void alternarProduto(produto)}
                                            >
                                                {produto.is_active ? 'Desativar' : 'Ativar'}
                                            </button>
                                        </div>
                                    </article>
                                ))}
                            </section>
                        </div>
                    ) : (
                        <section className="pedidos-admin">
                            <header>
                                <h2>Pedidos</h2>
                                <span>{pedidos.length} registros</span>
                            </header>
                            {pedidos.length ? (
                                pedidos.map((pedido) => (
                                    <article key={pedido.id}>
                                        <div>
                                            <strong>Pedido #{pedido.id}</strong>
                                            <span>
                                                {pedido.usuario?.name ?? 'Cliente'} ·{' '}
                                                {pedido.usuario?.email}
                                            </span>
                                        </div>
                                        <b>{moeda.format(Number(pedido.total))}</b>
                                        <em className={`status-${pedido.status}`}>
                                            {rotulosStatus[pedido.status] ?? pedido.status}
                                        </em>
                                        <span>{pedido.itens.length} item(ns)</span>
                                        <select
                                            value=""
                                            disabled={
                                                processando ||
                                                !(proximosStatus[pedido.status]?.length > 0)
                                            }
                                            onChange={(evento) => {
                                                if (evento.target.value) {
                                                    void alterarStatus(
                                                        pedido,
                                                        evento.target.value,
                                                    );
                                                }
                                            }}
                                        >
                                            <option value="">Alterar status</option>
                                            {(proximosStatus[pedido.status] ?? []).map((opcao) => (
                                                <option key={opcao.valor} value={opcao.valor}>
                                                    {opcao.rotulo}
                                                </option>
                                            ))}
                                        </select>
                                    </article>
                                ))
                            ) : (
                                <p className="sem-dados-admin">Nenhum pedido registrado.</p>
                            )}
                        </section>
                    )}
                </section>
            </div>

            {produtoEstoque ? (
                <div className="sobreposicao">
                    <form className="modal-estoque-admin" onSubmit={ajustarEstoque}>
                        <Boxes size={25} />
                        <h2>Ajustar estoque</h2>
                        <p>
                            {produtoEstoque.name} possui <strong>{produtoEstoque.stock}</strong>{' '}
                            unidades.
                        </p>
                        <label>
                            Quantidade do ajuste
                            <input
                                required
                                type="number"
                                value={ajuste}
                                placeholder="Ex.: 5 ou -2"
                                onChange={(evento) => setAjuste(evento.target.value)}
                            />
                        </label>
                        <label>
                            Motivo
                            <input
                                required
                                minLength={5}
                                value={motivo}
                                onChange={(evento) => setMotivo(evento.target.value)}
                            />
                        </label>
                        <div>
                            <button disabled={processando}>Registrar ajuste</button>
                            <button type="button" onClick={() => setProdutoEstoque(null)}>
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            ) : null}
        </main>
    );
}

