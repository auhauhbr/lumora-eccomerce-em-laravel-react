import { useEffect, useState } from 'react';
import { ErroApi, requisitar } from '../servicos/api';
import {
    CircleAlert,
    CircleCheck,
    Clock3,
    CreditCard,
    ShieldCheck,
} from './Icones';

type TipoRetorno = 'sucesso' | 'pendente' | 'falha';

type Propriedades = {
    tipo: TipoRetorno;
    pedidoId: number | null;
    autenticado: boolean;
    aoAbrirConta: () => void;
    aoVoltar: () => void;
    aoEntrar: () => void;
};

const conteudos = {
    sucesso: {
        titulo: 'Pagamento recebido',
        texto: 'Estamos confirmando os dados finais do seu pedido.',
        Icone: CircleCheck,
    },
    pendente: {
        titulo: 'Pagamento em análise',
        texto: 'O Mercado Pago ainda está processando o pagamento.',
        Icone: Clock3,
    },
    falha: {
        titulo: 'Pagamento não concluído',
        texto: 'Nenhum valor foi confirmado. Você pode tentar novamente.',
        Icone: CircleAlert,
    },
};

export function PaginaRetornoPagamento({
    tipo,
    pedidoId,
    autenticado,
    aoAbrirConta,
    aoVoltar,
    aoEntrar,
}: Propriedades) {
    const [status, setStatus] = useState<string | null>(null);
    const [erro, setErro] = useState('');
    const { titulo, texto, Icone } = conteudos[tipo];

    useEffect(() => {
        if (!autenticado || !pedidoId) return;
        requisitar<{ dados: { payment_status: string; order_status: string } }>(
            `/api/orders/${pedidoId}/payment-status`,
        )
            .then((resposta) => setStatus(resposta.dados.payment_status))
            .catch((falha) =>
                setErro(
                    falha instanceof ErroApi
                        ? falha.message
                        : 'Não foi possível consultar o pagamento.',
                ),
            );
    }, [autenticado, pedidoId]);

    return (
        <main className={`pagina-retorno pagina-retorno--${tipo}`}>
            <section>
                <Icone size={52} />
                <span>Pedido {pedidoId ? `#${pedidoId}` : 'Lumora'}</span>
                <h1>{titulo}</h1>
                <p>{texto}</p>
                {status ? (
                    <div className="status-retorno">
                        <CreditCard size={18} />
                        Situação atual: <strong>{status}</strong>
                    </div>
                ) : null}
                {erro ? <div className="erro-retorno">{erro}</div> : null}
                <div className="seguranca-retorno">
                    <ShieldCheck size={19} />
                    A confirmação definitiva acontece pelo webhook seguro do Mercado Pago.
                </div>
                <div className="acoes-retorno">
                    {autenticado ? (
                        <button type="button" onClick={aoAbrirConta}>
                            Ver meus pedidos
                        </button>
                    ) : (
                        <button type="button" onClick={aoEntrar}>
                            Entrar para consultar
                        </button>
                    )}
                    <button type="button" onClick={aoVoltar}>
                        Voltar para a loja
                    </button>
                </div>
            </section>
        </main>
    );
}
