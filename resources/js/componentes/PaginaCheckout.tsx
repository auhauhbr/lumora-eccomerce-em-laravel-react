import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { ErroApi, requisitar } from '../servicos/api';
import type { Carrinho, Endereco, Pedido } from '../tipos/catalogo';
import {
    ArrowLeft,
    Check,
    CreditCard,
    MapPin,
    ShieldCheck,
    Truck,
} from './Icones';

const moeda = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

type Etapa = 'endereco' | 'revisao' | 'pagamento';

type FormularioEndereco = {
    zip_code: string;
    street: string;
    number: string;
    complement: string;
    neighborhood: string;
    city: string;
    state: string;
};

type Propriedades = {
    carrinho: Carrinho;
    subtotal: string;
    aoVoltar: () => void;
    aoPedidoCriado: (pedido: Pedido) => void;
};

const formularioInicial: FormularioEndereco = {
    zip_code: '',
    street: '',
    number: '',
    complement: '',
    neighborhood: '',
    city: '',
    state: '',
};

export function PaginaCheckout({
    carrinho,
    subtotal,
    aoVoltar,
    aoPedidoCriado,
}: Propriedades) {
    const [etapa, setEtapa] = useState<Etapa>('endereco');
    const [enderecos, setEnderecos] = useState<Endereco[]>([]);
    const [enderecoSelecionado, setEnderecoSelecionado] = useState<number | null>(null);
    const [formulario, setFormulario] = useState(formularioInicial);
    const [mostrarFormulario, setMostrarFormulario] = useState(false);
    const [consultandoCep, setConsultandoCep] = useState(false);
    const [salvandoEndereco, setSalvandoEndereco] = useState(false);
    const [processandoPedido, setProcessandoPedido] = useState(false);
    const [erro, setErro] = useState('');
    const [pedido, setPedido] = useState<Pedido | null>(null);
    const [erroPagamento, setErroPagamento] = useState('');

    const enderecoAtual = useMemo(
        () => enderecos.find((endereco) => endereco.id === enderecoSelecionado) ?? null,
        [enderecos, enderecoSelecionado],
    );

    useEffect(() => {
        requisitar<{ dados: Endereco[] }>('/api/addresses')
            .then((resposta) => {
                setEnderecos(resposta.dados);
                if (resposta.dados[0]) setEnderecoSelecionado(resposta.dados[0].id);
                else setMostrarFormulario(true);
            })
            .catch((falha) =>
                setErro(
                    falha instanceof ErroApi
                        ? falha.message
                        : 'Não foi possível carregar seus endereços.',
                ),
            );
    }, []);

    function alterarFormulario(campo: keyof FormularioEndereco, valor: string) {
        setFormulario((atual) => ({ ...atual, [campo]: valor }));
    }

    async function consultarCep() {
        const cep = formulario.zip_code.replace(/\D/g, '');
        if (cep.length !== 8) {
            setErro('Digite um CEP com 8 números.');
            return;
        }

        setConsultandoCep(true);
        setErro('');
        try {
            const resposta = await requisitar<{ dados: Omit<Endereco, 'id' | 'number'> }>(
                `/api/addresses/zipcode/${cep}`,
            );
            setFormulario((atual) => ({
                ...atual,
                zip_code: resposta.dados.zip_code,
                street: resposta.dados.street,
                complement: resposta.dados.complement ?? atual.complement,
                neighborhood: resposta.dados.neighborhood,
                city: resposta.dados.city,
                state: resposta.dados.state,
            }));
        } catch (falha) {
            setErro(falha instanceof ErroApi ? falha.message : 'Não foi possível consultar o CEP.');
        } finally {
            setConsultandoCep(false);
        }
    }

    async function salvarEndereco(evento: FormEvent) {
        evento.preventDefault();
        setSalvandoEndereco(true);
        setErro('');
        try {
            const resposta = await requisitar<{ dados: Endereco }>('/api/addresses', {
                method: 'POST',
                body: JSON.stringify(formulario),
            });
            setEnderecos((atuais) => [resposta.dados, ...atuais]);
            setEnderecoSelecionado(resposta.dados.id);
            setFormulario(formularioInicial);
            setMostrarFormulario(false);
        } catch (falha) {
            setErro(falha instanceof ErroApi ? falha.message : 'Não foi possível salvar o endereço.');
        } finally {
            setSalvandoEndereco(false);
        }
    }

    async function criarPedido() {
        if (!enderecoSelecionado) return;
        setProcessandoPedido(true);
        setErro('');
        setErroPagamento('');

        try {
            const resposta = await requisitar<{ dados: Pedido }>('/api/orders', {
                method: 'POST',
                body: JSON.stringify({ address_id: enderecoSelecionado }),
            });
            setPedido(resposta.dados);
            setEtapa('pagamento');
            aoPedidoCriado(resposta.dados);
            await iniciarPagamento(resposta.dados);
        } catch (falha) {
            setErro(falha instanceof ErroApi ? falha.message : 'Não foi possível criar o pedido.');
        } finally {
            setProcessandoPedido(false);
        }
    }

    async function iniciarPagamento(pedidoAtual = pedido) {
        if (!pedidoAtual) return;
        setErroPagamento('');
        try {
            const resposta = await requisitar<{
                dados: { payment_url: string | null };
            }>(`/api/orders/${pedidoAtual.id}/payment`, { method: 'POST' });

            if (resposta.dados.payment_url) {
                window.location.assign(resposta.dados.payment_url);
                return;
            }
            setErroPagamento('O checkout não retornou um endereço de pagamento.');
        } catch (falha) {
            setErroPagamento(
                falha instanceof ErroApi
                    ? falha.message
                    : 'Não foi possível abrir o Mercado Pago agora.',
            );
        }
    }

    return (
        <main className="pagina-checkout">
            <button className="voltar-checkout" type="button" onClick={aoVoltar}>
                <ArrowLeft size={17} />
                Voltar para a loja
            </button>

            <header className="cabecalho-checkout">
                <h1>Finalizar compra</h1>
                <p>Confirme o endereço e revise os itens antes de criar o pedido.</p>
            </header>

            <ol className="etapas-checkout">
                {(['endereco', 'revisao', 'pagamento'] as Etapa[]).map((item, indice) => (
                    <li
                        key={item}
                        className={
                            etapa === item ||
                            (etapa === 'revisao' && item === 'endereco') ||
                            (etapa === 'pagamento' && item !== 'pagamento')
                                ? 'etapa-ativa'
                                : ''
                        }
                    >
                        <span>{indice + 1}</span>
                        {item === 'endereco'
                            ? 'Endereço'
                            : item === 'revisao'
                              ? 'Revisão'
                              : 'Pagamento'}
                    </li>
                ))}
            </ol>

            <div className="estrutura-checkout">
                <div className="conteudo-checkout">
                    {etapa === 'endereco' ? (
                        <section className="bloco-checkout">
                            <div className="titulo-bloco-checkout">
                                <MapPin size={21} />
                                <div>
                                    <h2>Endereço de entrega</h2>
                                    <p>Escolha um endereço salvo ou cadastre um novo.</p>
                                </div>
                            </div>

                            {enderecos.length > 0 ? (
                                <div className="lista-enderecos">
                                    {enderecos.map((endereco) => (
                                        <label
                                            key={endereco.id}
                                            className={
                                                enderecoSelecionado === endereco.id
                                                    ? 'endereco-selecionado'
                                                    : ''
                                            }
                                        >
                                            <input
                                                type="radio"
                                                name="endereco"
                                                checked={enderecoSelecionado === endereco.id}
                                                onChange={() =>
                                                    setEnderecoSelecionado(endereco.id)
                                                }
                                            />
                                            <span>
                                                <strong>
                                                    {endereco.street}, {endereco.number}
                                                </strong>
                                                <small>
                                                    {endereco.neighborhood} · {endereco.city}/
                                                    {endereco.state} · CEP {endereco.zip_code}
                                                </small>
                                                {endereco.complement ? (
                                                    <small>{endereco.complement}</small>
                                                ) : null}
                                            </span>
                                            {enderecoSelecionado === endereco.id ? (
                                                <Check size={18} />
                                            ) : null}
                                        </label>
                                    ))}
                                </div>
                            ) : null}

                            <button
                                className="alternar-endereco"
                                type="button"
                                onClick={() => setMostrarFormulario((atual) => !atual)}
                            >
                                {mostrarFormulario ? 'Cancelar novo endereço' : 'Adicionar endereço'}
                            </button>

                            {mostrarFormulario ? (
                                <form className="formulario-endereco" onSubmit={salvarEndereco}>
                                    <label className="campo-cep">
                                        CEP
                                        <span>
                                            <input
                                                required
                                                inputMode="numeric"
                                                value={formulario.zip_code}
                                                onChange={(evento) =>
                                                    alterarFormulario(
                                                        'zip_code',
                                                        evento.target.value,
                                                    )
                                                }
                                            />
                                            <button
                                                type="button"
                                                disabled={consultandoCep}
                                                onClick={consultarCep}
                                            >
                                                {consultandoCep ? 'Consultando...' : 'Buscar CEP'}
                                            </button>
                                        </span>
                                    </label>
                                    <label className="campo-largo">
                                        Rua
                                        <input
                                            required
                                            value={formulario.street}
                                            onChange={(evento) =>
                                                alterarFormulario('street', evento.target.value)
                                            }
                                        />
                                    </label>
                                    <label>
                                        Número
                                        <input
                                            required
                                            value={formulario.number}
                                            onChange={(evento) =>
                                                alterarFormulario('number', evento.target.value)
                                            }
                                        />
                                    </label>
                                    <label>
                                        Complemento
                                        <input
                                            value={formulario.complement}
                                            onChange={(evento) =>
                                                alterarFormulario(
                                                    'complement',
                                                    evento.target.value,
                                                )
                                            }
                                        />
                                    </label>
                                    <label>
                                        Bairro
                                        <input
                                            required
                                            value={formulario.neighborhood}
                                            onChange={(evento) =>
                                                alterarFormulario(
                                                    'neighborhood',
                                                    evento.target.value,
                                                )
                                            }
                                        />
                                    </label>
                                    <label>
                                        Cidade
                                        <input
                                            required
                                            value={formulario.city}
                                            onChange={(evento) =>
                                                alterarFormulario('city', evento.target.value)
                                            }
                                        />
                                    </label>
                                    <label>
                                        Estado
                                        <input
                                            required
                                            maxLength={2}
                                            value={formulario.state}
                                            onChange={(evento) =>
                                                alterarFormulario('state', evento.target.value)
                                            }
                                        />
                                    </label>
                                    <button className="salvar-endereco" disabled={salvandoEndereco}>
                                        {salvandoEndereco ? 'Salvando...' : 'Salvar endereço'}
                                    </button>
                                </form>
                            ) : null}

                            {erro ? <div className="erro-checkout">{erro}</div> : null}

                            <button
                                className="avancar-checkout"
                                type="button"
                                disabled={!enderecoSelecionado}
                                onClick={() => setEtapa('revisao')}
                            >
                                Revisar pedido
                            </button>
                        </section>
                    ) : etapa === 'revisao' ? (
                        <section className="bloco-checkout">
                            <div className="titulo-bloco-checkout">
                                <Check size={21} />
                                <div>
                                    <h2>Revise antes de confirmar</h2>
                                    <p>Após criar o pedido, o carrinho será esvaziado.</p>
                                </div>
                            </div>

                            {enderecoAtual ? (
                                <div className="endereco-revisao">
                                    <strong>Entrega</strong>
                                    <span>
                                        {enderecoAtual.street}, {enderecoAtual.number}
                                        {enderecoAtual.complement
                                            ? ` · ${enderecoAtual.complement}`
                                            : ''}
                                    </span>
                                    <span>
                                        {enderecoAtual.neighborhood} · {enderecoAtual.city}/
                                        {enderecoAtual.state}
                                    </span>
                                    <button type="button" onClick={() => setEtapa('endereco')}>
                                        Alterar endereço
                                    </button>
                                </div>
                            ) : null}

                            <div className="itens-revisao">
                                {carrinho.itens.map((item) => (
                                    <article key={item.id}>
                                        <img
                                            src={
                                                item.produto.image_url ??
                                                `https://picsum.photos/seed/${item.produto.slug}/160/160`
                                            }
                                            alt=""
                                        />
                                        <div>
                                            <strong>{item.produto.name}</strong>
                                            <span>
                                                {item.quantity}{' '}
                                                {item.quantity === 1 ? 'unidade' : 'unidades'}
                                            </span>
                                        </div>
                                        <b>
                                            {moeda.format(
                                                Number(item.unit_price) * item.quantity,
                                            )}
                                        </b>
                                    </article>
                                ))}
                            </div>

                            <div className="aviso-criacao-pedido">
                                O pedido ficará salvo como aguardando pagamento. Se o Mercado Pago
                                ainda estiver indisponível, você poderá tentar novamente depois.
                            </div>
                            {erro ? <div className="erro-checkout">{erro}</div> : null}
                            <button
                                className="avancar-checkout"
                                type="button"
                                disabled={processandoPedido}
                                onClick={criarPedido}
                            >
                                {processandoPedido
                                    ? 'Criando pedido...'
                                    : 'Criar pedido e continuar'}
                            </button>
                        </section>
                    ) : (
                        <section className="bloco-checkout pagamento-checkout">
                            <CreditCard size={36} />
                            <h2>Pedido #{pedido?.id} criado</h2>
                            <p>
                                Seu pedido está salvo e aguarda o pagamento pelo Mercado Pago.
                            </p>
                            {erroPagamento ? (
                                <div className="aviso-pagamento-pendente">
                                    <strong>Pagamento ainda não iniciado</strong>
                                    <span>{erroPagamento}</span>
                                    <span>
                                        Nenhum valor foi cobrado. Você pode tentar novamente quando
                                        a conta do Mercado Pago estiver liberada.
                                    </span>
                                </div>
                            ) : (
                                <div className="processando-pagamento">
                                    Preparando o checkout seguro...
                                </div>
                            )}
                            <button
                                className="avancar-checkout"
                                type="button"
                                disabled={!pedido}
                                onClick={() => void iniciarPagamento()}
                            >
                                Tentar pagamento
                            </button>
                            <button className="voltar-loja-checkout" type="button" onClick={aoVoltar}>
                                Voltar para a loja
                            </button>
                        </section>
                    )}
                </div>

                <aside className="resumo-checkout">
                    <h2>Resumo do pedido</h2>
                    <div className="mini-itens-checkout">
                        {carrinho.itens.map((item) => (
                            <div key={item.id}>
                                <img
                                    src={
                                        item.produto.image_url ??
                                        `https://picsum.photos/seed/${item.produto.slug}/120/120`
                                    }
                                    alt=""
                                />
                                <span>
                                    <strong>{item.produto.name}</strong>
                                    <small>Quantidade: {item.quantity}</small>
                                </span>
                            </div>
                        ))}
                    </div>
                    <dl>
                        <div>
                            <dt>Subtotal</dt>
                            <dd>{moeda.format(Number(subtotal))}</dd>
                        </div>
                        <div>
                            <dt>Frete</dt>
                            <dd className="frete-gratis">Grátis</dd>
                        </div>
                        <div>
                            <dt>Total</dt>
                            <dd>{moeda.format(Number(subtotal))}</dd>
                        </div>
                    </dl>
                    <div className="metodos-checkout">
                        <Truck size={17} />
                        Entrega confirmada após informar o endereço
                    </div>
                    <div className="metodos-checkout">
                        <CreditCard size={17} />
                        Pix ou cartão pelo Mercado Pago
                    </div>
                    <div className="seguranca-checkout">
                        <ShieldCheck size={20} />
                        Seus dados de pagamento não passam pela Lumora.
                    </div>
                </aside>
            </div>
        </main>
    );
}
