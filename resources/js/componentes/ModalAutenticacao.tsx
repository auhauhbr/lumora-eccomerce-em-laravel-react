import { useState, type FormEvent } from 'react';
import { ErroApi, requisitar, salvarToken } from '../servicos/api';
import type { Usuario } from '../tipos/catalogo';
import { Check, X } from './Icones';

type RespostaAutenticacao = {
    token: string;
    usuario: Usuario;
};

type Propriedades = {
    aberto: boolean;
    aoFechar: () => void;
    aoAutenticar: (usuario: Usuario) => void;
};

export function ModalAutenticacao({
    aberto,
    aoFechar,
    aoAutenticar,
}: Propriedades) {
    const [modo, setModo] = useState<'entrar' | 'registrar'>('entrar');
    const [nome, setNome] = useState('');
    const [email, setEmail] = useState('');
    const [senha, setSenha] = useState('');
    const [erro, setErro] = useState('');
    const [enviando, setEnviando] = useState(false);

    if (!aberto) return null;

    async function enviar(evento: FormEvent) {
        evento.preventDefault();
        setEnviando(true);
        setErro('');

        try {
            const corpo =
                modo === 'entrar'
                    ? { email, password: senha, device_name: 'navegador-lumora' }
                    : {
                          name: nome,
                          email,
                          password: senha,
                          password_confirmation: senha,
                      };
            const resposta = await requisitar<RespostaAutenticacao>(
                modo === 'entrar' ? '/api/login' : '/api/register',
                { method: 'POST', body: JSON.stringify(corpo) },
            );
            salvarToken(resposta.token);
            aoAutenticar(resposta.usuario);
            aoFechar();
        } catch (falha) {
            setErro(
                falha instanceof ErroApi
                    ? falha.message
                    : 'Não foi possível acessar sua conta.',
            );
        } finally {
            setEnviando(false);
        }
    }

    return (
        <div className="sobreposicao">
            <section className="modal-autenticacao" role="dialog" aria-modal="true">
                <button type="button" className="fechar-modal" onClick={aoFechar}>
                    <X size={19} />
                </button>
                <div className="autenticacao-marca">
                    <span>L</span>
                    <div>
                        <b>Lumora</b>
                        <small>Comércio digital confiável</small>
                    </div>
                </div>
                <h2>{modo === 'entrar' ? 'Acesse sua conta' : 'Crie sua conta'}</h2>
                <p>
                    {modo === 'entrar'
                        ? 'Continue sua compra com carrinho e estoque sincronizados.'
                        : 'Cadastre-se para comprar e acompanhar seus pedidos.'}
                </p>
                <form onSubmit={enviar}>
                    {modo === 'registrar' ? (
                        <label>
                            Nome
                            <input
                                required
                                value={nome}
                                onChange={(evento) => setNome(evento.target.value)}
                            />
                        </label>
                    ) : null}
                    <label>
                        E-mail
                        <input
                            required
                            type="email"
                            value={email}
                            onChange={(evento) => setEmail(evento.target.value)}
                        />
                    </label>
                    <label>
                        Senha
                        <input
                            required
                            minLength={8}
                            type="password"
                            value={senha}
                            onChange={(evento) => setSenha(evento.target.value)}
                        />
                    </label>
                    {erro ? <div className="erro-formulario">{erro}</div> : null}
                    <button className="enviar-autenticacao" disabled={enviando}>
                        {enviando
                            ? 'Aguarde...'
                            : modo === 'entrar'
                              ? 'Entrar'
                              : 'Criar conta'}
                    </button>
                </form>
                <div className="credencial-demonstracao">
                    <Check size={15} />
                    A conta de demonstração já está preenchida.
                </div>
                <button
                    className="alternar-autenticacao"
                    type="button"
                    onClick={() => {
                        setErro('');
                        setModo(modo === 'entrar' ? 'registrar' : 'entrar');
                    }}
                >
                    {modo === 'entrar'
                        ? 'Ainda não tem conta? Cadastre-se'
                        : 'Já possui conta? Entrar'}
                </button>
            </section>
        </div>
    );
}
