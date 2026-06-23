import type { Categoria } from '../tipos/catalogo';
import { ChevronDown, X } from './Icones';

export type Filtros = {
    categoria: string;
    marca: string;
    condicao: string;
    minimo: string;
    maximo: string;
    ordenacao: string;
    emEstoque: boolean;
};

type Propriedades = {
    categorias: Categoria[];
    marcas: string[];
    filtros: Filtros;
    aberto: boolean;
    aoAlterar: (filtros: Filtros) => void;
    aoFechar: () => void;
};

export function FiltrosCatalogo({
    categorias,
    marcas,
    filtros,
    aberto,
    aoAlterar,
    aoFechar,
}: Propriedades) {
    const alterar = <C extends keyof Filtros>(campo: C, valor: Filtros[C]) =>
        aoAlterar({ ...filtros, [campo]: valor });

    return (
        <aside className={`filtros ${aberto ? 'filtros--abertos' : ''}`}>
            <div className="filtros-titulo">
                <span>Filtros</span>
                <button type="button" aria-label="Fechar filtros" onClick={aoFechar}>
                    <X size={18} />
                </button>
            </div>

            <label className="campo-filtro">
                <span>Categoria</span>
                <span className="select-filtro">
                    <select
                        value={filtros.categoria}
                        onChange={(evento) => alterar('categoria', evento.target.value)}
                    >
                        <option value="">Todas as categorias</option>
                        {categorias.map((categoria) => (
                            <option key={categoria.id} value={categoria.slug}>
                                {categoria.name}
                            </option>
                        ))}
                    </select>
                    <ChevronDown size={15} />
                </span>
            </label>

            <label className="campo-filtro">
                <span>Marca</span>
                <span className="select-filtro">
                    <select
                        value={filtros.marca}
                        onChange={(evento) => alterar('marca', evento.target.value)}
                    >
                        <option value="">Todas as marcas</option>
                        {marcas.map((marca) => (
                            <option key={marca} value={marca}>
                                {marca}
                            </option>
                        ))}
                    </select>
                    <ChevronDown size={15} />
                </span>
            </label>

            <label className="campo-filtro">
                <span>Condição</span>
                <span className="select-filtro">
                    <select
                        value={filtros.condicao}
                        onChange={(evento) => alterar('condicao', evento.target.value)}
                    >
                        <option value="">Todas</option>
                        <option value="novo">Novo</option>
                        <option value="usado">Usado</option>
                        <option value="recondicionado">Recondicionado</option>
                    </select>
                    <ChevronDown size={15} />
                </span>
            </label>

            <div className="filtros-divisor" />

            <label className="campo-filtro">
                <span>Preço mínimo</span>
                <span className="campo-moeda">
                    <b>R$</b>
                    <input
                        min="0"
                        type="number"
                        value={filtros.minimo}
                        placeholder="0"
                        onChange={(evento) => alterar('minimo', evento.target.value)}
                    />
                </span>
            </label>

            <label className="campo-filtro">
                <span>Preço máximo</span>
                <span className="campo-moeda">
                    <b>R$</b>
                    <input
                        min="0"
                        type="number"
                        value={filtros.maximo}
                        placeholder="5000"
                        onChange={(evento) => alterar('maximo', evento.target.value)}
                    />
                </span>
            </label>

            <label className="campo-filtro">
                <span>Ordenar por</span>
                <span className="select-filtro">
                    <select
                        value={filtros.ordenacao}
                        onChange={(evento) => alterar('ordenacao', evento.target.value)}
                    >
                        <option value="">Nome</option>
                        <option value="price_asc">Menor preço</option>
                        <option value="price_desc">Maior preço</option>
                        <option value="name_desc">Nome decrescente</option>
                    </select>
                    <ChevronDown size={15} />
                </span>
            </label>

            <label className="filtro-checkbox">
                <input
                    type="checkbox"
                    checked={filtros.emEstoque}
                    onChange={(evento) => alterar('emEstoque', evento.target.checked)}
                />
                <span />
                Somente disponíveis
            </label>

            <button
                className="limpar-filtros"
                type="button"
                onClick={() =>
                    aoAlterar({
                        categoria: '',
                        marca: '',
                        condicao: '',
                        minimo: '',
                        maximo: '',
                        ordenacao: '',
                        emEstoque: false,
                    })
                }
            >
                Limpar filtros
            </button>
        </aside>
    );
}
