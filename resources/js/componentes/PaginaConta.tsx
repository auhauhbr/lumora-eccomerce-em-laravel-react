import { useCallback, useEffect, useMemo, useState } from 'react';
import { ErroApi, requisitar } from '../servicos/api';
import type { Endereco, Pedido, Usuario } from '../tipos/catalogo';
import {
    ArrowLeft,
    Check,
    Clock3,
    CreditCard,
    LogOut,
    MapPin,
    PackageSearch,
    Trash2,
    Truck,
    UserRound,
    WalletCards,
} from './Icones';

const moeda = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const data = new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
});

const rotulosStatus: Record<string, string> = {
    pending_payment: 'Aguardando pagamento',
    paid: 'Pago',
    processing: 'Em preparação',
    shipped: 'Enviado',
    delivered: 'Entregue',
    cancelled: 'Cancelado',
    expired: 'Expirado',
};

type Aba = 'pedidos' | 'enderecos' | 'dados';

type Propriedades = {
    usuario: Usuario;
    aoVoltar: () => void;
    aoSair: () => void;
};

export function PaginaConta({ usuario, aoVoltar, aoSair }: Propriedades) {
    const [aba, setAba] = useState<Aba>('pedidos');
    const [pedidos, setPedidos] = useState<Pedido[]>([]);
    const [enderecos, setEnderecos] = useState<Endereco[]>([]);
    const [pedidoAberto, setPedidoAberto] = useState<number | null>(null);
    const [carregando, setCarregando] = useState(true);
    const [processando, setProcessando] = useState<number | null>(null);
    const [aviso, setAviso] = useState('');

    const carregar = useCallback(async () => {
        setCarregando(true);
        setAviso('');
        try {
            const [respostaPedidos, respostaEnderecos] = await Promise.all([
                requisitar<{ dados: Pedido[] }>('/api/orders'),
                requisitar<{ dados: Endereco[] }>('/api/addresses'),
            ]);
            setPedidos(respostaPedidos.dados);
            setEnderecos(respostaEnderecos.dados);
        } catch (falha) {
            setAviso(
                falha instanceof ErroApi
                    ? falha.message
                    : 'Não foi possível carregar os dados da conta.',
            );
        } finally {
            setCarregando(false);
        }
    }, []);

    useEffect(() => {
        void carregar();
    }, [carregar]);

    const totais = useMemo(
        () => ({
            pedidos: pedidos.length,
            pendentes: pedidos.filter((pedido) => pedido.status === 'pending_payment').length,
            concluidos: pedidos.filter((pedido) =>
                ['paid', 'processing', 'shipped', 'delivered'].includes(pedido.status),
            ).length,
        }),
        [pedidos],
    );

    async function pagar(pedido: Pedido) {
        setProcessando(pedido.id);
        setAviso('');
        try {
            const resposta = await requisitar<{ dados: { payment_url: string | null } }>(
                `/api/orders/${pedido.id}/payment`,
                { method: 'POST' },
            );
            if (resposta.dados.payment_url) {
                window.location.assign(resposta.dados.payment_url);
                return;
            }
            setAviso('O Mercado Pago não retornou o endereço do checkout.');
        } catch (falha) {
            setAviso(
                falha instanceof ErroApi
                    ? falha.message
                    : 'Não foi possível iniciar o pagamento.',
            );
        } finally {
            setProcessando(null);
        }
    }

    async function cancelar(pedido: Pedido) {
        setProcessando(pedido.id);
        setAviso('');
        try {
            const resposta = await requisitar<{ dados: Pedido }>(
                `/api/orders/${pedido.id}/cancel`,
                { method: 'POST' },
            );
            setPedidos((atuais) =>
                atuais.map((item) => (item.id === resposta.dados.id ? resposta.dados : item)),
            );
        } catch (falha) {
            setAviso(falha instanceof ErroApi ? falha.message : 'Não foi possível cancelar.');
        } finally {
            setProcessando(null);
        }
    }

    async function excluirEndereco(endereco: Endereco) {
        setProcessando(endereco.id);
        setAviso('');
        try {
            await requisitar(`/api/addresses/${endereco.id}`, { method: 'DELETE' });
            setEnderecos((atuais) => atuais.filter((item) => item.id !== endereco.id));
        } catch (falha) {
            setAviso(
                falha instanceof ErroApi
                    ? falha.message
                    : 'Não foi possível excluir o endereço.',
            );
        } finally {
            setProcessando(null);
        }
    }

    return (
        <main className="pagina-conta">
            <button className="voltar-conta" type="button" onClick={aoVoltar}>
                <ArrowLeft size={17} />
                Voltar para a loja
            </button>

            <header className="cabecalho-conta">
                <div>
                    <span>Minha conta</span>
                    <h1>Olá, {usuario.name}</h1>
                    <p>Acompanhe seus pedidos, pagamentos e endereços.</p>
                </div>
                <button type="button" onClick={aoSair}>
                    <LogOut size={16} />
                    Sair
                </button>
            </header>

            <section className="indicadores-conta">
                <div>
                    <WalletCards size={20} />
                    <span>Pedidos</span>
                    <strong>{totais.pedidos}</strong>
                </div>
                <div>
                    <Clock3 size={20} />
                    <span>Aguardando pagamento</span>
                    <strong>{totais.pendentes}</strong>
                </div>
                <div>
                    <Truck size={20} />
                    <span>Em andamento</span>
                    <strong>{totais.concluidos}</strong>
                </div>
            </section>

            <div className="estrutura-conta">
                <nav className="menu-conta">
                    <button
                        className={aba === 'pedidos' ? 'ativo' : ''}
                        type="button"
                        onClick={() => setAba('pedidos')}
                    >
                        <WalletCards size={17} />
                        Meus pedidos
                    </button>
                    <button
                        className={aba === 'enderecos' ? 'ativo' : ''}
                        type="button"
                        onClick={() => setAba('enderecos')}
                    >
                        <MapPin size={17} />
                        Endereços
                    </button>
                    <button
                        className={aba === 'dados' ? 'ativo' : ''}
                        type="button"
                        onClick={() => setAba('dados')}
                    >
                        <UserRound size={17} />
                        Meus dados
                    </button>
                </nav>

                <section className="conteudo-conta">
                    {aviso ? <div className="aviso-conta">{aviso}</div> : null}
                    {carregando ? (
                        <div className="vazio-conta">Carregando sua conta...</div>
                    ) : aba === 'pedidos' ? (
                        pedidos.length ? (
                            <div className="lista-pedidos-conta">
                                {pedidos.map((pedido) => {
                                    const aberto = pedidoAberto === pedido.id;
                                    const pendente = pedido.status === 'pending_payment';
                                    return (
                                        <article key={pedido.id} className="pedido-conta">
                                            <header>
                                                <div>
                                                    <span>Pedido #{pedido.id}</span>
                                                    <small>
                                                        {pedido.created_at
                                                            ? data.format(
                                                                  new Date(pedido.created_at),
                                                              )
                                                            : 'Pedido recente'}
                                                    </small>
                                                </div>
                                                <strong
                                                    className={`status-pedido status-${pedido.status}`}
                                                >
                                                    {rotulosStatus[pedido.status] ?? pedido.status}
                                                </strong>
                                                <b>{moeda.format(Number(pedido.total))}</b>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setPedidoAberto(aberto ? null : pedido.id)
                                                    }
                                                >
                                                    {aberto ? 'Ocultar detalhes' : 'Ver detalhes'}
                                                </button>
                                            </header>
                                            {aberto ? (
                                                <div className="detalhe-pedido-conta">
                                                    <div className="linha-status-pedido">
                                                        {[
                                                            ['pending_payment', 'Pagamento'],
                                                            ['paid', 'Pago'],
                                                            ['processing', 'Preparação'],
                                                            ['shipped', 'Envio'],
                                                            ['delivered', 'Entrega'],
                                                        ].map(([status, titulo]) => (
                                                            <div
                                                                key={status}
                                                                className={
                                                                    status === pedido.status ||
                                                                    [
                                                                        'paid',
                                                                        'processing',
                                                                        'shipped',
                                                                        'delivered',
                                                                    ].indexOf(pedido.status) >=
                                                                        [
                                                                            'paid',
                                                                            'processing',
                                                                            'shipped',
                                                                            'delivered',
                                                                        ].indexOf(status)
                                                                        ? 'concluido'
                                                                        : ''
                                                                }
                                                            >
                                                                <span>
                                                                    <Check size={12} />
                                                                </span>
                                                                <small>{titulo}</small>
                                                            </div>
                                                        ))}
                                                    </div>
                                                    <div className="itens-pedido-conta">
                                                        {pedido.itens.map((item) => (
                                                            <div key={item.id}>
                                                                <span>
                                                                    <strong>
                                                                        {item.product_name}
                                                                    </strong>
                                                                    <small>
                                                                        {item.quantity} ×{' '}
                                                                        {moeda.format(
                                                                            Number(item.unit_price),
                                                                        )}
                                                                    </small>
                                                                </span>
                                                                <b>
                                                                    {moeda.format(
                                                                        Number(item.total),
                                                                    )}
                                                                </b>
                                                            </div>
                                                        ))}
                                                    </div>
                                                    <div className="entrega-pedido-conta">
                                                        <MapPin size={17} />
                                                        <span>
                                                            <strong>Endereço de entrega</strong>
                                                            {pedido.endereco.street},{' '}
                                                            {pedido.endereco.number} ·{' '}
                                                            {pedido.endereco.city}/
                                                            {pedido.endereco.state}
                                                        </span>
                                                    </div>
                                                    {pendente ? (
                                                        <footer>
                                                            <button
                                                                className="pagar-pedido"
                                                                type="button"
                                                                disabled={processando === pedido.id}
                                                                onClick={() => void pagar(pedido)}
                                                            >
                                                                <CreditCard size={16} />
                                                                Pagar agora
                                                            </button>
                                                            <button
                                                                className="cancelar-pedido"
                                                                type="button"
                                                                disabled={processando === pedido.id}
                                                                onClick={() => void cancelar(pedido)}
                                                            >
                                                                Cancelar pedido
                                                            </button>
                                                        </footer>
                                                    ) : null}
                                                </div>
                                            ) : null}
                                        </article>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="vazio-conta">
                                <PackageSearch size={38} />
                                <h2>Você ainda não tem pedidos</h2>
                                <p>Quando concluir uma compra, ela aparecerá aqui.</p>
                            </div>
                        )
                    ) : aba === 'enderecos' ? (
                        enderecos.length ? (
                            <div className="enderecos-conta">
                                {enderecos.map((endereco) => (
                                    <article key={endereco.id}>
                                        <MapPin size={19} />
                                        <div>
                                            <strong>
                                                {endereco.street}, {endereco.number}
                                            </strong>
                                            <span>
                                                {endereco.neighborhood} · {endereco.city}/
                                                {endereco.state}
                                            </span>
                                            <small>CEP {endereco.zip_code}</small>
                                        </div>
                                        <button
                                            type="button"
                                            aria-label={`Excluir endereço ${endereco.street}`}
                                            disabled={processando === endereco.id}
                                            onClick={() => void excluirEndereco(endereco)}
                                        >
                                            <Trash2 size={16} />
                                        </button>
                                    </article>
                                ))}
                                <p className="nota-enderecos">
                                    Novos endereços podem ser cadastrados durante o checkout.
                                </p>
                            </div>
                        ) : (
                            <div className="vazio-conta">
                                <MapPin size={38} />
                                <h2>Nenhum endereço salvo</h2>
                                <p>Você poderá cadastrar um endereço ao finalizar uma compra.</p>
                            </div>
                        )
                    ) : (
                        <div className="dados-conta">
                            <div>
                                <span>Nome</span>
                                <strong>{usuario.name}</strong>
                            </div>
                            <div>
                                <span>E-mail</span>
                                <strong>{usuario.email}</strong>
                            </div>
                            <div>
                                <span>Tipo da conta</span>
                                <strong>
                                    {usuario.role === 'admin' ? 'Administrador' : 'Cliente'}
                                </strong>
                            </div>
                            <p>
                                Alteração de senha e dados pessoais será adicionada em uma etapa
                                futura.
                            </p>
                        </div>
                    )}
                </section>
            </div>
        </main>
    );
}
