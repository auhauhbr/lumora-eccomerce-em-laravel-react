import type { Usuario } from '../tipos/catalogo';
import { LogIn, LogOut, Menu, ShoppingBag, UserRound } from './Icones';

type Propriedades = {
    usuario: Usuario | null;
    quantidadeCarrinho: number;
    aoAbrirCarrinho: () => void;
    aoAbrirLogin: () => void;
    aoAbrirConta: () => void;
    aoSair: () => void;
    aoAlternarFiltros: () => void;
};

export function Cabecalho({
    usuario,
    quantidadeCarrinho,
    aoAbrirCarrinho,
    aoAbrirLogin,
    aoAbrirConta,
    aoSair,
    aoAlternarFiltros,
}: Propriedades) {
    return (
        <header className="cabecalho">
            <div className="marca">
                <button
                    className="botao-menu"
                    type="button"
                    aria-label="Abrir filtros"
                    onClick={aoAlternarFiltros}
                >
                    <Menu size={19} />
                </button>
                <span className="marca-simbolo">
                    <img src="/imagens/marca/lumora-logo.png" alt="" />
                </span>
                <span className="marca-nome">Lumora</span>
            </div>

            <div className="cabecalho-acoes">
                {usuario ? (
                    <button className="usuario-logado" type="button" onClick={aoAbrirConta}>
                        <UserRound size={16} />
                        <span>
                            <small>Bem-vindo</small>
                            {usuario.name}
                        </span>
                    </button>
                ) : (
                    <button className="botao-entrar" type="button" onClick={aoAbrirLogin}>
                        <LogIn size={16} />
                        Entrar
                    </button>
                )}

                {usuario ? (
                    <button className="botao-sair" type="button" onClick={aoSair}>
                        <LogOut size={15} />
                        Sair
                    </button>
                ) : null}

                <button className="botao-carrinho" type="button" onClick={aoAbrirCarrinho}>
                    <ShoppingBag size={16} />
                    Carrinho
                    <span>{quantidadeCarrinho}</span>
                </button>
            </div>
        </header>
    );
}
